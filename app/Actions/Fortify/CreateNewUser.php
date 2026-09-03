<?php

namespace App\Actions\Fortify;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * M0 note: the public site has NO self-registration — leads and career
 * applications are forms, not accounts. Staff accounts are provisioned
 * via `php artisan sewa:admin` (bootstrap) or the System screens (M1);
 * portal clients are invited from Portal ops (M5). This Fortify action
 * stays wired for those future flows and is the single creation path
 * they should reuse.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a new user with an active status.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => strtolower(trim((string) $input['email'])),
            'password' => $input['password'],
            'locale' => 'en',
            'status' => UserStatus::Active,
        ]);
    }
}
