<?php

namespace Modules\Backup\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Modules\Backup\Filament\Pages\ManageBackups;
use Modules\Backup\Jobs\RunBackup;
use Modules\Backup\Support\BackupDestinations;
use Modules\Backup\Support\BackupRunner;
use Modules\Backup\Support\GoogleDrive\GoogleDriveAdapter;
use Modules\Backup\Support\GoogleDrive\GoogleDriveClient;
use Platform\Backup\Backups;
use Platform\Modules\ModuleServiceProvider;
use Throwable;

class BackupServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Backup';

    protected string $nameLower = 'backup';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(BackupRunner::class);

        // Guarded by View / Create / Download:Backup (Super Admins by default)
        config()->push('filament-shield.pages.exclude', ManageBackups::class);
    }

    public function boot(): void
    {
        parent::boot();

        // FR-BKP-03: module and theme installs ask for a database backup first
        $this->app->make(Backups::class)->useRunner(fn (string $reason): array => $this->app->make(BackupRunner::class)->beforeChange($reason));

        // Google Drive as a filesystem driver (no Google SDK: Laravel's HTTP client)
        Storage::extend(BackupDestinations::GOOGLE_DRIVER, function ($app, array $config): FilesystemAdapter {
            $adapter = new GoogleDriveAdapter(
                new GoogleDriveClient((string) $config['client_id'], (string) $config['client_secret'], (string) $config['refresh_token']),
                (string) ($config['folder'] ?? 'University Backups'),
            );

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(module_path($this->name, 'routes/web.php'));
        }

        // The remote destination (S3 / Google Drive / FTP) as a disk, so earlier backups can be listed and downloaded
        try {
            BackupDestinations::register();
        } catch (Throwable) {
            // not migrated yet
        }
    }

    /** FR-BKP-01/05: times from Settings › Backup. */
    protected function configureSchedules(Schedule $schedule): void
    {
        try {
            $databaseTime = (string) settings('backup.database_time', '01:00');
            $fullDay = (int) settings('backup.full_day', 0);
            $fullTime = (string) settings('backup.full_time', '01:30');
        } catch (Throwable) {
            [$databaseTime, $fullDay, $fullTime] = ['01:00', 0, '01:30'];   // not migrated yet
        }

        $schedule->job(new RunBackup('database'))->dailyAt(substr($databaseTime, 0, 5))->name('backup:database');
        $schedule->job(new RunBackup('full'))->weeklyOn($fullDay, substr($fullTime, 0, 5))->name('backup:full');
        $schedule->call(fn () => $this->app->make(BackupRunner::class)->clean())->dailyAt('02:30')->name('backup:clean');
    }
}
