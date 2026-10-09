<?php

namespace Modules\Notification;

use Modules\Notification\Filament\NotificationPlugin;
use Modules\Notification\Types\ContentReminderNotice;
use Modules\Notification\Types\ModuleInstalledNotice;
use Modules\Notification\Types\ModuleUninstalledNotice;
use Modules\Notification\Types\ReminderSummaryNotice;
use Modules\Notification\Types\UserCreatedNotice;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

/**
 * Everything this module adds to the platform (SAD Part A §7).
 * Each registration needs the matching key in module.json "provides".
 */
final class NotificationExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(NotificationPlugin::class);

        // Core notification types (FR-NOTI-04)
        $r->notification(ModuleInstalledNotice::class);
        $r->notification(ModuleUninstalledNotice::class);
        $r->notification(UserCreatedNotice::class);
        $r->notification(ContentReminderNotice::class);    // Content Update Reminder (FR-NOTI-12)
        $r->notification(ReminderSummaryNotice::class);    // weekly admin summary (FR-NOTI-13)
    }
}
