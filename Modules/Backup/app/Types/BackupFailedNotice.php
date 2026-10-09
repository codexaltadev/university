<?php

namespace Modules\Backup\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;

/** FR-BKP-07, FR-NOTI-04: a backup failed — Super Admins, in the bell and by email. */
class BackupFailedNotice extends NotificationType
{
    public static function key(): string
    {
        return 'backup.failed';
    }

    public static function label(): string
    {
        return __('backup::backup.notice.label');
    }

    public static function placeholders(): array
    {
        return ['type' => 'database / full / pre_install', 'error' => 'What went wrong'];
    }

    public static function defaultSubject(string $locale): string
    {
        return __('backup::backup.notice.subject', [], $locale);
    }

    public static function defaultBody(string $locale): string
    {
        return __('backup::backup.notice.body', [], $locale);
    }

    public function recipients(): Collection
    {
        return Recipients::superAdmins();
    }

    public function url(): ?string
    {
        return Route::has('filament.admin.pages.backups') ? route('filament.admin.pages.backups') : null;
    }
}
