<?php

use App\Models\Media;
use App\Support\Media\HashedFileNamer;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Image;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Pdf;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Webp;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

/*
|--------------------------------------------------------------------------
| Spatie laravel-medialibrary configuration (v11)
|--------------------------------------------------------------------------
| Media pipeline owner: 03-technical-specs/09-media-pipeline.md.
|
| NEVER publish the package migrations — the media table schema is owned by
| database/migrations/0001_01_01_000000_create_media_table.php, which also
| carries our columns (alt_text, is_decorative, credit, focal_point,
| namespace, person_consent). `php artisan vendor:publish --tag=media-library-migrations`
| is a forbidden command in this codebase.
*/

return [

    // The public disk (config/filesystems.php) — served via the media.
    // subdomain / storage symlink with immutable cache headers.
    'disk_name' => env('MEDIA_DISK', 'public'),

    // Our own model (extends the Spatie base with the Sewa columns above).
    'media_model' => Media::class,

    // Hard ceiling; the admin validation layer caps images at 8 MB and
    // resumes at 5 MB (config/sewa.php media.*).
    'max_file_size' => (int) env('SEWA_MEDIA_MAX_BYTES', 8 * 1024 * 1024),

    // Conversions run on the `syncs` queue (07-queues-scheduling §2).
    'queue_name' => env('MEDIA_QUEUE', 'syncs'),

    'queue_conversions_by_default' => true,

    // Never queue conversions for rows that fail to commit.
    'queue_conversions_after_db_commit' => true,

    /*
     * Hashed filenames → immutable URLs: a media record's path never changes
     * after upload, so browser/CDN caching is permanent (09-media-pipeline §5).
     */
    'file_namer' => HashedFileNamer::class,

    'path_generator' => DefaultPathGenerator::class,

    // Convertible source types (raster, WebP source, PDF covers). SVG stays
    // unconverted (sanitised upload path instead); video is out of scope.
    'image_generators' => [
        Image::class,
        Webp::class,
        Pdf::class,
    ],

    /*
     * Phase-2 remote storage (unused at launch — everything lives on the
     * `public` disk on the host).
     */
    's3' => [
        'domain' => 'https://s3.amazonaws.com',
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'endpoint' => env('AWS_ENDPOINT'),
    ],

    /*
     * Responsive variants are intentionally disabled: the conversion set
     * (thumb/card/hero/wide/og/pdf-cover) is owned by our pipeline
     * (09-media-pipeline §6), not by on-the-fly srcset generation.
     */
    'responsive_images' => [
        'enabled' => false,
        'width_step' => 200,
        'max_widths' => null,
        'placeholder_svg' => null,
    ],

];
