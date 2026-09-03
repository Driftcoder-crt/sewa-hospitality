<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for role-based access control
        // These integrate with Spatie Permission package
        
        Gate::before(function ($user, $ability) {
            // Super-admin bypasses all gates
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        // Admin-level gates
        Gate::define('admin-access', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin']);
        });

        // Content management gates
        Gate::define('manage-content', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'editor', 'author']);
        });

        // Lead/CRM gates
        Gate::define('manage-leads', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'consultant']);
        });

        // HR gates
        Gate::define('manage-hr', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'hr-manager', 'recruiter']);
        });

        // Finance/Billing gates
        Gate::define('manage-billing', function ($user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'finance']);
        });

        // Portal client manager gates
        Gate::define('portal-manager', function ($user) {
            return $user->hasRole('client-manager');
        });

        // Portal employee gates
        Gate::define('portal-employee', function ($user) {
            return $user->hasAnyRole(['client-manager', 'client-employee']);
        });
    }
}
