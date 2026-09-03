<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Search query log row (03-technical-specs/08-search.md §3): anonymous
 * term + locale + hit count + zero-results flag. Zero-result terms
 * become editorial tickets in the city/content backlog (CitiesTable).
 */
class SearchQuery extends Model
{
    use HasUlids;

    protected $fillable = ['term', 'locale', 'hits', 'count', 'zero_results', 'last_seen_at'];

    protected function casts(): array
    {
        return [
            'zero_results' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /** Log (upsert) one anonymous search — shared-hosting safe. */
    public static function log(string $term, string $locale, int $hits): void
    {
        $term = mb_substr(trim($term), 0, 200);
        if ($term === '') {
            return;
        }

        $values = [
            'hits' => $hits,
            'zero_results' => $hits === 0,
            'last_seen_at' => now(),
        ];

        // A DB::raw increment is only valid inside an UPDATE statement —
        // in an INSERT values list it parses as an (unknown) column
        // reference, so the very first search of any term would fatal.
        // Branch instead: create with count=1, or update with the raw
        // increment. The unique (term, locale) index guards the pair.
        $existing = static::query()->where('term', $term)->where('locale', $locale)->first();

        if ($existing === null) {
            static::query()->create([
                'term' => $term,
                'locale' => $locale,
                'count' => 1,
                ...$values,
            ]);

            return;
        }

        static::query()->where('term', $term)->where('locale', $locale)
            ->update(['count' => DB::raw('count + 1'), ...$values]);
    }
}
