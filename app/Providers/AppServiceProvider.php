<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Test-only fixture tables for kernel tests
        if ($this->app->runningUnitTests()) {
            $this->loadMigrationsFrom(base_path('tests/Fixtures/migrations'));
        }
    }
}
