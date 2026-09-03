<?php

namespace Database\Factories;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Organization factory (03-technical-specs/03-database-schema.md §1).
 * The explicit $model is required: the model lives in a module
 * namespace, so Laravel's default factory→model guess
 * (App\Models\Organization) would not resolve.
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'industry' => fake()->randomElement([
                'Information Technology',
                'Manufacturing',
                'Pharmaceuticals',
                'Financial Services',
                'Consulting',
                'E-Commerce',
            ]),
            'status' => 'active',
            // Billing addresses are real client data — fixtures never
            // invent one; tests that need it set it explicitly.
            'billing_address' => null,
        ];
    }
}
