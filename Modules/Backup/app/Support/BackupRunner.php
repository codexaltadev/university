<?php

namespace Modules\Backup\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Backup\Models\BackupRun;
use Modules\Backup\Types\BackupFailedNotice;
use Platform\Notifications\Notifications;
use RuntimeException;
use Spatie\Backup\Config\Config as BackupConfig;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;
use Throwable;

/**
 * Backups (C-19) through spatie/laravel-backup: database only or full (database +
 * media + ZIP-installed modules and themes), to the private disk chosen in
 * Settings › Backup, optionally password-protected. Every attempt is a
 * backup_runs row; a failure notifies the Super Admins (FR-BKP-07); runs and
 * downloads are in the activity log (BRL-BKP-02).
 */
class BackupRunner
{
    /** Hours after which "last successful backup" is unhealthy (Deployment Plan §8). */
    public const HEALTHY_HOURS = 26;

    public function run(string $type = 'database', ?int $userId = null, ?string $reason = null): BackupRun
    {
        $disk = static::disk();
        $run = BackupRun::create([
            'type' => $type, 'status' => BackupRun::RUNNING, 'disk' => $disk, 'reason' => $reason,
            'triggered_by' => $userId, 'started_at' => now(),
        ]);

        $name = Str::slug($type).'-'.now()->format('Y-m-d-His').'-'.$run->id.'.zip';
        $path = config('backup.backup.name').'/'.$name;

        try {
            config([
                'backup.backup.destination.disks' => [$disk],
                'backup.backup.password' => settings('backup.password') ?: config('backup.backup.password'),
            ]);
            // Built from today's config each time (a queue worker must see changed settings); no spatie notifications
            $job = BackupJobFactory::createFromConfig(BackupConfig::fromArray(config('backup')))
                ->setFilename($name)
                ->disableSignals();

            if ($type !== 'full') {
                $job->dontBackupFilesystem();
            }

            $job->run();

            if (! Storage::disk($disk)->exists($path)) {
                throw new RuntimeException('The backup did not finish.');
            }

            $run->update(['status' => BackupRun::SUCCESS, 'path' => $path, 'size_bytes' => Storage::disk($disk)->size($path), 'finished_at' => now()]);
        } catch (Throwable $e) {
            $run->update(['status' => BackupRun::FAILED, 'error' => Str::limit($e->getMessage(), 2000), 'finished_at' => now()]);
            app(Notifications::class)->send(new BackupFailedNotice(['type' => $type, 'error' => Str::limit($e->getMessage(), 300)]));
        }

        activity('backup')->performedOn($run)
            ->withProperties(['attributes' => ['type' => $type, 'status' => $run->status, 'reason' => $reason]])
            ->event($run->status === BackupRun::SUCCESS ? 'created' : 'failed')->log('backup '.$run->status);

        return $run;
    }

    /** FR-BKP-03: the database before a module or theme changes. */
    public function beforeChange(string $reason): array
    {
        $run = $this->run('pre_install', auth()->id(), $reason);

        return $run->status === BackupRun::SUCCESS
            ? ['status' => 'success', 'message' => $run->path]
            : ['status' => 'failed', 'message' => $run->error];
    }

    /**
     * FR-BKP-05: files older than the retention period go, but the newest successful
     * backup always stays.
     *
     * @return int runs removed
     */
    public function clean(): int
    {
        $days = max(14, (int) settings('backup.retention_days', 14));
        $newest = BackupRun::query()->where('status', BackupRun::SUCCESS)->latest('finished_at')->value('id');
        $removed = 0;

        BackupRun::query()->where('started_at', '<', now()->subDays($days))
            ->when($newest, fn ($q) => $q->whereKeyNot($newest))
            ->each(function (BackupRun $run) use (&$removed): void {
                if ($run->path && Storage::disk($run->disk)->exists($run->path)) {
                    Storage::disk($run->disk)->delete($run->path);
                }

                $run->delete();
                $removed++;
            });

        return $removed;
    }

    /** @return array{healthy: bool, last: ?BackupRun} FR-BKP-06 */
    public function health(): array
    {
        $last = BackupRun::query()->where('status', BackupRun::SUCCESS)->latest('finished_at')->first();

        return ['healthy' => $last !== null && $last->finished_at->gt(now()->subHours(self::HEALTHY_HOURS)), 'last' => $last];
    }

    /** Destination (FR-BKP-04): this server's private disk, or the remote one from Settings › Backup. */
    public static function disk(): string
    {
        return BackupDestinations::register();
    }
}
