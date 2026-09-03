<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Portal\Enums\SenderRole;
use App\Modules\Portal\Models\PortalMessage;
use App\Modules\Portal\Models\PortalThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Portal message factory (schema §8) — append-only rows. */
class PortalMessageFactory extends Factory
{
    protected $model = PortalMessage::class;

    public function definition(): array
    {
        return [
            'thread_id' => PortalThread::factory(),
            'sender_user_id' => User::factory(),
            'sender_role' => SenderRole::Client,
            'body' => fake()->sentence(12),
            'media_ids' => [],
            'read_at' => null,
            'created_at' => now(),
        ];
    }

    public function fromConsultant(): static
    {
        return $this->state(fn (): array => ['sender_role' => SenderRole::Consultant]);
    }

    public function system(): static
    {
        return $this->state(fn (): array => [
            'sender_role' => SenderRole::System,
            'sender_user_id' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }
}
