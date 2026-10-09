<?php

namespace Modules\Settings;

use Modules\Settings\Filament\SettingsPlugin;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class SettingsExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(SettingsPlugin::class);

        // The core tabs; modules add theirs the same way (FR-SET-10)
        foreach ([
            Groups\GeneralSettings::class, Groups\ContactSettings::class, Groups\SocialSettings::class,
            Groups\MediaSettings::class, Groups\ModuleVisibilitySettings::class, Groups\MailSettings::class,
            Groups\MaintenanceSettings::class, Groups\CodeSettings::class, Groups\AdminBrandingSettings::class,
        ] as $group) {
            $r->settings($group);
        }
    }
}
