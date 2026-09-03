<?php

namespace Database\Factories;

use App\Modules\Leads\Enums\NewsletterStatus;
use App\Modules\Leads\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Newsletter subscriber factory (double-opt-in tests). */
class NewsletterSubscriberFactory extends Factory
{
    protected $model = NewsletterSubscriber::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'status' => NewsletterStatus::Pending,
            'token' => Str::random(48),
            'locale' => 'en',
            'source' => 'tests',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => NewsletterStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }
}
