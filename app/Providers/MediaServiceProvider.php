<?php

namespace App\Providers;

use App\Listeners\Media\StripExifFromOriginal;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

/**
 * Media pipeline wiring (03-technical-specs/09-media-pipeline.md §7/§8).
 *
 * The pipeline contract itself lives in config/media-library.php (disk,
 * media model, queue, hashed file namer) and in the HasSewaMedia concern
 * (conversion set) — this provider only hooks the ingestion events:
 *
 * - MediaHasBeenAddedEvent → StripExifFromOriginal: privacy (EXIF GPS is
 *   stripped on ingest, §8) with orientation preserved (§7
 *   "OptimizeOnUpload"). The listener is synchronous by design so it runs
 *   deterministically before/around the queued conversion workers.
 */
class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nothing to bind — the media model/disk/queue contract is fully
        // declared in config/media-library.php.
    }

    public function boot(): void
    {
        Event::listen(MediaHasBeenAddedEvent::class, StripExifFromOriginal::class);
    }
}
