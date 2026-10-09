# Backup

Core module C-19 (SRS Part 1). Site › Backups and the Settings › Backup tab, on top of
`spatie/laravel-backup` (Deployment Plan §7).

| | |
|---|---|
| Slug | `backup` |
| Tables | `backup_runs` (L) — DB-02 §15 (+ `reason`) |
| Permissions | custom `View:Backup`, `Create:Backup`, `Download:Backup` — Super Admins by default |
| Settings | `backup`: database_time, full_day, full_time, retention_days (≥ 14), destination (local / s3 / gdrive / ftp) with its access details, password; secrets encrypted |

* **Schedule** (FR-BKP-01): database every day (01:00), full — database, uploaded media, ZIP-installed
  modules and themes — every week (Sunday 01:30); times in Settings › Backup. Jobs run on the
  `maintenance` queue.
* **Backup now** (FR-BKP-02): database only or full.
* **Before installs** (FR-BKP-03): the kernel's `Platform\Backup\Backups::beforeChange()` — called by module
  and theme install / update — runs a database backup first; if it fails, the install stops.
  `UWMS_BACKUP_BEFORE_CHANGE=false` switches it off (tests).
* **Destination** (FR-BKP-04): this server's private storage, **Amazon S3 or S3-compatible** (Wasabi, DigitalOcean
  Spaces, MinIO, R2 — key, secret, bucket, region, endpoint, folder, path-style), **Google Drive** or
  **FTP / FTPS** — all in Settings › Backup with "Test connection"; becomes the disk `backup-remote` at runtime
  (`BackupDestinations`). Never the public disk (BRL-BKP-01). Optional archive password.
* **Google Drive:** the university's own OAuth client (Client ID / secret, Drive API on) and "Connect Google
  account" — Google's consent page, state-checked callback `/admin/backup/google-drive/callback`, refresh token
  saved encrypted; scope `drive.file` (the app sees only its own files). Backups go to one folder (default
  "University Backups"). No Google SDK: `GoogleDriveClient` + `GoogleDriveAdapter` (Flysystem) on Laravel's
  HTTP client.
* **Retention** (FR-BKP-05): at least 14 days; the newest successful backup always stays. Daily 02:30.
* **List, download, health** (FR-BKP-06): last successful backup, warning after 26 hours; download through
  the admin panel only, logged (BRL-BKP-02).
* **Failure** (FR-BKP-07): Super Admins are notified (bell and email).
* Not yet: restore from the admin panel (FR-BKP-08, Could) — restore with the Deployment Plan §7 steps.
