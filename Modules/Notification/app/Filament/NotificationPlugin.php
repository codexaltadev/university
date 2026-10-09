<?php

namespace Modules\Notification\Filament;

use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Modules\Notification\Filament\Pages\ContentFreshness;
use Modules\Notification\Filament\Pages\MyNotifications;
use Modules\Notification\Filament\Pages\NotificationTemplates;
use Modules\Notification\Filament\Resources\ReminderRules\ReminderRuleResource;
use Modules\Notification\Filament\Widgets\MyContentFreshness;

class NotificationPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'notification';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->databaseNotifications()                     // bell with unread count (FR-NOTI-01)
            ->databaseNotificationsPolling('30s')
            ->pages([MyNotifications::class, NotificationTemplates::class, ContentFreshness::class])
            ->resources([ReminderRuleResource::class])
            ->widgets([MyContentFreshness::class])                // FR-NOTI-16
            ->userMenuItems([
                Action::make('myNotifications')->label(fn (): string => __('notification::notification.my.title'))
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->url(fn (): string => MyNotifications::getUrl()),
            ]);
    }

    public function boot(Panel $panel): void {}
}
