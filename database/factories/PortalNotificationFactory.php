<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Portal\Models\PortalNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Portal notification factory (04 doc §3). */
class PortalNotificationFactory extends Factory
{
    protected $model = PortalNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(6),
            'body' => fake()->optional()->sentence(),
            'url' => '/moves',
            'kind' => fake()->randomElement(['stage', 'document', 'message', 'invoice', 'checklist', 'general']),
            'read_at' => null,
            'created_at' => now(),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }
}
