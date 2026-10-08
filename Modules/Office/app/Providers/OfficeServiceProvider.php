<?php

namespace Modules\Office\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Office\Models\Office;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Platform\Permissions\EntityPermissions;

class OfficeServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Office';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'office';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Each office gets its own "Manage:Office#{id}" permission (FR-ROLE-07)
        $this->app->make(EntityPermissions::class)->register(Office::class);
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
