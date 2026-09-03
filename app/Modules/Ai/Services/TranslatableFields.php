<?php

namespace App\Modules\Ai\Services;

use App\Modules\Blog\Models\Post;
use App\Modules\Cities\Models\City;
use App\Modules\Cms\Models\Page;
use App\Modules\Csr\Models\CsrStory;
use App\Modules\Services\Models\Service;

/**
 * Per-entity translatable field maps for the TranslateContent pipeline
 * (11-multilingual §4). String fields translate verbatim; JSON fields
 * translate value-by-value with the structure preserved (MergeTranslated).
 * The EN source row's other columns are copied untouched.
 */
final class TranslatableFields
{
    /** @return array{strings: list<string>, json: list<string>}|null */
    public static function for(string $entityClass): ?array
    {
        return match ($entityClass) {
            Page::class => [
                'strings' => ['title', 'meta_title', 'meta_description'],
                'json' => ['blocks'],
            ],
            Service::class => [
                'strings' => ['name', 'short_desc', 'intro', 'meta_title', 'meta_description'],
                'json' => ['content_blocks', 'faq'],
            ],
            City::class => [
                'strings' => ['name', 'description', 'meta_title', 'meta_description'],
                'json' => ['content_blocks'],
            ],
            Post::class => [
                'strings' => ['title', 'excerpt', 'body', 'meta_title', 'meta_description'],
                'json' => [],
            ],
            CsrStory::class => [
                'strings' => ['title', 'body'],
                'json' => [],
            ],
            default => null,
        };
    }
}
