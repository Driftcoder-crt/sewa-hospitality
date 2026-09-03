<?php

namespace App\Support\Cms;

/**
 * Minimal word-level diff (LCS) for revision rendering — no external
 * packages on the shared-hosting stack. Used by the page editor's
 * revision diff view; structural (per-block) diffs are computed in
 * RevisionManager on top of this for text fields.
 */
final class TextDiff
{
    /**
     * Word-level diff of two plain-text strings.
     *
     * @return list<array{op: 'same'|'add'|'del', text: string}>
     */
    public static function words(?string $from, ?string $to): array
    {
        $from = html_entity_decode(strip_tags((string) $from)) ?: '';
        $to = html_entity_decode(strip_tags((string) $to)) ?: '';

        $a = preg_split('/\s+/', trim($from)) ?: [];
        $b = preg_split('/\s+/', trim($to)) ?: [];
        $n = count($a);
        $m = count($b);

        if ($n === 0 && $m === 0) {
            return [];
        }

        // LCS table (guard size: cap inputs at ~2k words per side).
        if ($n > 2000 || $m > 2000) {
            return [
                ['op' => 'del', 'text' => implode(' ', $a)],
                ['op' => 'add', 'text' => implode(' ', $b)],
            ];
        }

        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $ops = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $ops[] = ['op' => 'same', 'text' => $a[$i]];
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $ops[] = ['op' => 'del', 'text' => $a[$i]];
                $i++;
            } else {
                $ops[] = ['op' => 'add', 'text' => $b[$j]];
                $j++;
            }
        }
        while ($i < $n) {
            $ops[] = ['op' => 'del', 'text' => $a[$i]];
            $i++;
        }
        while ($j < $m) {
            $ops[] = ['op' => 'add', 'text' => $b[$j]];
            $j++;
        }

        return self::coalesce($ops);
    }

    /** @param list<array{op: string, text: string}> $ops */
    private static function coalesce(array $ops): array
    {
        $out = [];
        foreach ($ops as $op) {
            $last = end($out);
            if ($last !== false && $last['op'] === $op['op']) {
                $out[array_key_last($out)]['text'] .= ' '.$op['text'];

                continue;
            }
            $out[] = ['op' => $op['op'], 'text' => $op['text']];
        }

        return $out;
    }
}
