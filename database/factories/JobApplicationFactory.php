<?php

namespace Database\Factories;

use App\Modules\Careers\Enums\ApplicationStatus;
use App\Modules\Careers\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Job application factory (ATS pipeline tests). */
class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        return [
            'applicant_name' => $this->faker->name(),
            'applicant_email' => $this->faker->unique()->safeEmail(),
            'applicant_phone' => '+91 98'.$this->faker->numerify('#######'),
            'cover_message' => 'I have five years of client-facing relocation experience and I care about the details.',
            'source' => 'site',
            'status' => ApplicationStatus::New,
            'idempotency_key' => (string) Str::ulid(),
            'consent_at' => now(),
            'consent_version' => '2026-01',
        ];
    }
}
