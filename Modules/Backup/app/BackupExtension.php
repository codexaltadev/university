<?php

namespace Modules\Backup;

use Modules\Backup\Filament\BackupPlugin;
use Modules\Backup\Settings\BackupSettings;
use Modules\Backup\Types\BackupFailedNotice;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class BackupExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(BackupPlugin::class);
        $r->settings(BackupSettings::class);           // Settings › Backup (Super Admins)
        $r->notification(BackupFailedNotice::class);   // FR-BKP-07
    }
}
