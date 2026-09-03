<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Enums\ThreadStatus;
use App\Modules\Portal\Models\PortalMove;
use App\Modules\Portal\Models\PortalThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Portal thread factory (schema §8). */
class PortalThreadFactory extends Factory
{
    protected $model = PortalThread::class;

    public function definition(): array
    {
        return [
            'move_record_id' => PortalMove::factory(),
            'organization_id' => function (array $attrs): string {
                $move = PortalMove::query()->find($attrs['move_record_id']);

                return $move?->organization_id ?? Organization::factory()->create()->getKey();
            },
            'subject' => 'Housing shortlist question',
            'status' => ThreadStatus::Open,
            'created_by' => User::factory(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['status' => ThreadStatus::Closed]);
    }
}
