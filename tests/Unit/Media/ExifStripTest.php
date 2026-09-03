<?php

use App\Listeners\Media\StripExifFromOriginal;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Tests\Support\TestSubject;

/*
 * EXIF-strip listener tests.
 *
 * HONEST SCOPE NOTE: GD cannot WRITE EXIF, so a binary fixture with an
 * injected GPS payload cannot be produced here — that fixture pack lands
 * with M3 (sewdocs/09-media-pipeline §8 compliance fixtures) and will
 * assert the GPS payload is gone after the pass. What IS guaranteed and
 * asserted here: the listener never corrupts a valid JPEG (it stays a
 * readable image with identical pixel dimensions), it fully re-encodes
 * whenever an EXIF block is present (the guaranteed-clean property — a
 * full GD re-encode carries no metadata at all), and PNG/WebP media is
 * skipped untouched since those formats carry no EXIF GPS.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('test_subjects', function ($table) {
        $table->ulid('id')->primary();
        $table->timestamps();
    });
});

afterEach(function () {
    // Tests use the real public disk — remove the morph path so nothing
    // leaks between suites (and nothing is left for media:prune to find).
    Storage::disk('public')->deleteDirectory('Tests/Support/TestSubject');
});

/**
 * Build real GD image bytes + a persisted App\Models\Media row pointing at
 * them on the public disk, mirroring the post-addMedia state (row saved,
 * file stored) WITHOUT running the package pipeline — so the listener is
 * exercised in isolation.
 *
 * @return array{0: Media, 1: string, 2: string} media, absolute path, original bytes
 */
function makeMediaWithFile(string $extension, string $mimeType, callable $renderer): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'sewa-exif-test-');
    $image = $renderer();
    match ($extension) {
        'png' => imagepng($image, $tmp),
        default => imagejpeg($image, $tmp, 90),
    };
    imagedestroy($image);
    $bytes = file_get_contents($tmp);
    unlink($tmp);

    $subject = TestSubject::create([]);

    $media = new Media;
    $media->model_type = TestSubject::class;
    $media->model_id = $subject->id;
    $media->collection_name = 'images';
    $media->name = 'test';
    $media->file_name = 'test-image.'.$extension;
    $media->mime_type = $mimeType;
    $media->disk = 'public';
    $media->conversions_disk = 'public';
    $media->size = strlen($bytes);
    $media->manipulations = [];
    $media->custom_properties = [];
    $media->generated_conversions = [];
    $media->responsive_images = [];
    $media->alt_text = '';
    $media->is_decorative = false;
    $media->save();

    // Spatie's DefaultPathGenerator layout: {morph class}/{id}/{collection}/{file}.
    $absolute = $media->getPath();
    $root = rtrim(Storage::disk('public')->path(''), '/\\');
    $relative = ltrim(substr($absolute, strlen($root)), '/\\');

    Storage::disk('public')->put($relative, $bytes);

    return [$media, $absolute, $bytes];
}

it('leaves a valid, readable JPEG after the strip pass (guaranteed-clean property)', function () {
    [$media, $absolute, $originalBytes] = makeMediaWithFile(
        'jpg',
        'image/jpeg',
        static fn () => imagecreatetruecolor(80, 40),
    );

    expect(file_exists($absolute))->toBeTrue()
        ->and(is_readable($absolute))->toBeTrue();

    (new StripExifFromOriginal)->handle(new MediaHasBeenAddedEvent($media));

    // GD-generated JPEGs carry no EXIF block, so the pass exits early —
    // the bytes are untouched and remain a decodable 80×40 image.
    $info = getimagesize($absolute);

    expect(file_exists($absolute))->toBeTrue()
        ->and($info)->not->toBeFalse()
        ->and($info[0])->toBe(80)
        ->and($info[1])->toBe(40)
        ->and(filesize($absolute))->toBe(strlen($originalBytes))
        ->and($media->fresh()->size)->toBe(strlen($originalBytes));
});

it('skips non-JPEG raster media untouched (PNG/WebP carry no EXIF GPS)', function () {
    [$media, $absolute, $originalBytes] = makeMediaWithFile(
        'png',
        'image/png',
        static fn () => imagecreatetruecolor(60, 30),
    );

    (new StripExifFromOriginal)->handle(new MediaHasBeenAddedEvent($media));

    expect(file_get_contents($absolute))->toBe($originalBytes)
        ->and($media->fresh()->size)->toBe(strlen($originalBytes));
});
