<?php

namespace Database\Factories;

use App\Modules\Blog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Category factory (blog archive tests). */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Relocation Guide to India', 'Immigration Explainers', 'City Guides', 'Housing Market Notes',
        ]).' '.$this->faker->unique()->numberBetween(10, 99);

        return ['slug' => Str::slug($name), 'name' => $name, 'sort' => 0];
    }
}
