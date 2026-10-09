<?php

namespace Modules\Statistics\Providers;

use Modules\Statistics\Filament\Pages\ManageStatistics;
use Platform\Modules\ModuleServiceProvider;

class StatisticsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Statistics';

    protected string $nameLower = 'statistics';

    public function register(): void
    {
        parent::register();

        // Guarded by Manage:Statistic / ManageMain:Statistic (BRL-STAT-01), not a page permission
        config()->push('filament-shield.pages.exclude', ManageStatistics::class);
    }
}
