<?php

namespace Modules\Backup\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Where backups go (FR-BKP-04): this server's private storage, Amazon S3 or an
 * S3-compatible service (Wasabi, DigitalOcean Spaces, MinIO, Cloudflare R2 …),
 * or an FTP server — configured in Settings › Backup, not in the server's .env.
 * The chosen destination becomes the filesystem disk "backup-remote" at runtime.
 */
class BackupDestinations
{
    public const TYPES = ['local', 's3', 'gdrive', 'ftp'];

    public const REMOTE_DISK = 'backup-remote';

    /** Settings keys that hold a secret (encrypted, never shown again). */
    public const SECRETS = ['password', 's3_secret', 'ftp_password', 'gdrive_client_secret', 'gdrive_refresh_token'];

    /** Filesystem driver of the Google Drive adapter (registered by the module). */
    public const GOOGLE_DRIVER = 'uwms-google-drive';

    /** Registers the remote disk from the saved settings; returns the disk backups use. */
    public static function register(): string
    {
        $values = settings()->group('backup');
        $config = static::diskConfig($values);

        if ($config === null) {
            return 'local';
        }

        config(['filesystems.disks.'.self::REMOTE_DISK => $config]);

        return self::REMOTE_DISK;
    }

    /**
     * Filesystem disk config for the chosen destination, or null for this server.
     *
     * @param  array<string, mixed>  $v  Settings › Backup values
     * @return array<string, mixed>|null
     */
    public static function diskConfig(array $v): ?array
    {
        return match ($v['destination'] ?? 'local') {
            's3' => [
                'driver' => 's3',
                'key' => $v['s3_key'] ?? null,
                'secret' => $v['s3_secret'] ?? null,
                'region' => ($v['s3_region'] ?? null) ?: 'us-east-1',
                'bucket' => $v['s3_bucket'] ?? null,
                'endpoint' => ($v['s3_endpoint'] ?? null) ?: null,
                'use_path_style_endpoint' => (bool) ($v['s3_path_style'] ?? false),
                'root' => trim((string) ($v['s3_root'] ?? ''), '/') ?: null,
                'visibility' => 'private',
                'throw' => true,
            ],
            'gdrive' => [
                'driver' => self::GOOGLE_DRIVER,
                'client_id' => $v['gdrive_client_id'] ?? null,
                'client_secret' => $v['gdrive_client_secret'] ?? null,
                'refresh_token' => $v['gdrive_refresh_token'] ?? null,
                'folder' => ($v['gdrive_folder'] ?? null) ?: 'University Backups',
                'throw' => true,
            ],
            'ftp' => [
                'driver' => 'ftp',
                'host' => $v['ftp_host'] ?? null,
                'port' => (int) (($v['ftp_port'] ?? null) ?: 21),
                'username' => $v['ftp_username'] ?? null,
                'password' => $v['ftp_password'] ?? null,
                'root' => ($v['ftp_root'] ?? null) ?: '/',
                'ssl' => (bool) ($v['ftp_ssl'] ?? true),
                'passive' => (bool) ($v['ftp_passive'] ?? true),
                'timeout' => 30,
                'throw' => true,
            ],
            default => null,
        };
    }

    /**
     * Writes and removes a small file to check the destination ("Test connection").
     * Empty secret fields use the saved secret.
     *
     * @param  array<string, mixed>  $values  form values
     * @return string|null error, null when it works
     */
    public static function test(array $values): ?string
    {
        $saved = settings()->group('backup');

        foreach (self::SECRETS as $key) {
            if (blank($values[$key] ?? null)) {
                $values[$key] = $saved[$key] ?? null;
            }
        }

        $config = static::diskConfig($values);

        if ($config === null) {
            return null;   // this server
        }

        try {
            $disk = Storage::build($config);
            $file = config('backup.backup.name').'/.connection-test-'.uniqid();
            $disk->put($file, 'ok');
            $disk->delete($file);

            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
