<?php

namespace Modules\Backup\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Backup\Filament\Pages\ManageBackups;

class BackupPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'backup';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageBackups::class]);
    }

    public function boot(Panel $panel): void {}
}
