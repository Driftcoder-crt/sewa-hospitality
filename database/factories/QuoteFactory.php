<?php

namespace Database\Factories;

use App\Modules\Billing\Enums\QuoteStatus;
use App\Modules\Billing\Models\Quote;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Quote factory (schema §9). Amounts are INTEGER PAISE — fixtures use
 * clean round paise so any float leakage shows up as a failing assert.
 * `number` is NOT allocated through SequentialNumbering here (the
 * locked path belongs to the service layer); tests pass or allocate it.
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $lines = [
            [
                'description' => 'Home search — serviced apartment shortlist',
                'qty' => 1,
                'rate' => 2_500_00, // ₹2,500.00 in paise
                'tax_class' => 18,
                'amount' => 2_500_00,
            ],
            [
                'description' => 'City orientation drive',
                'qty' => 2,
                'rate' => 850_00,
                'tax_class' => 18,
                'amount' => 1_700_00,
            ],
        ];

        return [
            'number' => 'SEWA-Q-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'organization_id' => Organization::factory(),
            'move_record_id' => null,
            'lead_id' => null,
            'status' => QuoteStatus::Draft,
            'lines' => $lines,
            'total' => array_sum(array_column($lines, 'amount')),
            'currency' => 'INR',
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
            'sent_at' => null,
            'accepted_at' => null,
            'token' => null,
            'version' => 1,
            'created_by' => null,
            'notes' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
            'token' => bin2hex(random_bytes(24)),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'status' => QuoteStatus::Accepted,
            'sent_at' => now()->subDays(2),
            'accepted_at' => now()->subDay(),
            'token' => bin2hex(random_bytes(24)),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => QuoteStatus::Sent,
            'sent_at' => now()->subDays(45),
            'valid_until' => now()->subDay()->format('Y-m-d'),
            'token' => bin2hex(random_bytes(24)),
        ]);
    }
}
