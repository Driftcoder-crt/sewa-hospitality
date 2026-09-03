<?php

namespace App\Support\Pdf;

/**
 * Minimal, dependency-free PDF 1.4 writer (12-billing-finance §3/§6).
 *
 * RECORDED DEVIATION (least-deviation rule): the spec assumed a PDF
 * binary or SDK would be available on the host. Hostinger shared
 * hosting has neither, and the composer allowlist is locked — so the
 * platform generates its invoice documents natively: PDF 1.4 with the
 * base-14 fonts (Helvetica/Helvetica-Bold, WinAnsi), filled rects for
 * the brand band and table stripes, RGB color ops. No images, no
 * compression dependencies — output is a plain string that stores to
 * the private disk and attaches to mail like any other file.
 *
 * Scope discipline: this writer serves STRUCTURED DOCUMENTS (invoices,
 * quotes) — headings, key-value rows, tables, footers. It is not a
 * general layout engine and must not grow into one.
 *
 * Coordinates: top-left origin (Y grows downward) via fromTop() — page
 * content is written top-down like HTML, not in raw PDF bottom-up space.
 */
final class PdfBuilder
{
    /** A4 portrait in points. */
    public const WIDTH = 595.28;

    public const HEIGHT = 841.89;

    /** @var list<string> rendered content stream per page */
    private array $pages = [];

    /** @var list<string> current page ops */
    private array $ops = [];

    public function __construct()
    {
        $this->addPage();
    }

    /** Start a new page (content written before this lands on the old one). */
    public function addPage(): self
    {
        $this->pages[] = implode("\n", $this->ops);
        $this->ops = [];

        return $this;
    }

    /** Filled rectangle (top-left anchor). */
    public function rect(float $x, float $yTop, float $w, float $h, array $rgb): self
    {
        [$r, $g, $b] = $this->color($rgb);
        $y = $this->fromTop($yTop + $h);

        $this->ops[] = sprintf('%s %s %s rg', $r, $g, $b);
        $this->ops[] = sprintf('%.2F %.2F %.2F %.2F re f', $x, $y, $w, $h);

        return $this;
    }

    /** Horizontal rule. */
    public function line(float $x1, float $yTop, float $x2, array $rgb, float $width = 0.7): self
    {
        [$r, $g, $b] = $this->color($rgb);
        $y = $this->fromTop($yTop);

        $this->ops[] = sprintf('%s %s %s RG', $r, $g, $b);
        $this->ops[] = sprintf('%.2F w', $width);
        $this->ops[] = sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $y, $x2, $y);

        return $this;
    }

    /**
     * One text run on one baseline. $size in points; $bold picks
     * Helvetica-Bold. Fluid: returns $this so builders chain
     * rect() → text() → line() → output(). measure() stays public
     * for callers that need a run's advance width (two-column rows).
     */
    public function text(float $x, float $yTop, string $text, float $size = 10, bool $bold = false, array $rgb = [0, 0, 0], bool $rightAlign = false): self
    {
        [$r, $g, $b] = $this->color($rgb);
        $escaped = $this->escape($text);
        $y = $this->fromTop($yTop + $size); // baseline sits below the top edge
        $font = $bold ? '/F2' : '/F1';
        $width = $this->measure($text, $size, $bold);

        if ($rightAlign) {
            $x -= $width;
        }

        $this->ops[] = sprintf(
            'BT %s %s %s rg %s %.2F Tf %.2F %.2F Td (%s) Tj ET',
            $r, $g, $b, $font, $size, $x, $y, $escaped,
        );

        return $this;
    }

    /** Approximate Helvetica advance width (core-font AFM averages). */
    public function measure(string $text, float $size, bool $bold = false): float
    {
        $wide = 'MWmw@';
        $narrow = " ijl.,:;'|!";
        $units = 0;

        foreach (mb_str_split($text) as $char) {
            $units += match (true) {
                str_contains($wide, $char) => 830,
                str_contains($narrow, $char) => 280,
                mb_strlen($char) > 1 => 550,
                ctype_upper($char) => 700,
                ctype_digit($char) => 556,
                default => 520,
            };
        }

        return $units * ($bold ? 1.03 : 1.0) * $size / 1000;
    }

    /** Build the complete PDF string. */
    public function output(): string
    {
        $this->pages[] = implode("\n", $this->ops);
        $this->ops = [];

        $pages = array_values(array_filter(
            $this->pages,
            fn (string $stream): bool => $stream !== '' || count($this->pages) === 1,
        ));

        $objects = [];

        // 1: catalog, 2: page tree, 3: F1, 4: F2 — then per page: content + page.
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $kids = [];
        $nextId = 5;

        foreach (array_keys($pages) as $index) {
            $contentId = $nextId;
            $pageId = $nextId + 1;
            $nextId += 2;
            $kids[] = $pageId.' 0 R';

            $objects[$contentId] = '<< /Length '.strlen($pages[$index])." >>\nstream\n".$pages[$index]."\nendstream";
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::WIDTH,
                self::HEIGHT,
                $contentId,
            );
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;

        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";

        foreach ($objects as $id => $body) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }

    /** Top-based Y → PDF bottom-based Y. */
    private function fromTop(float $yTop): float
    {
        return self::HEIGHT - $yTop;
    }

    /** @param  list<float>  $rgb  0–255 triple */
    private function color(array $rgb): array
    {
        [$r, $g, $b] = array_pad(array_slice($rgb, 0, 3), 3, 0);

        return [
            number_format(min(255, max(0, (float) $r)) / 255, 3, '.', ''),
            number_format(min(255, max(0, (float) $g)) / 255, 3, '.', ''),
            number_format(min(255, max(0, (float) $b)) / 255, 3, '.', ''),
        ];
    }

    /** WinAnsi-safe escaping (₹ and friends become ASCII — core fonts have no rupee glyph). */
    private function escape(string $text): string
    {
        $text = str_replace('₹', 'INR ', $text);
        $text = str_replace(["\u{00A0}", "\u{200B}"], ' ', $text);

        $map = [
            '—' => '-', '–' => '-', '’' => "'", '‘' => "'", '“' => '"', '”' => '"',
            '•' => '-', '…' => '...', '×' => 'x', '≈' => '~', '→' => '>',
        ];

        $text = strtr($text, $map);
        $text = iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text) ?: '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
