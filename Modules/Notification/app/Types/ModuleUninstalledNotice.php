<?php

namespace Modules\Notification\Types;

use Illuminate\Support\Collection;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;

/** FR-NOTI-04: a module was uninstalled — Super Admins. */
class ModuleUninstalledNotice extends NotificationType
{
    public static function key(): string
    {
        return 'core.module_uninstalled';
    }

    public static function label(): string
    {
        return __('notification::notification.types.module_uninstalled');
    }

    public static function defaultChannels(): array
    {
        return ['database'];
    }

    public static function placeholders(): array
    {
        return ['module' => 'Module name', 'version' => 'Version'];
    }

    public static function defaultSubject(string $locale): string
    {
        return __('notification::notification.texts.module_uninstalled.subject', [], $locale);
    }

    public static function defaultBody(string $locale): string
    {
        return __('notification::notification.texts.module_uninstalled.body', [], $locale);
    }

    public function recipients(): Collection
    {
        return Recipients::superAdmins();
    }
}
