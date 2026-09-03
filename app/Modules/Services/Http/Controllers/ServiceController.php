<?php

namespace App\Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cities\Models\City;
use App\Modules\Cms\Enums\PageStatus;
use App\Modules\I18n\Services\ContentVariants;
use App\Modules\Services\Models\Service;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public service surface (04-modules/02-services-module.md §3):
 *   /services                     → hub (11 cards, auto-composed)
 *   /services/{family}            → family page (intro + child cards)
 *   /services/{parent}/{leaf}     → leaf (blocks + coverage + related)
 * URL slugs locked to 03-service-catalog.md; family/immigration hubs
 * auto-compose from published children — never duplicated copy.
 */
class ServiceController extends Controller
{
    public function __construct(private readonly Factory $views) {}

    public function hub(): View
    {
        $tree = $this->tree();

        return $this->views->make('services.hub', [
            'services' => $tree['leaves'],
            'families' => $tree['families'],
        ]);
    }

    public function family(string $slug): View
    {
        // Family hubs + the immigration hub are parent-less services.
        $service = ContentVariants::firstInLocale(
            Service::query()->published()->whereNull('parent_id')->where('slug', mb_strtolower($slug)),
        );

        if (! $service) {
            throw new NotFoundHttpException('Service not found.');
        }

        // Cards render publicPath() which resolves ->parent — eager it.
        $children = $service->children()->published()->with('parent')->get();
        $coverage = $this->coverageFor($service);

        return $this->views->make('services.family', [
            'service' => $service,
            'children' => $children,
            'coverage' => $coverage,
            'blocks' => $service->content_blocks ?? [],
            'leadIndex' => 0,
        ]);
    }

    public function leaf(string $parentSlug, string $slug): View
    {
        $service = ContentVariants::firstInLocale(
            Service::query()
                ->published()
                ->where('slug', mb_strtolower($slug))
                ->whereHas('parent', function ($query) use ($parentSlug): void {
                    $query->where('slug', mb_strtolower($parentSlug))->published();
                }),
        );

        if (! $service) {
            throw new NotFoundHttpException('Service not found.');
        }

        $coverage = $this->coverageFor($service);
        // Cards render publicPath() which resolves ->parent — eager it.
        $related = $service->related()->published()->with('parent')->take(3)->get();

        return $this->views->make('services.leaf', [
            'service' => $service,
            'coverage' => $coverage,
            'related' => $related,
            'blocks' => $service->content_blocks ?? [],
            'leadIndex' => 0,
        ]);
    }

    /** Cached catalog tree (services.hub + family pages, 60s per cms §6). Locale-keyed — never poison across locales. */
    private function tree(): array
    {
        return Cache::remember('services.tree.'.app()->getLocale(), 60, function (): array {
            // Leaves render publicPath() which resolves ->parent — eager
            // it once for the whole tree (no lazy-load under the dev guard).
            $published = ContentVariants::localizedList(
                Service::query()->published()->with('parent'),
            )
                ->orderBy('sort')
                ->orderBy('name')
                ->get();

            $families = $published
                ->whereNull('parent_id')
                ->mapWithKeys(fn (Service $service): array => [
                    $service->getKey() => [
                        'service' => $service,
                        'children' => $service->children()->published()->with('parent')->get(),
                    ],
                ]);

            $leaves = $published->filter(fn (Service $service): bool => $service->parent_id !== null);

            return ['families' => $families, 'leaves' => $leaves];
        });
    }

    /**
     * Coverage truth (cities doc §5): only cities with an actual
     * city_services row and a PUBLISHED city render in strips. Cached
     * 60s (services doc §6 — no N+1 on hubs).
     */
    private function coverageFor(Service $service)
    {
        return Cache::remember('services.coverage.'.$service->getKey().'.'.app()->getLocale(), 60, function () use ($service) {
            return $service->cities()
                ->where('cities.status', PageStatus::Published->value)
                ->orderBy('cities.is_hub', 'desc')
                ->orderBy('cities.name')
                ->get(['cities.id', 'cities.slug', 'cities.name', 'cities.is_hub', 'city_services.note']);
        });
    }
}
