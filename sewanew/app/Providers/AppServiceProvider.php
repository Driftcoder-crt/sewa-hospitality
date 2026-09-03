<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Production-grade database configuration
        Schema::defaultStringLength(191);
        
        // Enforce strict mode for data integrity
        Model::shouldBeStrict(! $this->app->isProduction());
        
        // Prevent lazy loading in production for performance
        Model::preventLazyLoading(! $this->app->isProduction());
        
        // Prevent access to missing attributes
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
        
        // Prevent silent discarding of attributes
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
