<?php

namespace App\Support\Cms;

use DOMDocument;
use DOMElement;

/**
 * HTML sanitizer for CMS rich-text fields (B1/B2/B4 bodies).
 *
 * Contract (04-modules/01-cms.md §2 + §5, ui-components doc):
 * - whitelist-based (p, headings h2–h4, lists, links, images, tables,
 *   blockquote, code, hr, br, figure/figcaption, strong/em);
 * - strips <script>/<style>/<iframe>/event handlers/javascript: URLs —
 *   always;
 * - enforces the heading ladder: any h1 becomes h2 (single-H1 rule is
 *   template-owned by the lead hero);
 * - preserves safe href/target and img src/alt/width/height/loading.
 *
 * Implemented on DOMDocument — zero new dependencies, safe output by
 * construction. If parsing fails, the empty string is returned (fail
 * closed); the editor shows a visible error rather than raw HTML.
 */
final class HtmlSanitizer
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's',
        'a', 'ul', 'ol', 'li', 'blockquote', 'code', 'pre',
        'figure', 'figcaption', 'img', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = self::parse($html);
        if ($document === null) {
            return '';
        }

        $root = $document->documentElement;
        if ($root === null) {
            return '';
        }

        self::walk($root);

        $inner = '';
        foreach ([...$root->childNodes] as $child) {
            $inner .= $document->saveHTML($child);
        }

        return trim($inner);
    }

    private static function parse(string $html): ?DOMDocument
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $ok = $document->loadHTML(
            '<?xml encoding="utf-8"?><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR,
        );
        libxml_clear_errors();

        return $ok ? $document : null;
    }

    private static function walk(DOMElement $element): void
    {
        foreach ([...$element->childNodes] as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            // Heading ladder FIRST: h1 is not whitelisted, so the unknown-
            // element branch below would unwrap it to bare text before the
            // rename could ever run. Demote, then let the normal path treat
            // it as an allowed h2.
            if (mb_strtolower($child->tagName) === 'h1') {
                $child = self::renameTag($child, 'h2');
            }

            // Unknown elements unwrap their text children into the parent
            // (formatting scaffolding like <span>); script/style/iframe
            // drop entirely — unwrapping them would spill their CONTENTS
            // (JS source) into the page as text.
            $tag = mb_strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form'], true)) {
                $child->remove();

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $text = trim($child->textContent);
                if ($text !== '') {
                    $child->replaceWith(...$child->childNodes);
                } else {
                    $child->remove();
                }

                continue;
            }

            self::cleanAttributes($child);

            self::walk($child);
        }
    }

    private static function cleanAttributes(DOMElement $element): void
    {
        foreach ([...$element->attributes] as $attribute) {
            $name = mb_strtolower($attribute->name);
            $value = $attribute->value;

            // Every attribute is dropped unless explicitly allowed.
            if (! self::attributeAllowed($element, $name)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            // URLs: block javascript:/vbscript:/data: (except data: images).
            if (in_array($name, ['href', 'src'], true)) {
                $trimmed = mb_strtolower(trim(preg_replace('/[\s\x00-\x1F]/', '', $value) ?? ''));
                if (preg_match('/^(javascript|vbscript):/i', $trimmed)
                    || (str_starts_with($trimmed, 'data:') && ! str_starts_with($trimmed, 'data:image/'))) {
                    $element->removeAttribute($attribute->name);

                    continue;
                }
            }

            if ($name === 'target') {
                $element->setAttribute('rel', 'noopener');
            }
        }
    }

    private static function attributeAllowed(DOMElement $element, string $name): bool
    {
        return match (mb_strtolower($element->tagName)) {
            'a' => in_array($name, ['href', 'title', 'target'], true),
            'img' => in_array($name, ['src', 'alt', 'width', 'height', 'loading'], true),
            'td', 'th' => in_array($name, ['colspan', 'rowspan'], true),
            'ol' => in_array($name, ['start'], true),
            default => false, // tags carry no attributes in our whitelist
        };
    }

    private static function renameTag(DOMElement $element, string $newTag): DOMElement
    {
        $replacement = $element->ownerDocument->createElement($newTag);
        while ($element->firstChild !== null) {
            $replacement->appendChild($element->firstChild);
        }
        $element->parentNode?->replaceChild($replacement, $element);

        return $replacement;
    }
}
