<?php

namespace Modules\Notification\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;

/** FR-NOTI-04: a module was installed or updated — Super Admins. */
class ModuleInstalledNotice extends NotificationType
{
    public static function key(): string
    {
        return 'core.module_installed';
    }

    public static function label(): string
    {
        return __('notification::notification.types.module_installed');
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
        return __('notification::notification.texts.module_installed.subject', [], $locale);
    }

    public static function defaultBody(string $locale): string
    {
        return __('notification::notification.texts.module_installed.body', [], $locale);
    }

    public function recipients(): Collection
    {
        return Recipients::superAdmins();
    }

    public function url(): ?string
    {
        return Route::has('filament.admin.pages.modules') ? route('filament.admin.pages.modules') : null;
    }
}
