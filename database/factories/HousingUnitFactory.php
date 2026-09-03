<?php

namespace Database\Factories;

use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\HousingUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Housing unit factory (04-modules/10-cities-content.md §2): honest
 * from-rates, tier/type/bedrooms filters, optional verification via
 * ->verified() state. Inventory is NEVER production-seeded (contract);
 * factories exist for the test matrix only.
 */
class HousingUnitFactory extends Factory
{
    protected $model = HousingUnit::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'type' => 'serviced-apartment',
            'name' => 'The '.$this->faker->unique()->lastName().' Residence',
            'locality' => $this->faker->citySuffix(),
            'area' => null,
            'bedrooms' => 2,
            'tier' => 'professional',
            'status' => 'draft',
            'from_rate' => 65000,
            'rate_unit' => 'month',
            'amenities' => ['Wi-Fi', 'Housekeeping', 'Air conditioning'],
            'published' => false,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (): array => ['verified_at' => now()]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['published' => true, 'status' => 'published']);
    }

    public function executive(): static
    {
        return $this->state(fn (): array => ['tier' => 'executive', 'from_rate' => 120000]);
    }
}
