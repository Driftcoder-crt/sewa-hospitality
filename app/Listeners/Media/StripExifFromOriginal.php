<?php

namespace App\Listeners\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Throwable;

/**
 * Ingestion privacy pass (03-technical-specs/09-media-pipeline.md §7
 * "OptimizeOnUpload" + §8 "EXIF GPS stripped on ingest … keep orientation").
 *
 * M0 decision (documented): this runs SYNCHRONOUSLY on MediaHasBeenAddedEvent.
 * That gives deterministic ordering before/around the queued conversion
 * workers — every derived image is produced by a GD/Imagick re-encode that
 * drops metadata anyway, so the whole tree is clean regardless of worker
 * timing. A queued variant with admin progress is an M1 item and must not
 * change the strip behaviour itself.
 *
 * Strategy: for JPEG sources that carry an EXIF block we do NOT surgically
 * remove individual tags — we ALWAYS re-encode the full file through GD
 * (quality 92), which nukes ALL metadata (EXIF GPS, camera fingerprint,
 * embedded thumbnails), applying the EXIF Orientation transform BEFORE the
 * save so orientation is preserved visually. Files without an EXIF block
 * (exif_read_data() === false) carry no EXIF GPS and are left untouched —
 * a pointless re-encode on shared hosting buys nothing.
 *
 * Scope notes:
 * - PNG/WebP are skipped: they carry no EXIF GPS block at all.
 * - Non-raster media (PDFs, SVGs) is skipped by isRasterImage().
 * - XMP/IPTC-only location payloads are out of scope for M0 (documented);
 *   the M3 fixture pack adds EXIF-GPS-injected binaries proving the strip.
 *
 * Error-locks doctrine: this listener must NEVER break the upload flow —
 * the entire body is guarded and any failure is logged to the `ops`
 * channel and swallowed.
 */
final class StripExifFromOriginal
{
    /** One warning per PHP process when GD/exif are unavailable ("once-ish"). */
    private static bool $warnedMissingExtensions = false;

    public function handle(MediaHasBeenAddedEvent $event): void
    {
        try {
            $this->strip($event->media);
        } catch (Throwable $e) {
            // Never break the upload flow (error-locks doctrine): log and
            // return — the upload itself is already committed at this point.
            Log::channel('ops')->error('media.exif_strip.failed', [
                'uuid' => $event->media->uuid ?? null,
                'file_name' => $event->media->file_name ?? null,
                'disk' => $event->media->disk ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function strip(\Spatie\MediaLibrary\MediaCollections\Models\Media $media): void
    {
        // Only our media model understands isRasterImage(); only raster
        // images can carry EXIF in the first place.
        if (! $media instanceof Media || ! $media->isRasterImage()) {
            return;
        }

        // PNG/WebP carry no EXIF GPS — JPEG is the only raster format with
        // an EXIF block in the wild, so it is the only one we re-encode.
        if ($media->mime_type !== 'image/jpeg') {
            return;
        }

        if (! extension_loaded('gd') || ! function_exists('exif_read_data')) {
            if (! self::$warnedMissingExtensions) {
                self::$warnedMissingExtensions = true;
                Log::channel('ops')->warning('media.exif_strip.extensions_missing', [
                    'message' => 'GD and/or exif extension unavailable — original JPEGs are stored untouched (privacy risk), conversions still re-encode cleanly.',
                ]);
            }

            return;
        }

        // Local-disk guard: getPath() is a filesystem path and only meaningful
        // for the local driver. Remote disks (Phase-2 S3) need a streaming
        // re-encode — deliberately skipped, not approximated.
        if (config("filesystems.disks.{$media->disk}.driver") !== 'local') {
            return;
        }

        $absolutePath = $media->getPath();

        if (! is_file($absolutePath)) {
            return;
        }

        $exif = @exif_read_data($absolutePath);

        // No EXIF block at all → no EXIF GPS to strip (covers previously
        // processed/optimized uploads and generated files).
        if ($exif === false) {
            return;
        }

        $hadGps = isset($exif['GPSLatitude'], $exif['GPSLongitude']);

        // EXIF Orientation is an IFD0 tag; anything unknown/missing is
        // treated as "normal" (1) and needs no transform.
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $image = @imagecreatefromjpeg($absolutePath);

        if ($image === false) {
            throw new RuntimeException('imagecreatefromjpeg() could not decode the original file.');
        }

        // Apply the orientation transform BEFORE saving so the re-encoded
        // file stores pixels in their visual orientation (EXIF "keep
        // orientation" = bake it in, drop the tag with the rest).
        $image = $this->applyOrientation($image, $orientation);

        $tmp = tempnam(sys_get_temp_dir(), 'sewa-exif-');

        if ($tmp === false) {
            imagedestroy($image);

            throw new RuntimeException('tempnam() failed while preparing the EXIF strip.');
        }

        if (! imagejpeg($image, $tmp, 92)) {
            imagedestroy($image);
            unlink($tmp);

            throw new RuntimeException('imagejpeg() failed while re-encoding the original file.');
        }

        imagedestroy($image);

        // Replace the original's contents in place (the media row's path
        // stays valid — no Spatie bookkeeping to touch).
        file_put_contents($absolutePath, file_get_contents($tmp));
        unlink($tmp);

        clearstatcache(true, $absolutePath);

        $media->size = (int) filesize($absolutePath);
        $media->save();

        Log::channel('ops')->info('media.exif_strip.cleaned', [
            'uuid' => $media->uuid,
            'had_gps' => $hadGps,
            'orientation' => $orientation,
            'new_size' => $media->size,
        ]);
    }

    /**
     * EXIF Orientation 1–8 → GD transform. imagerotate() turns COUNTER-
     * clockwise for positive angles, so "rotate 90° clockwise" is -90.
     * Background 0 per the M0 decision (corners exposed by rotation are
     * filled black; JPEGs are opaque so alpha never leaks).
     */
    private function applyOrientation(\GdImage $image, int $orientation): \GdImage
    {
        return match ($orientation) {
            2 => $this->flip($image, IMG_FLIP_HORIZONTAL),
            3 => $this->rotate($image, 180),
            4 => $this->flip($image, IMG_FLIP_VERTICAL),
            5 => $this->flip($this->rotate($image, -90), IMG_FLIP_HORIZONTAL), // transpose
            6 => $this->rotate($image, -90),                                   // 90° clockwise
            7 => $this->flip($this->rotate($image, -90), IMG_FLIP_VERTICAL),   // transverse
            8 => $this->rotate($image, 90),                                    // 90° counter-clockwise
            default => $image, // 1 (normal) or unknown values: untouched
        };
    }

    private function flip(\GdImage $image, int $mode): \GdImage
    {
        if (! imageflip($image, $mode)) {
            throw new RuntimeException('imageflip() failed.');
        }

        return $image;
    }

    private function rotate(\GdImage $image, float $degrees): \GdImage
    {
        $rotated = imagerotate($image, $degrees, 0);

        if ($rotated === false) {
            throw new RuntimeException('imagerotate() failed.');
        }

        // imagerotate returns a new canvas (except for 360° rotations);
        // free the source to keep memory flat on 8 MB uploads.
        if ($rotated !== $image) {
            imagedestroy($image);
        }

        return $rotated;
    }
}
