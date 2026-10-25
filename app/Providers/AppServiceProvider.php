<?php

namespace App\Providers;

use App\Services\FmsApiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // DIU Faculty Management System API (config/services.php "fms"), one per application
        $this->app->singleton(FmsApiClient::class, fn (): FmsApiClient => new FmsApiClient(
            config('services.fms.url'),
            config('services.fms.token'),
            (int) config('services.fms.cache_ttl', 3600),
        ));
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
