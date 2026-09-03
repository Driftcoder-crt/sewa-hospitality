<?php

namespace App\Modules\Cms\Services;

use App\Modules\Cms\Models\Redirect;
use Illuminate\Support\Facades\Cache;

/**
 * Redirect lookup + normalization (04-modules/01-cms.md §4.5): the
 * fallback route consults a cached map; a hit increments the counter
 * and serves 301/302. Map is flushed on every redirect write.
 */
final class RedirectService
{
    public const CACHE_KEY = 'cms.redirects.map';

    /**
     * Normalize a path to the canonical redirect key: leading slash,
     * lowercase, no trailing slash (except root), query string dropped
     * (the `from` is a path; matching ignores ?query per contract).
     */
    public static function normalize(string $path): string
    {
        $path = trim(parse_url($path, PHP_URL_PATH) ?? $path);
        $path = str_starts_with($path, '/') ? $path : '/'.$path;
        $path = rtrim($path, '/');
        $path = mb_strtolower($path);

        return $path === '' ? '/' : $path;
    }

    /** @return array<string, array{to: string, code: int, id: string}> */
    public static function map(): array
    {
        /** @var array<string, array{to: string, code: int, id: string}> $map */
        $map = Cache::rememberForever(self::CACHE_KEY, fn (): array => Redirect::query()
            ->where('active', true)
            ->get(['id', 'from', 'to', 'code'])
            ->mapWithKeys(fn (Redirect $r): array => [
                $r->from => ['to' => $r->to, 'code' => $r->code->value, 'id' => $r->getKey()],
            ])
            ->all());

        return $map;
    }

    public static function lookup(string $path): ?Redirect
    {
        $entry = self::map()[self::normalize($path)] ?? null;
        if (! $entry) {
            return null;
        }

        return Redirect::query()->find($entry['id']);
    }

    public static function flushMap(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
