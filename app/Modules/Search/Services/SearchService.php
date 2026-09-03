<?php

namespace App\Modules\Search\Services;

use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\HousingUnit;
use App\Modules\Cms\Models\Page;
use App\Modules\Search\Models\SearchQuery;
use App\Modules\Services\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Builder;
use Throwable;

/**
 * Site search (03-technical-specs/08-search.md): the Scout contract —
 * `Model::search($term)` with the DATABASE driver (MySQL FULLTEXT) in
 * production. Scout's sqlite/test path throws on whereFullText, so the
 * service falls back to the documented LIKE fallback transparently
 * (§2 "hybrid" note). Grouped hits for the /search tabs, cached
 * 10 min per query (shared-hosting CPU discipline), logged anonymously
 * into search_queries.
 */
class SearchService
{
    public const CACHE_TTL = 600;

    public const GROUPS = ['services', 'cities', 'housing', 'pages'];

    /**
     * Grouped results, capped per group.
     *
     * @return array{total: int, term: string, groups: array<string, array{label: string, count: int, hits: Collection}>}
     */
    public function search(string $term, string $locale = 'en', int $perGroup = 8): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return ['total' => 0, 'term' => $term, 'groups' => []];
        }

        $cacheKey = 'search.'.md5($term.'|'.$locale.'|'.$perGroup);

        $result = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($term, $perGroup): array {
            $groups = [];
            $total = 0;

            foreach ([
                'services' => ['label' => 'Services', 'hits' => $this->services($term, $perGroup)],
                'cities' => ['label' => 'City guides', 'hits' => $this->cities($term, $perGroup)],
                'housing' => ['label' => 'Housing', 'hits' => $this->housing($term, $perGroup)],
                'pages' => ['label' => 'Pages', 'hits' => $this->pages($term, $perGroup)],
            ] as $groupKey => $group) {
                $groups[$groupKey] = [
                    'label' => $group['label'],
                    'count' => $group['hits']->count(),
                    'hits' => $group['hits'],
                ];
                $total += $groups[$groupKey]['count'];
            }

            return ['total' => $total, 'groups' => $groups];
        });

        SearchQuery::log($term, $locale, $result['total']);

        return $result + ['term' => $term];
    }

    /** @return Collection<int, Service> */
    private function services(string $term, int $limit): Collection
    {
        // Hit URLs resolve ->parent via publicPath() — eager it for the
        // LIKE-fallback path too (no lazy load under the dev guard).
        $hits = $this->scout(Service::query()->with('parent'), $term, $limit);

        return $hits->filter(fn ($model): bool => $model->status->isPublic())
            ->take($limit)
            ->values();
    }

    /** @return Collection<int, City> */
    private function cities(string $term, int $limit): Collection
    {
        $hits = $this->scout(City::query(), $term, $limit);

        return $hits->filter(fn ($model): bool => $model->status->isPublic())
            ->take($limit)
            ->values();
    }

    /** @return Collection<int, HousingUnit> */
    private function housing(string $term, int $limit): Collection
    {
        $hits = $this->scout(HousingUnit::query()->with('city:id,name,slug'), $term, $limit);

        return $hits->filter(fn ($model): bool => $model->published)
            ->take($limit)
            ->values();
    }

    /** @return Collection<int, Page> */
    private function pages(string $term, int $limit): Collection
    {
        $hits = $this->scout(Page::query(), $term, $limit);

        return $hits->filter(fn ($model): bool => $model->status->isPublic())
            ->take($limit)
            ->values();
    }

    /**
     * Scout first (contract), LIKE fallback on throw (sqlite/tests).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return Collection<int, mixed>
     */
    private function scout($query, string $term, int $limit): Collection
    {
        try {
            $model = $query->getModel();

            /** @var Builder $builder */
            $builder = $model->search($term);

            return $builder->take($limit)->get();
        } catch (Throwable) {
            $term = mb_strtolower($term);

            return $query
                ->get()
                ->filter(fn ($model): bool => $this->matches($term, $model))
                ->take($limit)
                ->values();
        }
    }

    /** LIKE-fallback matcher over each model's searchable fields. */
    private function matches(string $needle, $model): bool
    {
        foreach ((array) $model->toSearchableArray() as $value) {
            if (is_string($value) && $value !== '' && str_contains(mb_strtolower(strip_tags($value)), $needle)) {
                return true;
            }
        }

        return false;
    }
}
