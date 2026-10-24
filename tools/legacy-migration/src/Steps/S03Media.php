<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\IdMap;
use Legacy\Migration\Support\Normalizer;
use Platform\Media\MediaFolder;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
use Platform\Media\MediaRejected;
use Platform\Modules\ModuleRegistry;

/**
 * S03 Media import (Data Migration Plan §3, §5; DB-02 §18): the files the legacy rows point to go
 * into the Media Library **once** — the same path, or the same content, is the same item (id map
 * "files", key = the path) — and the migrated record uses that item in its field. Other steps take
 * their files from the map too (S17D, S17G, S17A …), so run them again after S03.
 *
 * Files come from webbackend.daffodilvarsity.edu.bd into a cache (LEGACY_FILES_PATH, default
 * storage/app/legacy-files): `--download-files` fetches the missing ones (4 at a time, 3 tries,
 * "UWMS-Migration"); without it only the cache is used. `--only=program,faculty` limits the groups.
 * A field someone already filled by hand is left alone. Run after the steps of the groups.
 */
class S03Media extends Step
{
    /**
     * group => list of [module slug, legacy table, column, model, media field] — the table is also
     * the id map key of the step that migrated the rows — or, for a table of files that belong to
     * another row (a notice's files), [… , parent column, parent table, title column]: every file
     * of that row goes into the field, in order, with its title.
     */
    public const SOURCES = [
        'faculty' => [['faculty', 'faculties', 'faculty_photo', 'Modules\\Faculty\\Models\\Faculty', 'photo']],
        'department' => [
            ['department', 'departments', 'department_photo', 'Modules\\Department\\Models\\Department', 'photo'],
            ['department', 'departments', 'department_cover', 'Modules\\Department\\Models\\Department', 'cover'],
        ],
        'program' => [
            ['program', 'department_program_type', 'program_image', 'Modules\\Program\\Models\\Program', 'image'],
            ['program', 'program_facilitators', 'image', 'Modules\\Program\\Models\\ProgramFacilitator', 'photo'],
            ['program', 'program_professional_memberships', 'image', 'Modules\\Program\\Models\\ProgramMembership', 'logo'],
            ['program', 'program_variants', 'image', 'Modules\\Program\\Models\\ProgramVariant', 'image'],
        ],
        'facilities' => [['facilities', 'facilities', 'facility_icon', 'Modules\\Facilities\\Models\\Facility', 'image']],
        'accreditation' => [
            ['accreditation', 'accreditations', 'logo', 'Modules\\Accreditation\\Models\\Accreditation', 'logo'],
            ['accreditation', 'rankings', 'logo', 'Modules\\Accreditation\\Models\\Ranking', 'logo'],
        ],
        'testimonials' => [['testimonials', 'testimonials', 'photo', 'Modules\\Testimonials\\Models\\Testimonial', 'photo']],
        'events' => [['events', 'events', 'photo', 'Modules\\Events\\Models\\Event', 'featured_image']],
        'notices' => [['notice', 'notice_files', 'file_name', 'Modules\\Notice\\Models\\Notice', 'attachments', 'notice_id', 'notices', 'file_title']],
    ];

    /** Plan §3: at most 4 downloads at once, 3 tries */
    public const PARALLEL = 4;

    public const TRIES = 3;

    /** Longest side of a photo that had to be made smaller (shrink) */
    public const MAX_SIDE = 2400;

    /** @var array<string, int> folder per legacy table */
    protected array $folders = [];

    public static function key(): string
    {
        return 'S03';
    }

    public static function title(): string
    {
        return 'Files into the Media Library';
    }

    public static function requires(): array
    {
        return [];
    }

    public static function cachePath(): string
    {
        return rtrim((string) (config('database.connections.legacy.files_path') ?: storage_path('app/legacy-files')), '/');
    }

    protected function handle(): void
    {
        $only = array_filter((array) config('legacy.only', []));
        $modules = app(ModuleRegistry::class);

        // 1. What is wanted: [table, legacy id, path, model, field, title (files of a row) or false (one file)]
        $wanted = [];
        foreach (self::SOURCES as $group => $sources) {
            if ($only !== [] && ! in_array($group, $only, true)) {
                continue;
            }
            foreach ($sources as $source) {
                [$slug, $table, $column, $model, $field] = $source;
                [$parent, $parentTable, $title] = [$source[5] ?? null, $source[6] ?? null, $source[7] ?? null];
                $legacy = Schema::connection('legacy');
                if (! class_exists($model) || ! $modules->isActive($slug) || ! $legacy->hasColumn($table, $column)) {
                    $this->report->review("{$table}.{$column}: module {$slug} is not active here — skipped.");

                    continue;
                }
                $columns = array_values(array_filter(['id', $column, $parent, $title]));
                $rows = $this->rows($table, fn ($q) => $q->select($columns)->whereNotNull($column)->where($column, '<>', '')
                    ->when($legacy->hasColumn($table, 'deleted_at'), fn ($q) => $q->whereNull('deleted_at')));
                foreach ($rows as $row) {
                    $path = Normalizer::filePath($row->{$column});
                    if ($path === null || ! preg_match('/\.[a-z0-9]{2,5}$/i', $path)) {
                        $this->report->skipped($table, "{$column}: not a file (font icon, other site …)");

                        continue;
                    }
                    $wanted[] = $parent
                        ? [$parentTable, $row->{$parent}, $path, $model, $field, (string) Normalizer::text($title ? $row->{$title} : null)]
                        : [$table, $row->id, $path, $model, $field, false];
                }
            }
        }

        // 2. Into the cache
        $paths = array_values(array_unique(array_column($wanted, 2)));
        $missing = array_values(array_filter($paths, fn (string $p): bool => ! is_file($this->cached($p))));
        if ($missing !== [] && config('legacy.download_files')) {
            $failed = $this->download($missing);
            foreach ($failed as $path => $why) {
                $this->report->review("Not downloaded ({$why}): {$path}");
            }
        } elseif ($missing !== []) {
            $this->report->review(count($missing).' files are not in the cache ('.self::cachePath().'): run again with --download-files.');
        }

        // 3. Each file once into the Media Library, then into the record's field
        $many = [];   // record key => [record, field, table, media id => meta]
        foreach ($wanted as [$table, $id, $path, $model, $field, $title]) {
            $record = $this->ids->record($table, $id, $model);
            if ($record === null) {
                $this->report->skipped("{$table} → media", 'row not migrated');

                continue;
            }
            $item = $this->item($path, $table);
            if ($item === null) {
                $this->report->skipped("{$table} → media", 'file missing or refused');

                continue;
            }
            if ($title !== false) {   // one of the row's files: all together below
                $key = $record::class.':'.$record->getKey().':'.$field;
                $many[$key] ??= [$record, $field, $table, []];
                $many[$key][3][$item->id] = $title !== '' ? ['title' => ['en' => $title]] : [];

                continue;
            }
            if ($record->mediaIdsIn($field) === []) {   // a picture chosen by hand stays
                $record->syncMedia($field, [$item->id]);
                $this->report->saved("{$table} → media", true);
            } else {
                $this->report->saved("{$table} → media", false);
            }
        }
        foreach ($many as [$record, $field, $table, $files]) {
            if ($record->mediaIdsIn($field) === []) {   // files added by hand stay
                $record->syncMedia($field, array_keys($files), array_filter($files));
                $this->report->saved("{$table} → media", true);
            } else {
                $this->report->saved("{$table} → media", false);
            }
        }

        $this->report->review('Image sizes are made by the queue: run php artisan queue:work --stop-when-empty (until then pages show the original).');
    }

    /** The Media Library item for a legacy file: from the map, else one with the same content, else a new one. */
    protected function item(string $path, string $table): ?MediaItem
    {
        if ($item = $this->ids->record(IdMap::FILES, $path, MediaItem::class)) {
            return $item;
        }
        $file = $this->cached($path);
        if (! is_file($file)) {
            return null;
        }

        // The same content under another path: by the cached file's hash (the library may rewrite what it keeps, EXIF)
        $hash = 'sha256:'.hash_file('sha256', $file);
        $item = $this->ids->record(IdMap::FILES, $hash, MediaItem::class) ?? MediaItem::query()->where('file_hash', substr($hash, 7))->first();
        if ($item === null) {
            // The library moves and may rewrite the file it gets (EXIF), so it gets a copy; the cache stays
            $copy = tempnam(sys_get_temp_dir(), 'legacy');
            File::copy($file, $copy);
            $name = $this->shrink($copy, $path) ? pathinfo($path, PATHINFO_FILENAME).'.jpg' : basename($path);
            try {
                $item = app(MediaLibrary::class)->store(new UploadedFile($copy, $name, null, null, true), [
                    'folder_id' => $this->folder($table), 'scope_type' => 'site', 'scope_id' => 0, 'is_shared' => true,
                ]);
                $this->report->saved(IdMap::FILES, true);
            } catch (MediaRejected $e) {
                @unlink($copy);
                $this->report->review("Refused by the Media Library: {$path} — {$e->getMessage()}");

                return null;
            }
        }
        $this->ids->remember(IdMap::FILES, $path, $item);
        $this->ids->remember(IdMap::FILES, $hash, $item);

        return $item;
    }

    /**
     * A JPEG / PNG / WebP photo above the Media Library's size limit becomes a JPEG of at most
     * MAX_SIDE pixels (the cache keeps the original); anything else is left for the library to judge.
     */
    protected function shrink(string $file, string $path): bool
    {
        $limit = app(MediaLibrary::class)->maxUploadKilobytes() * 1024;
        $image = filesize($file) > $limit && preg_match('/\.(jpe?g|png|webp)$/i', $path) ? @imagecreatefromstring((string) file_get_contents($file)) : false;
        if (! $image) {
            return false;
        }
        $scale = min(1, self::MAX_SIDE / max(imagesx($image), imagesy($image)));
        $resized = imagescale($image, (int) round(imagesx($image) * $scale), (int) round(imagesy($image) * $scale), IMG_BICUBIC);
        imagejpeg($resized, $file, 85);
        clearstatcache(true, $file);
        $this->report->review("Made smaller to fit the Media Library ({$this->kb(filesize($file))} KB JPEG): {$path}");

        return true;
    }

    protected function kb(int|false $bytes): int
    {
        return (int) round((int) $bytes / 1024);
    }

    /** "Legacy import / {table}" in the Media Library, so the imported files can be found together */
    protected function folder(string $table): int
    {
        if (! isset($this->folders[$table])) {
            $root = MediaFolder::query()->firstOrCreate(['parent_id' => null, 'name' => 'Legacy import', 'scope_type' => 'site', 'scope_id' => 0]);
            $this->folders[$table] = MediaFolder::query()->firstOrCreate(['parent_id' => $root->id, 'name' => $table, 'scope_type' => 'site', 'scope_id' => 0])->id;
        }

        return $this->folders[$table];
    }

    protected function cached(string $path): string
    {
        return self::cachePath().'/'.$path;
    }

    /** The old app served some files at their path ("images/…") and its upload disk under "storage/" */
    protected static function url(string $path, bool $storage = false): string
    {
        return Normalizer::FILE_BASE.($storage ? 'storage/' : '').implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    /**
     * Downloads into the cache, PARALLEL at a time; a file is written only when complete.
     *
     * @param  list<string>  $paths
     * @return array<string, string> path => why it failed
     */
    protected function download(array $paths): array
    {
        $failed = [];
        $tries = array_fill_keys($paths, 0);
        $storage = [];   // path => already tried under storage/
        $queue = $paths;
        $multi = curl_multi_init();
        $running = [];

        $start = function (string $path) use ($multi, &$running, &$tries, &$storage): void {
            $tries[$path]++;
            $handle = curl_init(self::url($path, isset($storage[$path])));
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 120,
                CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_USERAGENT => 'UWMS-Migration',
            ]);
            curl_multi_add_handle($multi, $handle);
            $running[(int) $handle] = [$handle, $path];
        };

        while ($queue !== [] || $running !== []) {
            while ($queue !== [] && count($running) < self::PARALLEL) {
                $start(array_shift($queue));
            }
            curl_multi_exec($multi, $active);
            curl_multi_select($multi, 1.0);

            while ($done = curl_multi_info_read($multi)) {
                [$handle, $path] = $running[(int) $done['handle']];
                unset($running[(int) $done['handle']]);
                $code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
                $type = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
                $body = curl_multi_getcontent($handle);
                curl_multi_remove_handle($multi, $handle);
                curl_close($handle);

                if ($code === 200 && $body !== '' && ! str_starts_with($type, 'text/html')) {
                    File::ensureDirectoryExists(dirname($this->cached($path)));
                    File::put($this->cached($path), $body);
                } elseif ($code === 404 && ! isset($storage[$path]) && ! str_starts_with($path, 'storage/')) {
                    $storage[$path] = true;   // the same path on the old upload disk
                    $tries[$path] = 0;
                    $queue[] = $path;
                } elseif ($code !== 404 && $tries[$path] < self::TRIES) {
                    $queue[] = $path;   // try again later
                } else {
                    $failed[$path] = $code === 404 ? 'not found' : "HTTP {$code}";
                }
            }
        }
        curl_multi_close($multi);

        return $failed;
    }
}
