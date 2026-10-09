<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
| spatie/laravel-backup, driven by the Backup core module (C-19). The module sets
| the destination disk, archive password and what a run includes from Settings ›
| Backup; runs, retention and notifications are the module's own (backup_runs,
| FR-BKP-05/07), so spatie's notifications and cleanup strategy are not used.
*/
return [

    'backup' => [
        'name' => 'uwms-backups',

        'source' => [
            'files' => [
                // "Full" backup: uploaded media, ZIP-installed modules and themes (Deployment Plan §7)
                'include' => [
                    storage_path('app/public'),
                    storage_path('app/modules-runtime'),
                    storage_path('app/themes-runtime'),
                ],
                'exclude' => [
                    storage_path('app/public/theme'),          // compiled token CSS: made again on demand
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => storage_path('app'),
            ],

            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => '',
            // Private disk only (BRL-BKP-01); offsite disk (S3 …) from Settings › Backup
            'disks' => [env('BACKUP_DISK', 'local')],
            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',
        'verify_backup' => false,
        'tries' => 1,
        'retry_delay' => 0,
    ],

    // The Backup module notifies through the platform (FR-BKP-07)
    'notifications' => [
        // Every class listed (spatie expects the keys), none with a channel
        'notifications' => [
            BackupHasFailedNotification::class => [],
            UnhealthyBackupWasFoundNotification::class => [],
            CleanupHasFailedNotification::class => [],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],
        'notifiable' => Notifiable::class,
        'mail' => ['to' => 'nobody@example.invalid', 'from' => ['address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'), 'name' => env('MAIL_FROM_NAME', 'Backup')]],   // unused: no spatie notifications
        'slack' => ['webhook_url' => '', 'channel' => null, 'username' => null, 'icon' => null],
        'discord' => ['webhook_url' => '', 'username' => '', 'avatar_url' => ''],
        'webhook' => ['url' => ''],
    ],

    'log_channel' => null,

    'monitor_backups' => [
        [
            'name' => 'uwms-backups',
            'disks' => [env('BACKUP_DISK', 'local')],
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 20000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 14,
            'keep_daily_backups_for_days' => 14,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 0,
            'keep_yearly_backups_for_years' => 0,
            'delete_oldest_backups_when_using_more_megabytes_than' => 20000,
        ],
        'tries' => 1,
        'retry_delay' => 0,
    ],
];
