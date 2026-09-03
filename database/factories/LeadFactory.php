<?php

namespace Database\Factories;

use App\Modules\Leads\Enums\LeadSource;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Enums\LeadType;
use App\Modules\Leads\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Lead factory (money-path tests). */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'source' => LeadSource::Contact,
            'type' => LeadType::Enquiry,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+91 98'.$this->faker->numerify('#######'),
            'company' => null,
            'message' => 'We are moving to Gurugram next quarter and need help with housing.',
            'locale' => 'en',
            'status' => LeadStatus::New,
            'score' => 40,
            'idempotency_key' => (string) Str::ulid(),
            'consent_at' => now(),
            'consent_version' => '2026-01',
            'sla_due_at' => now()->addHours(2),
        ];
    }

    public function quote(): static
    {
        return $this->state(fn (): array => [
            'source' => LeadSource::ServicePage,
            'type' => LeadType::QuoteRequest,
            'company' => 'Acme Technologies Pvt. Ltd.',
            'sla_due_at' => now()->addHours(4),
        ]);
    }

    public function breached(): static
    {
        return $this->state(fn (): array => [
            'sla_due_at' => now()->subHours(3),
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (): array => [
            'status' => LeadStatus::Lost,
            'lost_reason' => 'budget',
            'first_response_at' => now()->subHours(5),
        ]);
    }
}
