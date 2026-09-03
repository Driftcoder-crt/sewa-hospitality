<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Models\Page;
use Throwable;

/**
 * Publish gate (04-modules/01-cms.md §5 + seo-technical §1.2): publish
 * is BLOCKED with field-level errors when meta_title/meta_description
 * are empty; noindex requires typed confirmation + reason; every block
 * must render (render probe). Guidance overruns (title >60, desc >160)
 * are warnings, not blockers.
 */
class PublishGate
{
    public function __construct(private readonly PageRenderer $renderer) {}

    /**
     * @return array{errors: array<string, string>, warnings: array<string, string>}
     */
    public function inspect(Page $page): array
    {
        $errors = [];
        $warnings = [];

        $metaTitle = trim((string) $page->meta_title);
        $metaDescription = trim((string) $page->meta_description);

        if ($metaTitle === '') {
            $errors['meta_title'] = 'Meta title is required before publishing.';
        } elseif (mb_strlen($metaTitle) > 60) {
            $warnings['meta_title'] = 'Meta title is '.mb_strlen($metaTitle).' characters — keep it ≤ 60 for search snippets.';
        }

        if ($metaDescription === '') {
            $errors['meta_description'] = 'Meta description is required before publishing.';
        } elseif (mb_strlen($metaDescription) > 160) {
            $warnings['meta_description'] = 'Meta description is '.mb_strlen($metaDescription).' characters — keep it ≤ 160.';
        }

        if ($page->blocks === null || $page->blocks === []) {
            $errors['blocks'] = 'Add at least one block before publishing.';
        }

        if ($page->noindex) {
            if (trim((string) $page->noindex_reason) === '') {
                $errors['noindex'] = 'A reason is required for noindex (typed confirmation).';
            }

            if ($page->noindex_confirmed_at === null) {
                $errors['noindex_confirm'] = 'Noindex must be explicitly confirmed by the editor.';
            }
        }

        // Render probe — a page that cannot render cannot publish.
        try {
            $failures = $this->renderer->probe($page);
        } catch (Throwable $e) {
            $failures = [['index' => -1, 'type' => 'page', 'message' => $e->getMessage()]];
        }

        foreach ($failures as $failure) {
            $label = $failure['type'] !== '' ? ucfirst(str_replace('_', ' ', $failure['type'])) : 'Block';
            $key = $failure['index'] >= 0 ? 'block.'.$failure['index'] : 'render';
            $errors[$key] = ($failure['index'] >= 0 ? $label.' (block '.($failure['index'] + 1).') failed to render: ' : 'Page failed to render: ')
                .$failure['message'];
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    public function passes(Page $page): bool
    {
        return $this->inspect($page)['errors'] === [];
    }
}
