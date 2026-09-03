<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\Security\NotBreachedPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Password reset action (Fortify contract) with the HIBP breach-list
 * check (05-security-reliability.md §1.1 — breach check on RESET).
 * Complexity rules come from Password::defaults(); the k-anonymity
 * rule rides on top and fails open when HIBP is unreachable (never
 * lock a user out because a dependency is down).
 */
final class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [...$this->passwordRules(), (new NotBreachedPassword)->validate()],
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();

        // A reset invalidates every existing session/token.
        $user->tokens()->delete();
    }
}
