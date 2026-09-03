<?php

namespace Database\Factories;

use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Invoice factory (schema §9). Integer paise everywhere; subtotal +
 * tax_breakdown are consistent with the fixture lines (18% class) so
 * paid/partial states assert cleanly.
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = 4_200_00; // ₹4,200.00
        $tax = (int) round($subtotal * 0.18); // 756_00

        return [
            'number' => 'SEWA-I-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'quote_id' => null,
            'organization_id' => Organization::factory(),
            'move_record_id' => null,
            'status' => InvoiceStatus::Draft,
            'lines' => [
                [
                    'description' => 'Home search — serviced apartment shortlist',
                    'qty' => 1,
                    'rate' => 2_500_00,
                    'tax_class' => 18,
                    'amount' => 2_500_00,
                ],
                [
                    'description' => 'Settling-in assistance',
                    'qty' => 1,
                    'rate' => 1_700_00,
                    'tax_class' => 18,
                    'amount' => 1_700_00,
                ],
            ],
            'subtotal' => $subtotal,
            'tax_breakdown' => ['18' => $tax],
            'total' => $subtotal + $tax,
            'currency' => 'INR',
            'due_at' => now()->addDays(15)->format('Y-m-d'),
            'sent_at' => null,
            'paid_at' => null,
            'reminders_sent' => 0,
            'last_reminder_at' => null,
            'void_reason' => null,
            'notes' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::Sent,
            'sent_at' => now()->subDays(30),
            'due_at' => now()->subDays(5)->format('Y-m-d'),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::Paid,
            'sent_at' => now()->subDays(20),
            'paid_at' => now()->subDays(2),
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn (): array => [
            'status' => InvoiceStatus::Void,
            'void_reason' => 'Duplicate of an earlier invoice.',
        ]);
    }
}
