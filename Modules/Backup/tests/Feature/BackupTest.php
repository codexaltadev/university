<?php

/*
| Phase 2.12 — Backup (C-19): database backup through spatie, failure notice,
| backup before installs, retention, health, screen with "Backup now" and
| download. FR-BKP-01..07, BRL-BKP-01/02.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification as NotificationFake;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Backup\Filament\Pages\ManageBackups;
use Modules\Backup\Jobs\RunBackup;
use Modules\Backup\Models\BackupRun;
use Modules\Backup\Support\BackupDestinations;
use Modules\Backup\Support\BackupRunner;
use Modules\Notification\Notifications\PlatformNotification;
use Modules\Settings\Filament\Pages\ManageSettings;
use Platform\Backup\Backups;
use Platform\Settings\Setting;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Storage::fake('local');
    $this->runner = app(BackupRunner::class);
});

it('backs up the database to the private disk and records the run (FR-BKP-01/02, BRL-BKP-01)', function () {
    $run = $this->runner->run('database');

    expect($run->status)->toBe('success')
        ->and($run->disk)->toBe('local')
        ->and($run->path)->toStartWith('uwms-backups/database-')
        ->and($run->size_bytes)->toBeGreaterThan(0)
        ->and(Storage::disk('local')->exists($run->path))->toBeTrue()
        ->and(Activity::query()->where('log_name', 'backup')->where('event', 'created')->exists())->toBeTrue();   // BRL-BKP-02
});

it('sends backups to Amazon S3 / S3-compatible storage set up in Settings › Backup (FR-BKP-04)', function () {
    settings()->save('backup', [
        'destination' => 's3', 's3_key' => 'AKIAEXAMPLE', 's3_secret' => 'top-secret', 's3_bucket' => 'diu-backups',
        's3_region' => 'ap-southeast-1', 's3_endpoint' => 'https://s3.wasabisys.com', 's3_root' => 'web',
    ], encrypted: BackupDestinations::SECRETS);

    expect(BackupRunner::disk())->toBe(BackupDestinations::REMOTE_DISK)
        ->and(config('filesystems.disks.backup-remote'))->toMatchArray([
            'driver' => 's3', 'key' => 'AKIAEXAMPLE', 'secret' => 'top-secret', 'bucket' => 'diu-backups',
            'region' => 'ap-southeast-1', 'endpoint' => 'https://s3.wasabisys.com', 'root' => 'web',
        ])
        ->and(Setting::query()->where('group', 'backup')->where('key', 's3_secret')->value('value'))->not->toContain('top-secret');

    Storage::fake(BackupDestinations::REMOTE_DISK);
    $run = $this->runner->run('database');
    expect($run->status)->toBe('success')
        ->and($run->disk)->toBe('backup-remote')
        ->and(Storage::disk('backup-remote')->exists($run->path))->toBeTrue();

    settings()->save('backup', ['destination' => 'local']);
    expect(BackupRunner::disk())->toBe('local');
});

it('tests the connection before saving, with the saved secret when the field is empty', function () {
    expect(BackupDestinations::test(['destination' => 'local']))->toBeNull();

    $error = BackupDestinations::test(['destination' => 'ftp', 'ftp_host' => '127.0.0.1', 'ftp_port' => 9, 'ftp_username' => 'x', 'ftp_password' => 'y', 'ftp_ssl' => false]);
    expect($error)->toBeString()->not->toBeEmpty();
});

it('asks for the S3 or FTP details only when that destination is chosen', function () {
    $this->actingAs(superAdmin());

    Livewire::withQueryParams(['tab' => 'tab-backup'])->test(ManageSettings::class)
        ->assertDontSee('Secret access key')
        ->fillForm(['backup.destination' => 's3'])
        ->assertSee('Secret access key')->assertSee('Test connection')
        ->assertDontSee('Passive mode')
        ->fillForm(['backup.s3_key' => 'AKIA', 'backup.s3_secret' => 'shh', 'backup.s3_bucket' => 'b'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(settings('backup.destination'))->toBe('s3')->and(settings()->group('backup')['s3_secret'])->toBe('shh');

    // Saving again with the secret field empty keeps it
    Livewire::withQueryParams(['tab' => 'tab-backup'])->test(ManageSettings::class)
        ->fillForm(['backup.s3_bucket' => 'b2'])->call('save')->assertHasNoFormErrors();
    expect(settings()->group('backup')['s3_secret'])->toBe('shh')->and(settings('backup.s3_bucket'))->toBe('b2');
});

it('tells the Super Admins when a backup fails (FR-BKP-07)', function () {
    NotificationFake::fake();
    $admin = superAdmin();
    config(['backup.backup.source.databases' => ['no-such-connection']]);

    $run = $this->runner->run('database');

    expect($run->status)->toBe('failed')->and($run->error)->not->toBeEmpty();
    NotificationFake::assertSentTo($admin, PlatformNotification::class, fn (PlatformNotification $n): bool => $n->key === 'backup.failed');
});

it('backs up the database before a module or theme install, and reports a failure (FR-BKP-03)', function () {
    config(['platform.backup.before_change' => true]);

    expect(app(Backups::class)->beforeChange('install news 1.0.0'))->toMatchArray(['status' => 'success'])
        ->and(BackupRun::query()->where('type', 'pre_install')->value('reason'))->toBe('install news 1.0.0');

    config(['backup.backup.source.databases' => ['no-such-connection']]);
    expect(app(Backups::class)->beforeChange('install news 1.0.1')['status'])->toBe('failed');

    config(['platform.backup.before_change' => false]);
    expect(app(Backups::class)->beforeChange('x')['status'])->toBe('skipped');
});

it('removes backups older than the retention period but keeps the newest good one (FR-BKP-05)', function () {
    $make = function (int $daysOld, string $status = 'success'): BackupRun {
        $path = 'uwms-backups/test-'.$daysOld.'-'.$status.'.zip';
        Storage::disk('local')->put($path, 'zip');

        return BackupRun::create(['type' => 'database', 'status' => $status, 'disk' => 'local', 'path' => $path,
            'started_at' => now()->subDays($daysOld), 'finished_at' => now()->subDays($daysOld)]);
    };

    $old = $make(40);
    $oldFailed = $make(30, 'failed');
    $recent = $make(3);

    expect($this->runner->clean())->toBe(2)
        ->and(BackupRun::query()->pluck('id')->all())->toBe([$recent->id])
        ->and(Storage::disk('local')->exists($old->path))->toBeFalse();

    // Even when everything is old, the newest successful backup stays
    $recent->update(['started_at' => now()->subDays(100), 'finished_at' => now()->subDays(100)]);
    expect($this->runner->clean())->toBe(0);
});

it('shows health: the last successful backup and a warning after 26 hours (FR-BKP-06)', function () {
    expect($this->runner->health()['healthy'])->toBeFalse();

    $run = BackupRun::create(['type' => 'database', 'status' => 'success', 'disk' => 'local', 'started_at' => now(), 'finished_at' => now()->subHours(2)]);
    expect($this->runner->health())->toMatchArray(['healthy' => true]);

    $run->update(['finished_at' => now()->subHours(30)]);
    expect($this->runner->health()['healthy'])->toBeFalse();
});

it('lets Super Admins start a backup and download it; others may not (FR-BKP-02/06)', function () {
    $this->actingAs(makeUser());
    $this->get(ManageBackups::getUrl())->assertForbidden();

    Queue::fake();
    $this->actingAs(superAdmin());
    $this->get(ManageBackups::getUrl())->assertOk()->assertSee('No successful backup yet.');

    Livewire::test(ManageBackups::class)->call('start', 'full')->assertNotified();
    Queue::assertPushedOn('maintenance', RunBackup::class, fn (RunBackup $job): bool => $job->type === 'full' && $job->userId === auth()->id());

    Storage::disk('local')->put('uwms-backups/x.zip', 'zip-bytes');
    $run = BackupRun::create(['type' => 'database', 'status' => 'success', 'disk' => 'local', 'path' => 'uwms-backups/x.zip', 'size_bytes' => 9, 'started_at' => now(), 'finished_at' => now()]);
    $failed = BackupRun::create(['type' => 'database', 'status' => 'failed', 'disk' => 'local', 'error' => 'disk full', 'started_at' => now(), 'finished_at' => now()]);

    Livewire::test(ManageBackups::class)
        ->assertCanSeeTableRecords([$run, $failed])
        ->assertTableActionVisible('download', $run)
        ->assertTableActionHidden('download', $failed)
        ->callTableAction('download', $run)
        ->assertFileDownloaded('x.zip');

    expect(Activity::query()->where('log_name', 'backup')->where('event', 'downloaded')->exists())->toBeTrue();
});
