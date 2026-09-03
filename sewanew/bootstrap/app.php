<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Admin panel routes (admin.sewahospitality.com)
            Route::middleware(['web'])
                ->group(base_path('routes/admin.php'));

            // Client portal routes (app.sewahospitality.com)
            Route::middleware(['web'])
                ->group(base_path('routes/portal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
