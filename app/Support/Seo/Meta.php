<?php

namespace App\Support\Seo;

use App\Modules\Cms\Models\Page;
use App\Modules\I18n\Services\ContentVariants;
use App\Support\Cms\Identity;
use App\Support\Media\MediaUrl;

/**
 * The single SEO head generator (06-content-seo/02-seo-technical.md
 * §1.1–1.3): title template per type, description, self-referential
 * canonical, robots, Open Graph + Twitter, hreflang alternates — one
 * service, never hand-maintained. Publish gates guarantee non-empty
 * metas; this service renders them.
 *
 * hreflang contract (11-multilingual §3/§5): alternates list every
 * PUBLISHED locale variant of the translation group + x-default → en;
 * locales without a published variant are omitted — hreflang always
 * matches reality.
 */
final class Meta
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $canonical,
        public readonly bool $noindex,
        public readonly ?string $ogImage,
        public readonly ?string $ogImageAlt,
        public readonly array $alternates = [],
    ) {}

    /** Build the head payload for a CMS page (title template per type). */
    public static function forPage(Page $page): self
    {
        $siteName = 'Sewa Hospitality';
        $title = trim((string) $page->meta_title);
        $title = $title !== '' ? $title : $page->title;
        $title .= ' — '.$siteName;

        $description = trim((string) $page->meta_description);

        $canonical = trim((string) $page->canonical_override);
        $canonical = $canonical !== ''
            ? $canonical
            : rtrim(config('app.url', 'https://sewahospitality.com'), '/').$page->publicPath();

        $ogImage = null;
        $ogImageAlt = null;
        if ($page->og_image_media_id) {
            $media = $page->ogMedia;
            if ($media) {
                $ogImage = MediaUrl::to($media->getUrl('og'));
                $ogImageAlt = $media->alt_text;
            }
        }

        return new self(
            $title,
            $description,
            $canonical,
            (bool) $page->noindex, // nullable column → bool contract of this VO
            $ogImage,
            $ogImageAlt,
            $page->noindex ? [] : ContentVariants::alternatesFor($page),
        );
    }

    /**
     * Rendered head tags (blade echoes inside @stack('head')).
     * <title> and the description <meta> render through the layout's
     * `title` / `meta_description` sections (defaulted there) so pages
     * without a Meta payload still carry sane heads — the generator
     * logic for both stays HERE (one service, never hand-maintained).
     */
    public function render(): string
    {
        $identity = Identity::current();
        $siteName = (string) ($identity['brand'] ?? 'Sewa Hospitality');
        $locale = str_replace('_', '-', app()->getLocale());

        $tags = [
            '<meta name="robots" content="'.($this->noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large').'">',
            '<link rel="canonical" href="'.e($this->canonical).'">',
        ];

        // hreflang set: all published locale variants + x-default → en
        // (11-multilingual §3). Sorted code-ascending with x-default last.
        // A noindexed URL leaves the indexable set — it must not stay
        // advertised as an alternate cluster member (the set lives on
        // the canonical, indexed pages only).
        if (! $this->noindex) {
            foreach ($this->alternates as $lang => $href) {
                if ($lang === 'x-default') {
                    continue;
                }

                $tags[] = '<link rel="alternate" hreflang="'.e($lang).'" href="'.e($this->absolute($href)).'">';
            }

            if (isset($this->alternates['x-default'])) {
                $tags[] = '<link rel="alternate" hreflang="x-default" href="'.e($this->absolute($this->alternates['x-default'])).'">';
            }
        }

        $tags[] = '<meta property="og:locale" content="'.e($locale).'">';
        $tags[] = '<meta property="og:type" content="website">';
        $tags[] = '<meta property="og:title" content="'.e($this->title).'">';
        $tags[] = '<meta property="og:description" content="'.e($this->description).'">';
        $tags[] = '<meta property="og:url" content="'.e($this->canonical).'">';
        $tags[] = '<meta property="og:site_name" content="'.e($siteName).'">';

        if ($this->ogImage) {
            $tags[] = '<meta property="og:image" content="'.e($this->ogImage).'">';
            $tags[] = '<meta property="og:image:width" content="1200">';
            $tags[] = '<meta property="og:image:height" content="630">';
            if ($this->ogImageAlt) {
                $tags[] = '<meta property="og:image:alt" content="'.e($this->ogImageAlt).'">';
            }
            $tags[] = '<meta name="twitter:card" content="summary_large_image">';
            $tags[] = '<meta name="twitter:title" content="'.e($this->title).'">';
            $tags[] = '<meta name="twitter:description" content="'.e($this->description).'">';
            $tags[] = '<meta name="twitter:image" content="'.e($this->ogImage).'">';
        }

        return implode("\n    ", $tags);
    }

    /** Alternate paths are root-relative public paths — absolutize. */
    private function absolute(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(config('app.url', 'https://sewahospitality.com'), '/').$path;
    }
}
