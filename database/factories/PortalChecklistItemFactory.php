<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Portal\Enums\ChecklistStatus;
use App\Modules\Portal\Models\PortalChecklistItem;
use App\Modules\Portal\Models\PortalMove;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Portal checklist item factory (schema §8). */
class PortalChecklistItemFactory extends Factory
{
    protected $model = PortalChecklistItem::class;

    public function definition(): array
    {
        return [
            'move_record_id' => PortalMove::factory(),
            'title' => fake()->randomElement([
                'Upload passport copy',
                'FRRO registration appointment',
                'School enrollment visit',
                'Lease signing',
                'Shipment customs clearance',
            ]),
            'detail' => fake()->optional()->sentence(),
            'due_at' => fake()->dateTimeBetween('-1 week', '+3 weeks'),
            'done_at' => null,
            'done_by' => null,
            'sort' => fake()->numberBetween(0, 20),
            'status' => ChecklistStatus::Pending,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (): array => [
            'status' => ChecklistStatus::Done,
            'done_at' => now(),
            'done_by' => User::factory(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => ['due_at' => now()->subDays(3)]);
    }
}
