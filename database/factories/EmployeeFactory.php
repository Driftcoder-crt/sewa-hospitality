<?php

namespace Database\Factories;

use App\Modules\Careers\Enums\Department;
use App\Modules\Careers\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Employee factory (team grid + /team page tests). */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->name();

        return [
            'employee_code' => 'SEW-'.Str::upper(Str::random(5)),
            'full_name' => $name,
            'designation' => 'Senior Relocation Consultant',
            'department' => Department::Relocation,
            'joined_at' => $this->faker->dateTimeBetween('-4 years', '-6 months'),
            'employment_type' => 'full',
            'is_public' => true,
            'bio' => 'Ten years of client-first mobility work across three countries.',
            'credentials' => [
                'languages' => ['English', 'Hindi'],
                'certifications' => ['EuRA Global Mobility Specialist'],
            ],
            'status' => 'active',
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (): array => ['is_public' => false]);
    }
}
