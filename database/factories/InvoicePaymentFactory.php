<?php

namespace Database\Factories;

use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\InvoicePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Invoice payment factory (schema §9) — audit row, integer paise. */
class InvoicePaymentFactory extends Factory
{
    protected $model = InvoicePayment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'method' => PaymentMethod::Bank,
            'amount' => 1_000_00,
            'paid_at' => now()->format('Y-m-d'),
            'reference' => 'UTR'.fake()->numerify('##########'),
            'recorded_by' => null,
        ];
    }

    public function upi(): static
    {
        return $this->state(fn (): array => ['method' => PaymentMethod::Upi]);
    }
}
