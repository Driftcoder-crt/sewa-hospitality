<?php

use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Fortify configuration
|--------------------------------------------------------------------------
| Fortify provides the authentication backend (login, 2FA challenge,
| password resets) for the admin and portal hosts. Its routes are mounted
| per-host in bootstrap/app.php (prefix '' / domain null here); each host
| has its own root, so `home` is '/'. Views live in resources/views/auth
| and are registered in AppServiceProvider (loginView, twoFactorChallengeView).
| Login rate limiting: the `login` limiter (5/min/IP+email, AppServiceProvider).
*/

return [

    'guard' => 'web',

    // Name of the password BROKER (auth.php passwords.*), as in stock
    // Fortify. NOT the Password rule — the rule default itself lives in
    // AppServiceProvider::boot() via Password::defaults(closure); calling
    // the Password::defaults() GETTER here (no args) would evaluate static
    // state at config-load time and break multi-app boots (Pest).
    'passwords' => 'users',

    'username' => 'email',

    'email_column' => 'email',

    'lowercase_usernames' => true,

    'home' => '/',

    'prefix' => '',

    'domain' => null,

    'middleware' => ['web'],

    'limiters' => [
        'login' => 'login',
    ],

    'views' => true,

    /*
     | Login only. Login itself is core Fortify and needs no feature flag;
     | registration is invitation-based (admin/portal invites) and stays
     | off by not enabling any feature. Profile/password management and
     | 2FA enrolment arrive with the admin and portal milestones and are
     | added to this list then.
     */
    'features' => [
        // Features::registration(), Features::resetPasswords(), ...
    ],

    'redirects' => [
        'login' => null,
        'logout' => null,
        'password-reset' => null,
        'register' => null,
        'email-verification' => null,
        'two-factor-authentication' => null,
    ],

];
