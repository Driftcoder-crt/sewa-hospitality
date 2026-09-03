<?php

namespace App\Modules\Cities\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cities\Enums\HousingTier;
use App\Modules\Cities\Enums\HousingType;
use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\HousingUnit;
use App\Modules\I18n\Services\ContentVariants;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Cities + housing public surface (04-modules/10-cities-content.md §3):
 * /cities hub · /cities/{slug} money template · /housing browser
 * (filters: city × type × tier × bedrooms × price band) · /housing/{unit}
 * detail · /housing/verified (the published checklist behind the badge).
 */
class CityController extends Controller
{
    public function __construct(private readonly Factory $views) {}

    public function citiesHub(): View
    {
        // Localized listing: ja variants first; EN sources render where a
        // ja variant does not exist (fallback never HIDES content).
        $cities = ContentVariants::localizedList(City::query()->published())
            ->orderByDesc('is_hub')
            ->orderBy('name')
            ->get();

        return $this->views->make('cities.hub', ['cities' => $cities]);
    }

    public function city(string $slug): View
    {
        $city = ContentVariants::firstInLocale(
            City::query()->published()->where('slug', mb_strtolower($slug)),
        );

        if (! $city) {
            throw new NotFoundHttpException('City not found.');
        }

        // Coverage truth (§5): only services with a real pivot row.
        $coverage = $city->services()
            ->published()
            ->orderBy('sort')
            ->get(['services.id', 'services.slug', 'services.name', 'services.icon_svg_key', 'services.parent_id', 'city_services.note']);

        $units = HousingUnit::query()
            ->where('published', true)
            ->where('city_id', $city->getKey())
            ->orderByDesc('verified_at')
            ->orderBy('name')
            ->take(6)
            ->get();

        return $this->views->make('cities.city', [
            'city' => $city,
            'coverage' => $coverage,
            'units' => $units,
            'blocks' => $city->content_blocks ?? [],
            'leadIndex' => 0,
        ]);
    }

    public function housing(Request $request): View
    {
        $query = HousingUnit::query()
            ->where('published', true)
            ->with('city:id,slug,name');

        // Filter matrix (§8) — every filter optional, shareable params.
        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('slug', mb_strtolower($request->string('city'))));
        }
        if ($request->filled('type') && HousingType::tryFrom($request->string('type')) !== null) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('tier') && HousingTier::tryFrom($request->string('tier')) !== null) {
            $query->where('tier', $request->string('tier'));
        }
        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', (int) $request->input('bedrooms'));
        }
        // Price bands (honest ranges): 0-40k, 40-80k, 80k+, per month.
        $band = (string) $request->input('price', '');
        if ($band === '0-40000') {
            $query->where('rate_unit', 'month')->where('from_rate', '<=', 40000);
        } elseif ($band === '40000-80000') {
            $query->where('rate_unit', 'month')->whereBetween('from_rate', [40000, 80000]);
        } elseif ($band === '80000+') {
            $query->where('rate_unit', 'month')->where('from_rate', '>', 80000);
        }

        $units = $query->orderByDesc('verified_at')->orderBy('name')->paginate(12)->withQueryString();

        $hubCities = ContentVariants::localizedList(City::query()->published())
            ->orderByDesc('is_hub')->orderBy('name')
            ->get(['id', 'slug', 'name']);

        return $this->views->make('housing.index', [
            'units' => $units,
            'cities' => $hubCities,
            'types' => HousingType::options(),
            'tiers' => HousingTier::options(),
            'filters' => [
                'city' => (string) $request->input('city', ''),
                'type' => (string) $request->input('type', ''),
                'tier' => (string) $request->input('tier', ''),
                'bedrooms' => (string) $request->input('bedrooms', ''),
                'price' => $band,
            ],
        ]);
    }

    public function unit(string $id): View
    {
        $unit = HousingUnit::query()
            ->where('published', true)
            ->with('city:id,slug,name')
            ->findOrFail($id);

        return $this->views->make('housing.unit', ['unit' => $unit]);
    }

    public function verified(): View
    {
        return $this->views->make('housing.verified');
    }
}
