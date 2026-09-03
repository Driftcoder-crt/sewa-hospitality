<?php

namespace App\Modules\Services\Services;

use App\Modules\Cms\Services\BlockRegistry;
use App\Modules\Services\Enums\ServiceStatus;
use App\Modules\Services\Models\Service;
use App\Support\Cms\HtmlSanitizer;
use Throwable;

/**
 * Service publish gate (04-modules/02-services-module.md §5): publish
 * requires meta title + description, intro, hero media with alt, and
 * ≥ 1 content block — the same structural no-broken-hero guarantee as
 * CMS pages (render probe included).
 */
class ServicePublishGate
{
    /**
     * @return array{errors: array<string, string>, warnings: array<string, string>}
     */
    public function inspect(Service $service): array
    {
        $errors = [];
        $warnings = [];

        if (trim((string) $service->meta_title) === '') {
            $errors['meta_title'] = 'Meta title is required before publishing.';
        }

        if (trim((string) $service->meta_description) === '') {
            $errors['meta_description'] = 'Meta description is required before publishing.';
        }

        if (trim((string) $service->intro) === '') {
            $errors['intro'] = 'Intro copy is required before publishing.';
        }

        if ($service->hero_media_id === null) {
            $errors['hero_media'] = 'Hero media is required (with alt text enforced at upload).';
        }

        if ($service->content_blocks === null || $service->content_blocks === []) {
            $errors['content_blocks'] = 'Add at least one content block before publishing.';
        }

        if ($service->noindex) {
            if (trim((string) $service->noindex_reason) === '') {
                $errors['noindex'] = 'A reason is required for noindex (typed confirmation).';
            }
            if ($service->noindex_confirmed_at === null) {
                $errors['noindex_confirm'] = 'Noindex must be explicitly confirmed.';
            }
        }

        // Render probe over content blocks (same BlockRegistry contract).
        foreach ($service->content_blocks ?? [] as $index => $block) {
            $type = is_array($block) ? ($block['type'] ?? '') : '';
            if (! BlockRegistry::has($type)) {
                $errors["block.$index"] = 'Block '.($index + 1).' has an unknown type.';

                continue;
            }

            try {
                view(BlockRegistry::component($type), [
                    'data' => is_array($block) ? ($block['data'] ?? []) : [],
                    'isLead' => false,
                ])->render();
            } catch (Throwable $e) {
                $errors["block.$index"] = 'Block '.($index + 1).' failed to render: '.$e->getMessage();
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    public function passes(Service $service): bool
    {
        return $this->inspect($service)['errors'] === [];
    }

    /** Sanitized intro for hub cards (strip tags, cap length). */
    public static function excerpt(Service $service, int $length = 160): string
    {
        $text = trim(HtmlSanitizer::clean((string) $service->intro));
        $text = strip_tags($text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 1).'…';
    }

    /** Status helper for tests + admin chips. */
    public static function isLive(Service $service): bool
    {
        return $service->status === ServiceStatus::Published;
    }
}
