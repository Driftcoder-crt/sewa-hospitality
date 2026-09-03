<?php

namespace Database\Factories;

use App\Modules\Careers\Enums\Department;
use App\Modules\Careers\Enums\EmploymentType;
use App\Modules\Careers\Enums\JobStatus;
use App\Modules\Careers\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Job posting factory (careers + ATS tests). */
class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    public function definition(): array
    {
        $title = $this->faker->randomElement([
            'Relocation Consultant', 'Immigration Specialist', 'Housing Coordinator',
        ]).' ('.$this->faker->unique()->numberBetween(100, 999).')';

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'department' => Department::Relocation,
            'location_text' => 'Gurugram, Haryana',
            'employment_type' => EmploymentType::Full,
            'experience_min' => 2,
            'experience_max' => 5,
            'description_html' => '<p>We move people with care — and we need a colleague who does the same.</p>',
            'status' => JobStatus::Open,
            'published_at' => now(),
            'locale' => 'en',
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => JobStatus::Closed,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => JobStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (): array => [
            'status' => JobStatus::Paused,
        ]);
    }
}
