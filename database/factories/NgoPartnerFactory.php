<?php

namespace Database\Factories;

use App\Modules\Csr\Models\NgoPartner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** NGO partner factory (CSR tests). */
class NgoPartnerFactory extends Factory
{
    protected $model = NgoPartner::class;

    public function definition(): array
    {
        $name = 'Hope Foundation '.$this->faker->unique()->numberBetween(10, 99);

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'website' => 'https://example.org/hope',
            'claim' => '600+ women trained',
            'claim_as_of' => 'Aug 2026',
            'status' => 'active',
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => 'archived']);
    }
}
