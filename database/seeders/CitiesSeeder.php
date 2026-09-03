<?php

namespace Database\Seeders;

use App\Modules\Cities\Enums\CityStatus;
use App\Modules\Cities\Models\City;
use App\Modules\Services\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Cities seed (06-content-seo/03-city-content-program.md W1): the
 * seven launch hub cities with honest, dated baseline copy — no
 * invented rates, no invented population stats. Coverage rows link
 * the core services per business reality (coverage truth rule).
 * Housing inventory is NOT seeded — production data comes from ops;
 * /housing renders its zero state honestly.
 */
class CitiesSeeder extends Seeder
{
    /** W1 hub cities (city content program §W1). */
    private const HUBS = [
        ['gurugram', 'Gurugram', 'Haryana', 28.4595, 77.0266, 'Corporate India\'s hub city — Fortune 500 offices, expat-heavy neighborhoods and the DLF corridor.'],
        ['new-delhi', 'New Delhi', 'Delhi', 28.6139, 77.2090, 'The capital: embassies, government liaison and the widest choice of international schooling.'],
        ['mumbai', 'Mumbai', 'Maharashtra', 19.0760, 72.8777, 'Finance and entertainment capital — premium inventory, sea-facing leases and fast timelines.'],
        ['bengaluru', 'Bengaluru', 'Karnataka', 12.9716, 77.5946, 'India\'s tech capital — international schools, tech parks and the largest inbound cohort.'],
        ['pune', 'Pune', 'Maharashtra', 18.5204, 73.8567, 'Manufacturing and IT corridor with a quieter pace — strong value on corporate housing.'],
        ['hyderabad', 'Hyderabad', 'Telangana', 17.3850, 78.4867, 'HITEC City pharma and tech boom, generous apartments and short commutes.'],
        ['chennai', 'Chennai', 'Tamil Nadu', 13.0827, 80.2707, 'Manufacturing and automotive hub with a deep expat network and coastal living.'],
    ];

    public function run(): void
    {
        $cityIds = [];

        foreach (self::HUBS as $i => [$slug, $name, $state, $lat, $lng, $description]) {
            $cityIds[$slug] = City::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'state' => $state,
                    'country' => 'IN',
                    'lat' => $lat,
                    'lng' => $lng,
                    'is_hub' => true,
                    'description' => $description,
                    'status' => CityStatus::Published->value,
                    'meta_title' => 'Relocation services in '.$name,
                    'meta_description' => 'Relocating to '.$name.'? Housing, immigration and settling-in support from Sewa Hospitality — honest, dated local information. (As of '.now()->format('M Y').'.)',
                    'content_blocks' => [
                        ['type' => 'feature_grid', 'data' => [
                            'columns' => '3',
                            'style' => 'border',
                            'items' => [
                                ['title' => 'Housing', 'text' => 'Verified serviced apartments and corporate housing across the main corridors.', 'icon' => 'building', 'url' => '/housing?city='.$slug],
                                ['title' => 'Settling-in', 'text' => 'School runs, utilities, drivers and the first-week checklist — handled.', 'icon' => 'home', 'url' => ''],
                                ['title' => 'Immigration', 'text' => 'FRRO registration and visa timelines with a dated compliance plan.', 'icon' => 'file-text', 'url' => '/services/immigration/inbound-immigration'],
                            ],
                        ]],
                        ['type' => 'cta_band', 'data' => [
                            'headline' => 'Moving to '.$name.'?',
                            'copy' => 'A named consultant plans the whole move — housing, immigration, settling-in.',
                            'theme' => 'brand',
                            'layout' => 'centered',
                            'ctas' => [['label' => 'Talk to a consultant', 'url' => '/contact', 'variant' => 'primary']],
                        ]],
                    ],
                ],
            )->getKey();
        }

        // Coverage truth (cities doc §5): connect core services per city.
        $bySlug = Service::query()->whereIn('slug', [
            'relocation', 'immigration', 'serviced-apartments', 'moving',
            'corporate-housing', 'fleet', 'travel',
        ])->get()->keyBy('slug');

        $coverageMap = [
            'relocation' => array_keys($cityIds),
            'immigration' => array_keys($cityIds),
            'serviced-apartments' => ['gurugram', 'new-delhi', 'mumbai', 'bengaluru', 'pune', 'hyderabad', 'chennai'],
            'moving' => array_keys($cityIds),
            'corporate-housing' => ['gurugram', 'new-delhi', 'mumbai', 'bengaluru', 'pune', 'hyderabad', 'chennai'],
            'fleet' => ['gurugram', 'new-delhi', 'mumbai', 'bengaluru', 'chennai'],
            'travel' => ['gurugram', 'new-delhi', 'mumbai', 'bengaluru'],
        ];

        foreach ($coverageMap as $serviceSlug => $citySlugs) {
            $service = $bySlug->get($serviceSlug);
            if (! $service) {
                continue;
            }

            foreach ($citySlugs as $citySlug) {
                $service->cities()->syncWithoutDetaching([
                    $cityIds[$citySlug] => ['note' => $serviceSlug === 'fleet' ? 'On-demand fleet desk' : null],
                ]);
            }
        }

        cache()->forget('services.tree');
    }
}
