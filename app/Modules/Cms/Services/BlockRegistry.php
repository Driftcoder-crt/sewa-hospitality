<?php

namespace App\Modules\Cms\Services;

use InvalidArgumentException;

/**
 * The typed block registry — M1 wave-1 of the 47-block catalog
 * (05-design-system/05-section-block-library.md §10: M1 = A1–A4,
 * B1–B4, E1). Every block declares its editor-facing fields, its
 * validation contract (blocks validate their data shape on save —
 * 04-modules/01-cms.md §5) and its perf budget (DOM nodes + media
 * weight, composer sums them — §8.7).
 *
 * Wave additions land here: M2 = C1–C5, D1–D3, B5–B9 ·
 * M3 = E2–E8, D4–D6 · M4 = F1–F10, C6–C8, A5–A8.
 */
final class BlockRegistry
{
    /** @var array<string, array<string, mixed>> type => definition */
    private static ?array $blocks = null;

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return self::$blocks ??= self::definitions();
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, self::all());
    }

    public static function definition(string $type): array
    {
        $block = self::all()[$type] ?? throw new InvalidArgumentException("Unknown block type [{$type}].");

        return $block;
    }

    public static function component(string $type): string
    {
        return self::definition($type)['component'];
    }

    /** Editor label (library code + name, e.g. "A1 · Hero"). */
    public static function label(string $type): string
    {
        $block = self::definition($type);

        return $block['code'].' · '.$block['label'];
    }

    /**
     * Grouped for the "Add block" picker.
     *
     * @return array<string, array<string, string>> category => type => label
     */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::all() as $type => $block) {
            $groups[$block['category']][$type] = self::label($type);
        }

        return $groups;
    }

    /**
     * Validate one block's data shape; returns field-level errors
     * ([] = valid). Blocks with repeatable structures validate every
     * item; missing media ids are caught by the render probe, not here.
     *
     * @return array<string, string> field => message
     */
    public static function validate(string $type, mixed $data): array
    {
        $block = self::definition($type);
        $errors = [];
        $data = is_array($data) ? $data : [];

        foreach ($block['fields'] as $name => $field) {
            $value = $data[$name] ?? null;
            $required = $field['required'] ?? false;

            if ($required && ($value === null || $value === '' || $value === [])) {
                $errors[$name] = ucfirst(str_replace('_', ' ', $name)).' is required.';

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $max = $field['max'] ?? null;
            if (is_string($value) && $max !== null && mb_strlen($value) > $max) {
                $errors[$name] = ucfirst(str_replace('_', ' ', $name))." must be ≤ {$max} characters ({$field['label']}).";
            }
        }

        return $errors;
    }

    /** Page-composer budget accounting (section-library §8.7). */
    public static function budget(string $type): array
    {
        return self::definition($type)['budget'];
    }

    /** @return array<string, array<string, mixed>> */
    private static function definitions(): array
    {
        return [

            /*
             | A1 — Hero (section-library §2): headline, sub, CTAs[1–2],
             | media, align, theme, height preset, overlay scrim, eyebrow.
             | The page's FIRST hero renders the single H1 (template-
             | enforced — ui-components doc).
             */
            'hero' => [
                'code' => 'A1',
                'label' => 'Hero',
                'category' => 'Layout & Structure',
                'component' => 'blocks.hero',
                'budget' => ['dom' => 30, 'kb' => 90],
                'fields' => [
                    'headline' => ['label' => 'Headline', 'type' => 'text', 'required' => true, 'max' => 120],
                    'sub' => ['label' => 'Sub-headline', 'type' => 'textarea', 'max' => 240],
                    'eyebrow' => ['label' => 'Eyebrow label', 'type' => 'text', 'max' => 60],
                    'media_id' => ['label' => 'Background media', 'type' => 'media'],
                    'overlay' => ['label' => 'Overlay strength', 'type' => 'select', 'options' => ['none' => 'None', 'soft' => 'Soft', 'strong' => 'Strong'], 'default' => 'soft'],
                    'height' => ['label' => 'Height preset', 'type' => 'select', 'options' => ['full' => 'Full', 'split' => 'Split', 'compact' => 'Compact'], 'default' => 'compact'],
                    'align' => ['label' => 'Alignment', 'type' => 'select', 'options' => ['start' => 'Start', 'center' => 'Center'], 'default' => 'start'],
                    'ctas' => ['label' => 'Buttons', 'type' => 'ctas', 'max_items' => 2],
                ],
            ],

            /*
             | A2 — Split Hero: asymmetric copy/media split (wild-ag/nomu
             | pattern). The optional email mini-form renders ONLY once
             | the leads module exists (M3) — until then it is hidden so
             | nothing dead-ends (ux-interactions §1).
             */
            'split_hero' => [
                'code' => 'A2',
                'label' => 'Split Hero',
                'category' => 'Layout & Structure',
                'component' => 'blocks.split-hero',
                'budget' => ['dom' => 40, 'kb' => 110],
                'fields' => [
                    'headline' => ['label' => 'Headline', 'type' => 'text', 'required' => true, 'max' => 120],
                    'sub' => ['label' => 'Sub-headline', 'type' => 'textarea', 'max' => 240],
                    'eyebrow' => ['label' => 'Eyebrow label', 'type' => 'text', 'max' => 60],
                    'media_id' => ['label' => 'Media', 'type' => 'media'],
                    'media_side' => ['label' => 'Media side', 'type' => 'select', 'options' => ['left' => 'Left', 'right' => 'Right'], 'default' => 'right'],
                    'ctas' => ['label' => 'Buttons', 'type' => 'ctas', 'max_items' => 2],
                ],
            ],

            /*
             | A3 — Section Wrapper: the framing primitive every other
             | block sits in — theme slot, anchor id, eyebrow/title/intro,
             | padding density. Renders its children via $slot.
             */
            'section_wrapper' => [
                'code' => 'A3',
                'label' => 'Section Wrapper',
                'category' => 'Layout & Structure',
                'component' => 'blocks.section-wrapper',
                'budget' => ['dom' => 6, 'kb' => 0],
                'fields' => [
                    'eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'max' => 60],
                    'title' => ['label' => 'Section title', 'type' => 'text', 'max' => 120],
                    'intro' => ['label' => 'Intro copy', 'type' => 'textarea', 'max' => 320],
                    'anchor_id' => ['label' => 'Anchor id', 'type' => 'text', 'max' => 60],
                    'density' => ['label' => 'Padding density', 'type' => 'select', 'options' => ['compact' => 'Compact', 'normal' => 'Normal', 'spacious' => 'Spacious'], 'default' => 'normal'],
                ],
            ],

            /*
             | A4 — Feature Grid: 2/3/4-col cards (border/plain/filled),
             | per-cell icon + title + text + optional link.
             */
            'feature_grid' => [
                'code' => 'A4',
                'label' => 'Feature Grid',
                'category' => 'Layout & Structure',
                'component' => 'blocks.feature-grid',
                'budget' => ['dom' => 8, 'kb' => 0],
                'fields' => [
                    'columns' => ['label' => 'Columns', 'type' => 'select', 'options' => ['2' => '2 columns', '3' => '3 columns', '4' => '4 columns'], 'default' => '3'],
                    'style' => ['label' => 'Card style', 'type' => 'select', 'options' => ['border' => 'Border', 'plain' => 'Plain', 'filled' => 'Filled'], 'default' => 'border'],
                    'items' => ['label' => 'Cards', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 80],
                        'text' => ['label' => 'Text', 'type' => 'textarea', 'max' => 240],
                        'icon' => ['label' => 'Icon key', 'type' => 'text', 'max' => 40],
                        'url' => ['label' => 'Link URL', 'type' => 'text', 'max' => 300],
                    ]],
                ],
            ],

            /*
             | B1 — Rich Text: sanitized wysiwyg with heading-ladder
             | enforcement (h1 demoted to h2 — single-H1 rule), pull-
             | quote + callout styles. Sanitizer whitelist is the
             | contract; scripts/handlers never survive.
             */
            'rich_text' => [
                'code' => 'B1',
                'label' => 'Rich Text',
                'category' => 'Editorial & Content',
                'component' => 'blocks.rich-text',
                'budget' => ['dom' => 15, 'kb' => 0],
                'fields' => [
                    'html' => ['label' => 'Content', 'type' => 'html', 'required' => true],
                ],
            ],

            /*
             | B2 — Text + Media: flagship editorial layout, copy
             | side + media + caption, flip flag.
             */
            'text_media' => [
                'code' => 'B2',
                'label' => 'Text + Media',
                'category' => 'Editorial & Content',
                'component' => 'blocks.text-media',
                'budget' => ['dom' => 18, 'kb' => 120],
                'fields' => [
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 120],
                    'copy' => ['label' => 'Copy (HTML)', 'type' => 'html', 'required' => true],
                    'media_id' => ['label' => 'Media', 'type' => 'media'],
                    'caption' => ['label' => 'Caption', 'type' => 'text', 'max' => 200],
                    'media_side' => ['label' => 'Media side', 'type' => 'select', 'options' => ['left' => 'Left', 'right' => 'Right'], 'default' => 'right'],
                ],
            ],

            /*
             | B3 — Chapter Heading: big serif display divider with
             | optional number + rule line (orionix-style). Renders a
             | heading only when the page has no H1 yet (ladder rule).
             */
            'chapter_heading' => [
                'code' => 'B3',
                'label' => 'Chapter Heading',
                'category' => 'Editorial & Content',
                'component' => 'blocks.chapter-heading',
                'budget' => ['dom' => 5, 'kb' => 0],
                'fields' => [
                    'title' => ['label' => 'Display title', 'type' => 'text', 'required' => true, 'max' => 120],
                    'number' => ['label' => 'Number (e.g. 01)', 'type' => 'text', 'max' => 8],
                ],
            ],

            /*
             | B4 — Accordion: single-source items (no DOM duplication —
             | the reference's structural defect), native <details> +
             | aria, first-open flag, one-at-a-time via Alpine group.
             */
            'accordion' => [
                'code' => 'B4',
                'label' => 'Accordion',
                'category' => 'Editorial & Content',
                'component' => 'blocks.accordion',
                'budget' => ['dom' => 10, 'kb' => 0],
                'fields' => [
                    'first_open' => ['label' => 'First item open', 'type' => 'boolean', 'default' => true],
                    'items' => ['label' => 'Items', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 120],
                        'body_html' => ['label' => 'Body (HTML)', 'type' => 'html', 'required' => true],
                    ]],
                ],
            ],

            /*
             | E1 — CTA Band: the recurring conversion band — headline,
             | copy, 1–2 buttons, brand/deep theme, centered/split.
             */
            'cta_band' => [
                'code' => 'E1',
                'label' => 'CTA Band',
                'category' => 'Promotional & Conversion',
                'component' => 'blocks.cta-band',
                'budget' => ['dom' => 14, 'kb' => 0],
                'fields' => [
                    'headline' => ['label' => 'Headline', 'type' => 'text', 'required' => true, 'max' => 120],
                    'copy' => ['label' => 'Copy', 'type' => 'textarea', 'max' => 240],
                    'theme' => ['label' => 'Theme', 'type' => 'select', 'options' => ['brand' => 'Brand (teal)', 'deep' => 'Deep (ink)'], 'default' => 'brand'],
                    'layout' => ['label' => 'Layout', 'type' => 'select', 'options' => ['centered' => 'Centered', 'split' => 'Split'], 'default' => 'centered'],
                    'ctas' => ['label' => 'Buttons', 'type' => 'ctas', 'max_items' => 2],
                ],
            ],

            /*
             | ── Wave 2 (M2) — section-library §4–5 + §3 remainder ──────
             */

            // B5 — Tabs: deep-linkable via ?tab= (contact offices pattern).
            'tabs' => [
                'code' => 'B5',
                'label' => 'Tabs',
                'category' => 'Editorial & Content',
                'component' => 'blocks.tabs',
                'budget' => ['dom' => 12, 'kb' => 0],
                'fields' => [
                    'items' => ['label' => 'Tabs', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'label' => ['label' => 'Tab label', 'type' => 'text', 'required' => true, 'max' => 40],
                        'content_html' => ['label' => 'Content (HTML)', 'type' => 'html', 'required' => true],
                    ]],
                ],
            ],

            // B6 — Timeline: dated milestones (relocation journey).
            'timeline' => [
                'code' => 'B6',
                'label' => 'Timeline',
                'category' => 'Editorial & Content',
                'component' => 'blocks.timeline',
                'budget' => ['dom' => 12, 'kb' => 0],
                'fields' => [
                    'items' => ['label' => 'Milestones', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'date' => ['label' => 'Date label', 'type' => 'text', 'required' => true, 'max' => 40],
                        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 80],
                        'text' => ['label' => 'Text', 'type' => 'textarea', 'max' => 240],
                    ]],
                ],
            ],

            // B7 — FAQ: renders FAQPage schema (schema-matches-visible).
            'faq' => [
                'code' => 'B7',
                'label' => 'FAQ',
                'category' => 'Editorial & Content',
                'component' => 'blocks.faq',
                'budget' => ['dom' => 10, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 80],
                    'items' => ['label' => 'Questions', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'q' => ['label' => 'Question', 'type' => 'text', 'required' => true, 'max' => 160],
                        'a' => ['label' => 'Answer', 'type' => 'textarea', 'required' => true, 'max' => 600],
                    ]],
                ],
            ],

            // B8 — Comparison Table: 2–4 columns, highlight column.
            'comparison_table' => [
                'code' => 'B8',
                'label' => 'Comparison Table',
                'category' => 'Editorial & Content',
                'component' => 'blocks.comparison-table',
                'budget' => ['dom' => 16, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 80],
                    'columns' => ['label' => 'Columns', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'label' => ['label' => 'Column label', 'type' => 'text', 'required' => true, 'max' => 40],
                    ]],
                    'rows' => ['label' => 'Rows', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'label' => ['label' => 'Row label', 'type' => 'text', 'required' => true, 'max' => 60],
                        'values' => ['label' => 'Values (comma-separated, one per column)', 'type' => 'text', 'required' => true, 'max' => 300],
                    ]],
                    'highlight' => ['label' => 'Highlighted column (1-based)', 'type' => 'text', 'max' => 2],
                ],
            ],

            // B9 — Story Pillars: 3–5 tall cards, title + hook.
            'story_pillars' => [
                'code' => 'B9',
                'label' => 'Story Pillars',
                'category' => 'Editorial & Content',
                'component' => 'blocks.story-pillars',
                'budget' => ['dom' => 10, 'kb' => 0],
                'fields' => [
                    'items' => ['label' => 'Pillars', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 60],
                        'hook' => ['label' => 'Two-line hook', 'type' => 'textarea', 'required' => true, 'max' => 160],
                        'media_id' => ['label' => 'Tall image media', 'type' => 'media'],
                    ]],
                ],
            ],

            // C1 — Gallery Grid: real <img>+alt, captions, aspect presets.
            'gallery_grid' => [
                'code' => 'C1',
                'label' => 'Gallery Grid',
                'category' => 'Media & Visual',
                'component' => 'blocks.gallery-grid',
                'budget' => ['dom' => 10, 'kb' => 140],
                'fields' => [
                    'columns' => ['label' => 'Columns', 'type' => 'select', 'options' => ['2' => '2 columns', '3' => '3 columns', '4' => '4 columns'], 'default' => '3'],
                    'aspect' => ['label' => 'Aspect', 'type' => 'select', 'options' => ['square' => 'Square', 'landscape' => 'Landscape', 'portrait' => 'Portrait'], 'default' => 'landscape'],
                    'items' => ['label' => 'Images', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'media_id' => ['label' => 'Media', 'type' => 'media', 'required' => true],
                        'caption' => ['label' => 'Caption', 'type' => 'text', 'max' => 140],
                    ]],
                ],
            ],

            // C2 — Carousel: scroll-snap + arrows + dots, CSS-first.
            'carousel' => [
                'code' => 'C2',
                'label' => 'Carousel',
                'category' => 'Media & Visual',
                'component' => 'blocks.carousel',
                'budget' => ['dom' => 12, 'kb' => 140],
                'fields' => [
                    'autoplay' => ['label' => 'Autoplay (off by default)', 'type' => 'boolean', 'default' => false],
                    'items' => ['label' => 'Slides', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'media_id' => ['label' => 'Media', 'type' => 'media', 'required' => true],
                        'caption' => ['label' => 'Caption', 'type' => 'text', 'max' => 140],
                    ]],
                ],
            ],

            // C3 — Full-Bleed Media: cinematic section (deep theme).
            'full_bleed_media' => [
                'code' => 'C3',
                'label' => 'Full-Bleed Media',
                'category' => 'Media & Visual',
                'component' => 'blocks.full-bleed-media',
                'budget' => ['dom' => 8, 'kb' => 200],
                'fields' => [
                    'media_id' => ['label' => 'Media', 'type' => 'media', 'required' => true],
                    'caption' => ['label' => 'Caption', 'type' => 'text', 'max' => 160],
                    'quote' => ['label' => 'Overlay quote (optional)', 'type' => 'textarea', 'max' => 200],
                    'height' => ['label' => 'Height', 'type' => 'select', 'options' => ['half' => 'Half viewport', 'full' => 'Full viewport'], 'default' => 'half'],
                ],
            ],

            // C4 — Video Feature: facade + play on intent only.
            'video_feature' => [
                'code' => 'C4',
                'label' => 'Video Feature',
                'category' => 'Media & Visual',
                'component' => 'blocks.video-feature',
                'budget' => ['dom' => 8, 'kb' => 60],
                'fields' => [
                    'youtube_id' => ['label' => 'YouTube id', 'type' => 'text', 'required' => true, 'max' => 24],
                    'poster_media_id' => ['label' => 'Poster media', 'type' => 'media'],
                    'title' => ['label' => 'Title', 'type' => 'text', 'max' => 100],
                    'caption' => ['label' => 'Caption', 'type' => 'text', 'max' => 160],
                ],
            ],

            // C5 — Logo Cloud: only badges actually held (trust rule).
            'logo_cloud' => [
                'code' => 'C5',
                'label' => 'Logo Cloud',
                'category' => 'Media & Visual',
                'component' => 'blocks.logo-cloud',
                'budget' => ['dom' => 8, 'kb' => 30],
                'fields' => [
                    'source' => ['label' => 'Source group', 'type' => 'select', 'options' => ['memberships' => 'Memberships (settings)', 'partners' => 'Partners (settings)'], 'default' => 'memberships'],
                    'manual_items' => ['label' => 'Manual entries', 'type' => 'items', 'item_fields' => [
                        'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 60],
                        'url' => ['label' => 'Proof link', 'type' => 'text', 'max' => 300],
                    ]],
                ],
            ],

            // D1 — Testimonial Grid: module-fed, graceful zero-state (M4).
            'testimonial_grid' => [
                'code' => 'D1',
                'label' => 'Testimonial Grid',
                'category' => 'Social Proof & Trust',
                'component' => 'blocks.testimonial-grid',
                'budget' => ['dom' => 14, 'kb' => 0],
                'fields' => [
                    'source' => ['label' => 'Source filter', 'type' => 'select', 'options' => ['home' => 'Homepage pick', 'service' => 'Per-service', 'city' => 'Per-city'], 'default' => 'home'],
                    'limit' => ['label' => 'Max shown', 'type' => 'text', 'max' => 2, 'default' => '4'],
                ],
            ],

            // D2 — Review Highlights: live GBP "as of" (M4 wiring).
            'review_highlights' => [
                'code' => 'D2',
                'label' => 'Review Highlights',
                'category' => 'Social Proof & Trust',
                'component' => 'blocks.review-highlights',
                'budget' => ['dom' => 12, 'kb' => 0],
                'fields' => [
                    'link_reviews' => ['label' => 'Link to /reviews', 'type' => 'boolean', 'default' => true],
                ],
            ],

            // D3 — Stats Band: honest counters with as-of, serif numerals.
            'stats_band' => [
                'code' => 'D3',
                'label' => 'Stats Band',
                'category' => 'Social Proof & Trust',
                'component' => 'blocks.stats-band',
                'budget' => ['dom' => 10, 'kb' => 0],
                'fields' => [
                    'as_of' => ['label' => 'As-of label (e.g. "As of Aug 2026")', 'type' => 'text', 'required' => true, 'max' => 60],
                    'items' => ['label' => 'Stats', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'value' => ['label' => 'Value (honest, dated)', 'type' => 'text', 'required' => true, 'max' => 12],
                        'suffix' => ['label' => 'Suffix (+, %)', 'type' => 'text', 'max' => 4],
                        'label' => ['label' => 'Label', 'type' => 'text', 'required' => true, 'max' => 60],
                    ]],
                ],
            ],
            /*
             | ── Wave 3 (M3) — section-library §5–6: E2–E8 + D4–D6 ────────
             */

            // E2 — Lead Form Section: form island + benefits + privacy note.
            'lead_form' => [
                'code' => 'E2',
                'label' => 'Lead Form',
                'category' => 'Promotional & Conversion',
                'component' => 'blocks.lead-form',
                'budget' => ['dom' => 40, 'kb' => 0],
                'fields' => [
                    'form_type' => ['label' => 'Form type', 'type' => 'select', 'options' => ['contact' => 'Contact', 'quote' => 'Quote request', 'callback' => 'Callback'], 'default' => 'contact'],
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 120],
                    'intro' => ['label' => 'Intro copy', 'type' => 'textarea', 'max' => 240],
                    'service_id' => ['label' => 'Pre-selected service id (context)', 'type' => 'text', 'max' => 40],
                    'benefits' => ['label' => 'Benefits beside the form', 'type' => 'items', 'item_fields' => [
                        'text' => ['label' => 'Benefit line', 'type' => 'text', 'required' => true, 'max' => 120],
                    ]],
                    'privacy_note' => ['label' => 'Privacy note', 'type' => 'textarea', 'max' => 240],
                ],
            ],

            // E3 — Offer Banner: dismissible strip with code chip.
            'offer_banner' => [
                'code' => 'E3',
                'label' => 'Offer Banner',
                'category' => 'Promotional & Conversion',
                'component' => 'blocks.offer-banner',
                'budget' => ['dom' => 10, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'required' => true, 'max' => 80],
                    'copy' => ['label' => 'Copy', 'type' => 'textarea', 'max' => 200],
                    'code' => ['label' => 'Promo code chip', 'type' => 'text', 'max' => 24],
                    'cta_label' => ['label' => 'Button label', 'type' => 'text', 'max' => 40],
                    'cta_url' => ['label' => 'Button URL', 'type' => 'text', 'max' => 300],
                    'theme' => ['label' => 'Theme', 'type' => 'select', 'options' => ['brand' => 'Brand', 'sand' => 'Sand'], 'default' => 'brand'],
                ],
            ],

            // E4 — Newsletter Capture: inline + modal variants, double opt-in.
            'newsletter_capture' => [
                'code' => 'E4',
                'label' => 'Newsletter Capture',
                'category' => 'Promotional & Conversion',
                'component' => 'blocks.newsletter-capture',
                'budget' => ['dom' => 18, 'kb' => 0],
                'fields' => [
                    'variant' => ['label' => 'Variant', 'type' => 'select', 'options' => ['inline' => 'Inline', 'modal' => 'Modal (button opens dialog)'], 'default' => 'inline'],
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'required' => true, 'max' => 100],
                    'copy' => ['label' => 'Copy', 'type' => 'textarea', 'max' => 200],
                    'note' => ['label' => 'Small print', 'type' => 'textarea', 'max' => 160],
                    'theme' => ['label' => 'Theme', 'type' => 'select', 'options' => ['light' => 'Light', 'brand' => 'Brand', 'deep' => 'Deep'], 'default' => 'light'],
                ],
            ],

            // E5 — Promo Card Grid: 2–4 offer cards.
            'promo_card_grid' => [
                'code' => 'E5',
                'label' => 'Promo Card Grid',
                'category' => 'Promotional & Conversion',
                'component' => 'blocks.promo-card-grid',
                'budget' => ['dom' => 16, 'kb' => 0],
                'fields' => [
                    'columns' => ['label' => 'Columns', 'type' => 'select', 'options' => ['2' => '2 columns', '3' => '3 columns', '4' => '4 columns'], 'default' => '3'],
                    'items' => ['label' => 'Offer cards', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 60],
                        'terms' => ['label' => 'Terms', 'type' => 'textarea', 'max' => 160],
                        'badge' => ['label' => 'Badge', 'type' => 'text', 'max' => 24],
                        'cta_label' => ['label' => 'CTA label', 'type' => 'text', 'max' => 40],
                        'cta_url' => ['label' => 'CTA URL', 'type' => 'text', 'max' => 300],
                        'validity' => ['label' => 'Validity line', 'type' => 'text', 'max' => 60],
                    ]],
                ],
            ],

            // E6 — Countdown Promo: deadline urgency, expires gracefully.
            'countdown_promo' => [
                'code' => 'E6',
                'label' => 'Countdown Promo',
                'category' => 'Promotional & Conversion',
                'component' => 'blocks.countdown-promo',
                'budget' => ['dom' => 14, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'required' => true, 'max' => 100],
                    'copy' => ['label' => 'Copy', 'type' => 'textarea', 'max' => 200],
                    'deadline' => ['label' => 'Deadline (ISO datetime, IST)', 'type' => 'text', 'required' => true, 'max' => 30],
                    'cta_label' => ['label' => 'Button label', 'type' => 'text', 'max' => 40],
                    'cta_url' => ['label' => 'Button URL', 'type' => 'text', 'max' => 300],
                    'theme' => ['label' => 'Theme', 'type' => 'select', 'options' => ['brand' => 'Brand', 'deep' => 'Deep'], 'default' => 'deep'],
                ],
            ],

            // E7 — Exit-Intent Modal: never first paint, 1/7d frequency cap.
            'exit_intent_modal' => [
                'code' => 'E7',
                'label' => 'Exit-Intent Modal',
                'category' => 'Promotional & Conversion',
                'component' => 'blocks.exit-intent-modal',
                'budget' => ['dom' => 22, 'kb' => 0],
                'fields' => [
                    'trigger' => ['label' => 'Trigger', 'type' => 'select', 'options' => ['exit' => 'Exit intent', 'scroll' => 'Scroll depth', 'time' => 'Time on page'], 'default' => 'exit'],
                    'scroll_pct' => ['label' => 'Scroll % (scroll trigger)', 'type' => 'text', 'max' => 3, 'default' => '60'],
                    'delay_seconds' => ['label' => 'Delay seconds (time trigger)', 'type' => 'text', 'max' => 3, 'default' => '20'],
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'required' => true, 'max' => 100],
                    'copy' => ['label' => 'Copy', 'type' => 'textarea', 'max' => 240],
                    'mode' => ['label' => 'Body', 'type' => 'select', 'options' => ['newsletter' => 'Newsletter form', 'cta' => 'Call-to-action buttons'], 'default' => 'newsletter'],
                    'ctas' => ['label' => 'Buttons (cta mode)', 'type' => 'ctas', 'max_items' => 2],
                ],
            ],

            // E8 — Sticky CTA Bar: mobile bottom bar + desktop rail, dismiss memory.
            'sticky_cta_bar' => [
                'code' => 'E8',
                'label' => 'Sticky CTA Bar',
                'category' => 'Promotional & Conversion',
                'component' => 'blocks.sticky-cta-bar',
                'budget' => ['dom' => 12, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Desktop rail label', 'type' => 'text', 'max' => 60],
                    'items' => ['label' => 'Actions', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'label' => ['label' => 'Label', 'type' => 'text', 'required' => true, 'max' => 24],
                        'url' => ['label' => 'URL (tel:, https://wa.me/…, /contact)', 'type' => 'text', 'required' => true, 'max' => 300],
                        'icon' => ['label' => 'Icon (call|whatsapp|chat)', 'type' => 'text', 'max' => 20],
                    ]],
                ],
            ],

            // D4 — Trust Checklist: Sewa-Verified style standards list.
            'trust_checklist' => [
                'code' => 'D4',
                'label' => 'Trust Checklist',
                'category' => 'Social Proof & Trust',
                'component' => 'blocks.trust-checklist',
                'budget' => ['dom' => 12, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                    'link_verified' => ['label' => 'Link to /housing/verified', 'type' => 'boolean', 'default' => true],
                    'items' => ['label' => 'Checklist', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'text' => ['label' => 'Check item', 'type' => 'text', 'required' => true, 'max' => 140],
                    ]],
                ],
            ],

            // D5 — Case Story: anonymized challenge→approach→outcome + metrics.
            'case_story' => [
                'code' => 'D5',
                'label' => 'Case Story',
                'category' => 'Social Proof & Trust',
                'component' => 'blocks.case-story',
                'budget' => ['dom' => 18, 'kb' => 0],
                'fields' => [
                    'client_label' => ['label' => 'Client label (anonymized)', 'type' => 'text', 'required' => true, 'max' => 60],
                    'challenge' => ['label' => 'Challenge', 'type' => 'textarea', 'required' => true, 'max' => 400],
                    'approach' => ['label' => 'Approach', 'type' => 'textarea', 'required' => true, 'max' => 400],
                    'outcome' => ['label' => 'Outcome', 'type' => 'textarea', 'required' => true, 'max' => 400],
                    'metrics' => ['label' => 'Metrics', 'type' => 'items', 'item_fields' => [
                        'value' => ['label' => 'Value', 'type' => 'text', 'required' => true, 'max' => 16],
                        'label' => ['label' => 'Label', 'type' => 'text', 'required' => true, 'max' => 60],
                    ]],
                ],
            ],

            // D6 — Team Grid: module-fed from employees.is_public (M3 HR).
            'team_grid' => [
                'code' => 'D6',
                'label' => 'Team Grid',
                'category' => 'Social Proof & Trust',
                'component' => 'blocks.team-grid',
                'budget' => ['dom' => 18, 'kb' => 30],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                    'department' => ['label' => 'Department filter', 'type' => 'select', 'options' => ['all' => 'All public people', 'relocation' => 'Relocation', 'immigration' => 'Immigration', 'housing' => 'Housing', 'fleet' => 'Fleet', 'hr' => 'HR', 'finance' => 'Finance', 'ops' => 'Operations', 'tech' => 'Technology'], 'default' => 'all'],
                    'limit' => ['label' => 'Max shown', 'type' => 'text', 'max' => 2, 'default' => '8'],
                ],
            ],
            /*
             | ── Wave 4 (M4) — section-library §2/§4/§7: A5–A8, C6–C8, F1–F10 ──
             | Completes the enumerated catalog (doc header says "47"; the
             | per-category enumerations A1–A8/B1–B9/C1–C8/D1–D6/E1–E8/F1–F10
             | total 49 — the enumeration is the contract, per worklog D1).
             */

            // A5 — Bento Grid: mixed-size tiles of stat/image/CTA.
            'bento_grid' => [
                'code' => 'A5', 'label' => 'Bento Grid', 'category' => 'Layout & Structure',
                'component' => 'blocks.bento-grid', 'budget' => ['dom' => 24, 'kb' => 60],
                'fields' => [
                    'items' => ['label' => 'Tiles', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 60],
                        'text' => ['label' => 'Text', 'type' => 'textarea', 'max' => 160],
                        'size' => ['label' => 'Tile size', 'type' => 'select', 'options' => ['wide' => 'Wide (2×1)', 'tall' => 'Tall (1×2)', 'normal' => 'Normal'], 'default' => 'normal'],
                    ]],
                ],
            ],

            // A6 — Step Flow: 3–6 numbered steps with connector line.
            'step_flow' => [
                'code' => 'A6', 'label' => 'Step Flow', 'category' => 'Layout & Structure',
                'component' => 'blocks.step-flow', 'budget' => ['dom' => 16, 'kb' => 0],
                'fields' => [
                    'items' => ['label' => 'Steps', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'title' => ['label' => 'Step title', 'type' => 'text', 'required' => true, 'max' => 60],
                        'text' => ['label' => 'Step text', 'type' => 'textarea', 'max' => 200],
                    ]],
                ],
            ],

            // A7 — Marquee Strip: CSS-only scrolling ribbon, reduced-motion safe.
            'marquee_strip' => [
                'code' => 'A7', 'label' => 'Marquee Strip', 'category' => 'Layout & Structure',
                'component' => 'blocks.marquee-strip', 'budget' => ['dom' => 10, 'kb' => 0],
                'fields' => [
                    'items' => ['label' => 'Messages', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'text' => ['label' => 'Message', 'type' => 'text', 'required' => true, 'max' => 80],
                    ]],
                ],
            ],

            // A8 — Spacer/Divider: rhythm with ornament options.
            'spacer_divider' => [
                'code' => 'A8', 'label' => 'Spacer/Divider', 'category' => 'Layout & Structure',
                'component' => 'blocks.spacer-divider', 'budget' => ['dom' => 2, 'kb' => 0],
                'fields' => [
                    'height' => ['label' => 'Height', 'type' => 'select', 'options' => ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'], 'default' => 'md'],
                    'ornament' => ['label' => 'Ornament', 'type' => 'select', 'options' => ['none' => 'None', 'rule' => 'Rule line', 'quote' => 'Quote mark'], 'default' => 'rule'],
                ],
            ],

            // C6 — Image Duo/Trio: art-directed overlapping editorial spread.
            'image_duo' => [
                'code' => 'C6', 'label' => 'Image Duo/Trio', 'category' => 'Media & Visual',
                'component' => 'blocks.image-duo', 'budget' => ['dom' => 10, 'kb' => 160],
                'fields' => [
                    'items' => ['label' => 'Images (2–3)', 'type' => 'items', 'required' => true, 'item_fields' => [
                        'media_id' => ['label' => 'Media', 'type' => 'media', 'required' => true],
                        'caption' => ['label' => 'Caption', 'type' => 'text', 'max' => 120],
                    ]],
                ],
            ],

            // C7 — Map Block: click-to-load facade, address card, directions.
            'map_block' => [
                'code' => 'C7', 'label' => 'Map Block', 'category' => 'Media & Visual',
                'component' => 'blocks.map-block', 'budget' => ['dom' => 12, 'kb' => 30],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                    'address' => ['label' => 'Address (rendered on card)', 'type' => 'textarea', 'required' => true, 'max' => 300],
                    'pin_lat' => ['label' => 'Latitude', 'type' => 'text', 'max' => 20],
                    'pin_lng' => ['label' => 'Longitude', 'type' => 'text', 'max' => 20],
                ],
            ],

            // C8 — Before/After Slider: drag handle + keyboard control.
            'before_after' => [
                'code' => 'C8', 'label' => 'Before/After Slider', 'category' => 'Media & Visual',
                'component' => 'blocks.before-after', 'budget' => ['dom' => 12, 'kb' => 160],
                'fields' => [
                    'label_before' => ['label' => 'Before label', 'type' => 'text', 'max' => 20, 'default' => 'Before'],
                    'label_after' => ['label' => 'After label', 'type' => 'text', 'max' => 20, 'default' => 'After'],
                    'caption' => ['label' => 'Caption', 'type' => 'text', 'max' => 140],
                ],
            ],

            // F1 — Services Grid: auto from the catalog (module-fed).
            'services_grid' => [
                'code' => 'F1', 'label' => 'Services Grid', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.services-grid', 'budget' => ['dom' => 20, 'kb' => 0],
                'fields' => [
                    'family' => ['label' => 'Family filter', 'type' => 'select', 'options' => ['all' => 'All services', 'employee-mobility' => 'Employee mobility', 'business-mobility' => 'Business mobility', 'standalone' => 'Standalone'], 'default' => 'all'],
                    'limit' => ['label' => 'Max shown', 'type' => 'text', 'max' => 2, 'default' => '9'],
                ],
            ],

            // F2 — Service Detail Accordion: per-service scope accordions.
            'service_accordion' => [
                'code' => 'F2', 'label' => 'Service Detail Accordion', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.service-accordion', 'budget' => ['dom' => 18, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                ],
            ],

            // F3 — Housing Inventory Grid: filters + verified badges.
            'housing_grid' => [
                'code' => 'F3', 'label' => 'Housing Grid', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.housing-grid', 'budget' => ['dom' => 24, 'kb' => 60],
                'fields' => [
                    'city_id' => ['label' => 'City filter (optional)', 'type' => 'text', 'max' => 40],
                    'limit' => ['label' => 'Max shown', 'type' => 'text', 'max' => 2, 'default' => '6'],
                ],
            ],

            // F4 — City Coverage Strip: hub cities with hover cards.
            'city_strip' => [
                'code' => 'F4', 'label' => 'City Coverage Strip', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.city-strip', 'budget' => ['dom' => 18, 'kb' => 30],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                    'limit' => ['label' => 'Max cities', 'type' => 'text', 'max' => 2, 'default' => '7'],
                ],
            ],

            // F5 — Posts Feed: module-fed editorial cards.
            'posts_feed' => [
                'code' => 'F5', 'label' => 'Posts Feed', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.posts-feed', 'budget' => ['dom' => 18, 'kb' => 0],
                'fields' => [
                    'type' => ['label' => 'Type filter', 'type' => 'select', 'options' => ['all' => 'All', 'blog' => 'Blog', 'news' => 'News'], 'default' => 'all'],
                    'limit' => ['label' => 'Max shown', 'type' => 'text', 'max' => 2, 'default' => '3'],
                ],
            ],

            // F6 — Category Cloud: category cards with counts + intros.
            'category_cloud' => [
                'code' => 'F6', 'label' => 'Category Cloud', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.category-cloud', 'budget' => ['dom' => 14, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                ],
            ],

            // F7 — Job Listings: module-fed open roles.
            'job_listings' => [
                'code' => 'F7', 'label' => 'Job Listings', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.job-listings', 'budget' => ['dom' => 16, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                    'department' => ['label' => 'Department filter', 'type' => 'select', 'options' => ['all' => 'All departments', 'relocation' => 'Relocation', 'immigration' => 'Immigration', 'housing' => 'Housing', 'fleet' => 'Fleet', 'tech' => 'Technology'], 'default' => 'all'],
                ],
            ],

            // F8 — Leadership Grid: is_public employees (06-hr §3).
            'leadership_grid' => [
                'code' => 'F8', 'label' => 'Leadership Grid', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.leadership-grid', 'budget' => ['dom' => 18, 'kb' => 30],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                    'limit' => ['label' => 'Max shown', 'type' => 'text', 'max' => 2, 'default' => '8'],
                ],
            ],

            // F9 — Ventures Strip: on-domain service-line cross-links.
            'ventures_strip' => [
                'code' => 'F9', 'label' => 'Ventures Strip', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.ventures-strip', 'budget' => ['dom' => 12, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                ],
            ],

            // F10 — Search Widget: inline grouped-preview search.
            'search_widget' => [
                'code' => 'F10', 'label' => 'Search Widget', 'category' => 'Interactive & Dynamic',
                'component' => 'blocks.search-widget', 'budget' => ['dom' => 8, 'kb' => 0],
                'fields' => [
                    'heading' => ['label' => 'Heading', 'type' => 'text', 'max' => 100],
                ],
            ],
        ];
    }
}
