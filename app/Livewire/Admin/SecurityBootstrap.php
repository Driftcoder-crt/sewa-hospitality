<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * 2FA bootstrap page (route admin.security). Unenrolled super-admin/admin
 * accounts are parked here by the admin.2fa middleware until TOTP is
 * confirmed; the page states the account status and the exact artisan
 * command to run. The full enrolment UI lands with the System screens (M1).
 */
#[Layout('layouts.admin')]
class SecurityBootstrap extends Component
{
    public function render(): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        return view('admin.security', [
            'twoFactorConfirmed' => $user !== null && $user->two_factor_confirmed_at !== null,
            'hasTwoFactorSecret' => $user !== null && $user->two_factor_secret !== null,
            'enableCommand' => 'php artisan user:enable-2fa '.($user?->email ?? ''),
        ]);
    }
}
