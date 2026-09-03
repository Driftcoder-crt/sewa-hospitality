<?php

namespace Database\Factories;

use App\Modules\Ai\Models\AiInvocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** AI invocation ledger factory (03-database-schema §10). */
class AiInvocationFactory extends Factory
{
    protected $model = AiInvocation::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'feature' => fake()->randomElement(['translate', 'enrich', 'summarize', 'draft', 'score']),
            'provider' => 'tokenrouter',
            'model' => 'z-ai/glm-5.3-free',
            'tokens_in' => fake()->numberBetween(50, 1200),
            'tokens_out' => fake()->numberBetween(50, 1200),
            'cost_estimate' => 0,
            'status' => 'ok',
            'latency_ms' => fake()->numberBetween(200, 4000),
            'meta' => null,
            'created_at' => now(),
        ];
    }

    public function feature(string $feature): static
    {
        return $this->state(fn (): array => ['feature' => $feature]);
    }

    public function fallback(): static
    {
        return $this->state(fn (): array => ['status' => 'fallback']);
    }

    public function error(): static
    {
        return $this->state(fn (): array => ['status' => 'error']);
    }

    public function lastMonth(): static
    {
        return $this->state(fn (): array => ['created_at' => now()->subMonth()->startOfMonth()->addDays(3)]);
    }
}
