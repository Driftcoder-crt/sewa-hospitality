<?php

use App\Models\Media;
use App\Support\Media\MediaUploadRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\In;
use Tests\Support\TestSubject;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Conversions must run INLINE for the assertions below: with the
    // queued default (syncs queue + after-commit dispatch) the jobs would
    // never fire inside RefreshDatabase's open transaction. Production
    // keeps queue_conversions_by_default=true (config/media-library.php).
    config(['media-library.queue_conversions_by_default' => false]);

    Schema::create('test_subjects', function ($table) {
        $table->ulid('id')->primary();
        $table->timestamps();
    });
});

afterEach(function () {
    // Tests run against the REAL public disk (phpunit.xml keeps the suite
    // dependency-free in DB/cache terms, but Spatie writes files). Remove
    // the morph path so the suite leaves no orphaned fixtures behind —
    // exactly the mess media:prune exists to sweep in production.
    Storage::disk('public')->deleteDirectory('Tests/Support/TestSubject');
});

it('stores an upload with the Sewa media columns defaulted correctly', function () {
    $subject = TestSubject::create([]);

    $media = $subject
        ->addMedia(UploadedFile::fake()->image('hero.jpg', 2400, 1350))
        ->toMediaCollection('images');

    expect($media)->toBeInstanceOf(Media::class)
        ->and(Media::query()->count())->toBe(1)
        ->and($media->model_type)->toBe(TestSubject::class)
        ->and($media->model_id)->toBe($subject->id)
        ->and($media->collection_name)->toBe('images')
        ->and($media->fresh()->mime_type)->toBe('image/jpeg')
        // Alt text defaults to '' at ingest; the admin uploader (M1) enforces
        // the required_unless rule before it ever reaches the database.
        ->and($media->fresh()->alt_text)->toBe('')
        ->and($media->fresh()->is_decorative)->toBeFalse();
});

it('generates the full responsive conversion set inline', function () {
    $subject = TestSubject::create([]);

    $media = $subject
        ->addMedia(UploadedFile::fake()->image('hero.jpg', 2400, 1350))
        ->toMediaCollection('images');

    // With queue_conversions_by_default=false the conversions run during
    // addMedia, so generated_conversions is already populated on a fresh
    // read (the exact set is declared in HasSewaMedia, 09-media-pipeline §3).
    $generated = $media->fresh()->generated_conversions;

    $expectedConversions = ['thumb', 'card', 'hero', 'hero-avif', 'wide', 'og'];

    expect($generated)->toHaveKeys($expectedConversions)
        ->and(array_map(
            static fn (string $name): ?bool => $generated[$name] ?? null,
            $expectedConversions,
        ))->each->toBeTrue();
});

it('enforces the alt-text discipline for described and decorative media', function () {
    $subject = TestSubject::create([]);

    $media = $subject
        ->addMedia(UploadedFile::fake()->image('hero.jpg', 2400, 1350))
        ->toMediaCollection('images')
        ->fresh();

    // Ingested without alt text and not decorative → NOT publish-safe.
    expect($media->hasUsableAltText())->toBeFalse()
        ->and($media->effectiveAltText)->toBe('');

    $media->alt_text = 'Consultant handing keys to a family in Gurugram';
    $media->save();

    expect($media->hasUsableAltText())->toBeTrue()
        ->and($media->effectiveAltText)->toBe('Consultant handing keys to a family in Gurugram');

    // Decorative images: empty alt is sanctioned ONLY by the explicit flag.
    $decorative = $subject
        ->addMedia(UploadedFile::fake()->image('divider.png', 40, 40))
        ->toMediaCollection('images');

    $decorative->is_decorative = true;
    $decorative->alt_text = '';
    $decorative->save();

    $decorative = $decorative->fresh();

    expect($decorative->effectiveAltText)->toBe('')
        ->and($decorative->hasUsableAltText())->toBeTrue();
});

it('publishes upload rules that enforce alt text and the namespace whitelist', function () {
    $rules = MediaUploadRules::imageRules('blog');

    expect($rules['alt_text'])->toContain('required_unless:is_decorative,true')
        ->and($rules['alt_text'])->toContain('string')
        ->and($rules['alt_text'])->toContain('max:1000')
        ->and($rules['file'])->toContain('max:8192')
        // svg is filtered out outside the brand namespace.
        ->and($rules['file'])->toContain('mimes:jpg,jpeg,png,webp')
        ->and($rules['focal_point'])->toContain('regex:/^(\d{1,3}(\.\d+)?),(\d{1,3}(\.\d+)?)$/');

    // The namespace rule is a Rule::in over config('sewa.media.namespaces').
    $inRule = collect($rules['namespace'])->first(
        static fn ($rule): bool => $rule instanceof In,
    );

    expect($inRule)->toBeInstanceOf(In::class)
        ->and($rules['namespace'])->toContain('required')
        ->and($rules['namespace'])->toContain('string');

    // Brand gets the svg whitelist; resumes keep their careers-only set.
    expect(MediaUploadRules::imageRules('brand')['file'])->toContain('mimes:jpg,jpeg,png,webp,svg')
        ->and(MediaUploadRules::resumeRules()['file'])->toContain('mimes:pdf,doc,docx')
        ->and(MediaUploadRules::resumeRules()['file'])->toContain('max:5120');
});
