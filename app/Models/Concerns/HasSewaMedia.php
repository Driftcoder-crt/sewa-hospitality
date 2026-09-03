<?php

namespace App\Models\Concerns;

use Spatie\Image\Enums\CropPosition;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The platform-wide responsive conversion set
 * (03-technical-specs/09-media-pipeline.md §3). Every content model that
 * renders media uses this trait so the whole site ships the same sizes,
 * formats (WebP q78 / AVIF q50) and focal-aware crops — zero CLS by
 * contract, since <x-media> always knows the output dimensions.
 *
 * Focal point: stored as "x,y" percentages ("32.5,40.0" = 32.5% from the
 * left, 40.0% from the top). Mapped to the nearest of Spatie's nine crop
 * positions — precise sub-rect focal cropping is an M1 polish item and
 * will not change the stored format.
 */
trait HasSewaMedia
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        $position = $this->cropPositionFor($media);

        // crop() needs explicit dimensions in this medialibrary version:
        // ImageDriver::crop(int $width, int $height, CropPosition) — the
        // legacy crop($position)-only form lands the enum in $width.
        $webp = fn (string $name, int $width, int $height) => $this
            ->addMediaConversion($name)
            ->width($width)
            ->height($height)
            ->crop($width, $height, $position)
            ->format('webp')
            ->quality(78);

        $webp('thumb', 150, 150);
        $webp('card', 600, 400);
        $webp('hero', 1600, 900);
        $webp('wide', 1920, 1080);

        $this->addMediaConversion('hero-avif')
            ->width(1600)
            ->height(900)
            ->crop(1600, 900, $position)
            ->format('avif')
            ->quality(50);

        $this->addMediaConversion('og')
            ->width(1200)
            ->height(630)
            ->crop(1200, 630, $position)
            ->format('jpg')
            ->quality(80);

        // Portal document previews (M5). Only meaningful for PDFs with an
        // imagick-backed driver; safe to declare, no-ops otherwise.
        $this->addMediaConversion('pdf-cover')
            ->width(600)
            ->height(800)
            ->format('webp')
            ->quality(78);
    }

    private function cropPositionFor(?Media $media): CropPosition
    {
        $focal = trim((string) ($media?->focal_point ?? ''));

        if ($focal === '' || ! str_contains($focal, ',')) {
            return CropPosition::Center;
        }

        [$x, $y] = array_map(
            static fn (string $v): float => min(100.0, max(0.0, (float) $v)),
            explode(',', $focal),
        );

        return match (true) {
            $y < 33.3 && $x < 50.0 => CropPosition::TopLeft,
            $y < 33.3 && $x > 66.6 => CropPosition::TopRight,
            $y < 33.3 => CropPosition::Top,
            $y > 66.6 && $x < 50.0 => CropPosition::BottomLeft,
            $y > 66.6 && $x > 66.6 => CropPosition::BottomRight,
            $y > 66.6 => CropPosition::Bottom,
            $x < 50.0 => CropPosition::Left,
            $x > 66.6 => CropPosition::Right,
            default => CropPosition::Center,
        };
    }
}
