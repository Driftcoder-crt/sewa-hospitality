<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Cities\Models\City;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Enums\MoveStage;
use App\Modules\Portal\Enums\MoveStatus;
use App\Modules\Portal\Models\PortalMove;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Portal move factory (03-database-schema.md §8). The reference is
 * NOT generated here — allocation must go through the locked
 * SequentialNumbering path; tests that need a unique reference pass
 * one explicitly (or call MoveReferenceGenerator).
 */
class PortalMoveFactory extends Factory
{
    protected $model = PortalMove::class;

    public function definition(): array
    {
        return [
            'reference' => 'SEWA-M-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'organization_id' => Organization::factory(),
            'employee_user_id' => User::factory(),
            'assignee_name' => fake()->name(),
            'assignee_email' => fake()->safeEmail(),
            'origin_city' => fake()->city(),
            'destination_city_id' => City::factory(),
            'move_date' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'stage' => MoveStage::InProgress,
            'status' => MoveStatus::Active,
            'summary' => null,
            'service_ids' => [],
            'timeline' => [],
        ];
    }

    public function intake(): static
    {
        return $this->state(fn (): array => ['stage' => MoveStage::Intake]);
    }

    public function complete(): static
    {
        return $this->state(fn (): array => ['stage' => MoveStage::Complete]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['stage' => MoveStage::Closed]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => MoveStatus::Cancelled]);
    }
}
