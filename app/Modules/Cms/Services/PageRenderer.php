<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Models\Page;
use App\Support\Seo\Meta;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Throwable;

/**
 * Page renderer (04-modules/01-cms.md §3): page → blocks → each block
 * through its Blade component, meta service, single-H1 enforcement.
 *
 * Single-H1 rule (ui-components doc): only the page's FIRST block may
 * be the lead — if it is a hero it renders <h1>; every other heading
 * renders <h2> or below (the rich-text sanitizer also demotes h1s).
 * Structurally impossible to publish two H1s.
 *
 * Full-page cache: version-keyed (PageCache) — publish/edits bump the
 * version; stale HTML self-heals within the 10-minute TTL.
 */
class PageRenderer
{
    public function __construct(private readonly Factory $views) {}

    /** Cached public HTML for a published page. */
    public function cachedHtml(Page $page, string $locale): string
    {
        $hit = PageCache::get($page, $locale);
        if (is_string($hit) && $hit !== '') {
            return $hit;
        }

        $html = $this->render($page, $locale)->render();

        PageCache::put($page, $locale, $html);

        return $html;
    }

    /**
     * Uncached render (preview, publish probe, dev gallery).
     * Probe usage wraps this in try/catch — a broken block fails the
     * publish, never the public page (04-modules/01-cms.md §6).
     */
    public function render(Page $page, string $locale = 'en'): View
    {
        $blocks = $page->blockList();

        // Single-H1 resolution: the first known block is the lead.
        $leadIndex = $this->leadIndex($blocks);

        $meta = Meta::forPage($page);

        return $this->views->make('cms.page', [
            'page' => $page,
            'blocks' => $blocks,
            'leadIndex' => $leadIndex,
            'meta' => $meta,
            'metaTags' => $meta->render(),
        ]);
    }

    private function leadIndex(array $blocks): ?int
    {
        foreach ($blocks as $index => $block) {
            if (BlockRegistry::has(is_array($block) ? ($block['type'] ?? '') : '')) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * The publish render probe: render every block in isolation and
     * the whole page once; any Throwable is reported with its block.
     *
     * @return list<array{index: int, type: string, message: string}>
     */
    public function probe(Page $page): array
    {
        $failures = [];
        foreach ($page->blockList() as $index => $block) {
            $type = is_array($block) ? ($block['type'] ?? '') : '';
            if (! BlockRegistry::has($type)) {
                $failures[] = ['index' => $index, 'type' => (string) $type, 'message' => 'Unknown block type.'];

                continue;
            }

            try {
                $data = is_array($block) ? ($block['data'] ?? []) : [];
                $this->views->make(BlockRegistry::component($type), [
                    'data' => $data,
                    'page' => $page,
                    'isLead' => $index === $this->leadIndex($page->blockList()),
                ])->render();
            } catch (Throwable $e) {
                $failures[] = ['index' => $index, 'type' => $type, 'message' => $e->getMessage()];
            }
        }

        try {
            $this->render($page)->render();
        } catch (Throwable $e) {
            $failures[] = ['index' => -1, 'type' => 'page', 'message' => $e->getMessage()];
        }

        return $failures;
    }
}
