<?php

namespace Database\Factories;

use App\Modules\Testimonials\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Testimonial factory (reviews tests). */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'client_name' => $this->faker->name(),
            'body' => 'The team found us a verified home in ten days and handled every FRRO step.',
            'source' => 'direct',
            'status' => 'published',
            'published_at' => now(),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (): array => ['verified_at' => now()]);
    }
}
