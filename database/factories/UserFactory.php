<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * User factory (03-technical-specs/03-database-schema.md §1).
 * `email_verified_at` is intentionally absent — the users table has no
 * verified-at column; account state lives in `status`
 * (active|invited|disabled, App\Enums\UserStatus). Phone numbers are
 * Indian E.164 (+91 + 10 digits).
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => '+91'.fake()->numerify('##########'),
            'locale' => 'en',
            'timezone' => 'Asia/Kolkata',
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    /** Blocked account (security disable, not deletion — 05-security-reliability). */
    public function disabled(): static
    {
        return $this->state(fn (): array => [
            'status' => UserStatus::Disabled,
        ]);
    }

    /** Portal invitee who has not accepted yet (magic-link flow, M5). */
    public function invited(): static
    {
        return $this->state(fn (): array => [
            'status' => UserStatus::Invited,
        ]);
    }
}
