<?php

namespace Database\Factories;

use App\Modules\Cities\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * City factory — test companion to CitiesSeeder (production data comes
 * only from the seeder, per the honest-content rule).
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => $this->faker->unique()->city(),
            'state' => $this->faker->state(),
            'country' => 'IN',
            'is_hub' => false,
            'status' => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => 'published',
            'description' => $this->faker->paragraph(),
            'meta_title' => 'Relocating to test city',
            'meta_description' => $this->faker->sentence(),
        ]);
    }

    public function hub(): static
    {
        return $this->state(fn (): array => ['is_hub' => true]);
    }
}
