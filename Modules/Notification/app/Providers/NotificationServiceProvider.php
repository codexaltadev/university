<?php

namespace Modules\Notification\Providers;

use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Modules\Notification\Filament\Pages\ContentFreshness;
use Modules\Notification\Filament\Pages\MyNotifications;
use Modules\Notification\Filament\Pages\NotificationTemplates;
use Modules\Notification\Filament\Widgets\MyContentFreshness;
use Modules\Notification\Support\Notifier;
use Modules\Notification\Support\Reminders;
use Modules\Notification\Types\ModuleInstalledNotice;
use Modules\Notification\Types\ModuleUninstalledNotice;
use Modules\Notification\Types\UserCreatedNotice;
use Platform\Events\ModuleInstalled;
use Platform\Events\ModuleUninstalled;
use Platform\Modules\ModuleServiceProvider;
use Platform\Notifications\Notifications;

class NotificationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notification';

    protected string $nameLower = 'notification';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Notifier::class);
        $this->app->singleton(Reminders::class);

        // Every user's own page; templates guarded by Manage:NotificationTemplate; the report by ViewAny:ReminderRule
        config()->push('filament-shield.pages.exclude', MyNotifications::class);
        config()->push('filament-shield.pages.exclude', NotificationTemplates::class);
        config()->push('filament-shield.pages.exclude', ContentFreshness::class);
        config()->push('filament-shield.widgets.exclude', MyContentFreshness::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(Notifications::class)->useSender(fn ($notification): int => $this->app->make(Notifier::class)->send($notification));

        // FR-NOTI-04: core events
        $notify = fn ($notification) => $this->app->make(Notifications::class)->send($notification);

        Event::listen(fn (ModuleInstalled $event) => $notify(new ModuleInstalledNotice(['module' => $event->manifest->name(), 'version' => $event->manifest->version()])));
        Event::listen(fn (ModuleUninstalled $event) => $notify(new ModuleUninstalledNotice(['module' => $event->manifest->name(), 'version' => $event->manifest->version()])));

        // An account made by an admin (invitation, user screen); seeders and imports stay quiet
        User::created(function (User $user) use ($notify): void {
            if (Auth::check()) {
                $notify(new UserCreatedNotice(['name' => $user->name, 'email' => $user->email, 'by' => Auth::user()->name, 'by_id' => Auth::id()], $user));
            }
        });
    }

    /** FR-NOTI-08: read notifications go after 90 days. */
    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->call(fn () => Notifier::clean())->dailyAt('03:15')->name('notification:clean');
        $schedule->call(fn () => app(Reminders::class)->check())->dailyAt('07:00')->name('notification:reminders');     // FR-NOTI-11/12
        $schedule->call(fn () => app(Reminders::class)->summary())->weeklyOn(0, '08:00')->name('notification:summary'); // FR-NOTI-13
    }
}
