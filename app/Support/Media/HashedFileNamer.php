<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Support\FileNamer\FileNamer;

/**
 * v11-compatible hashed file namer (upstream's HashedFileNamer was
 * removed in v11). Opaque, unguessable names keep user-supplied
 * originals out of the URL path (09-media-pipeline §2 privacy rule).
 */
class HashedFileNamer extends FileNamer
{
    public function originalFileName(string $fileName): string
    {
        return md5(parent::originalFileName($fileName));
    }

    public function conversionFileName(string $fileName, Conversion $conversion): string
    {
        return md5(pathinfo($fileName, PATHINFO_FILENAME)).'-'.$conversion->getName();
    }

    public function responsiveFileName(string $fileName): string
    {
        return md5($fileName);
    }
}
