<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

/**
 * Shared password rule set (03-technical-specs/05-security-reliability.md
 * §1.1). Complexity defaults live in AppServiceProvider: 12+ chars with
 * mixed case, numbers and symbols in production; relaxed locally so demo
 * seed data can hash without ceremony.
 */
trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return list<string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::defaults(), 'confirmed'];
    }
}
