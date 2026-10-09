<?php

namespace Platform\Media;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Platform\Localization\Languages;
use Platform\Models\UserScope;
use Platform\Settings\Settings;

/**
 * Puts files into the Media Library (C-07): checks (FileGuard), SVG cleaning,
 * EXIF removal, duplicate detection, scope, then spatie stores the file and
 * queues the conversions. Rejections throw MediaRejected with a readable message.
 */
class MediaLibrary
{
    public function __construct(protected FileGuard $guard, protected SvgSanitizer $svg) {}

    /**
     * @param  array<string, mixed>  $attributes  folder_id, is_shared, is_private, scope_type, scope_id, title, alt_text …
     *
     * @throws MediaRejected
     */
    public function store(UploadedFile $file, array $attributes = [], ?User $by = null): MediaItem
    {
        $by ??= auth()->user();
        $name = $file->getClientOriginalName();
        [$mime, $facts] = $this->prepare($file->getRealPath(), $name, (int) $file->getSize());

        [$scopeType, $scopeId] = isset($attributes['scope_type'])
            ? [$attributes['scope_type'], (int) ($attributes['scope_id'] ?? 0)]
            : $this->defaultScope($by);

        return DB::transaction(function () use ($file, $attributes, $name, $mime, $facts, $scopeType, $scopeId): MediaItem {
            $item = MediaItem::query()->create([
                'title' => $attributes['title'] ?? [app(Languages::class)->default() => pathinfo($name, PATHINFO_FILENAME)],
                'alt_text' => $this->texts($attributes['alt_text'] ?? null),
                'folder_id' => $attributes['folder_id'] ?? null,
                'is_shared' => (bool) ($attributes['is_shared'] ?? false),
                'is_private' => (bool) ($attributes['is_private'] ?? false),
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'kind' => MediaItem::kindOf($mime),
                'mime_type' => $mime,
                ...$facts,
            ]);

            $this->attach($item, $file, $name);

            return $item;
        });
    }

    /** New file, same item and permalink; every usage keeps working (FR-MEDIA-07). */
    public function replace(MediaItem $item, UploadedFile $file): MediaItem
    {
        $name = $file->getClientOriginalName();
        [$mime, $facts] = $this->prepare($file->getRealPath(), $name, (int) $file->getSize());

        $item->update(['kind' => MediaItem::kindOf($mime), 'mime_type' => $mime, ...$facts]);
        $this->attach($item, $file, $name);

        return $item->refresh();
    }

    /** {"en": "…", "bn": ""} → only the filled languages, or null. */
    protected function texts(?array $texts): ?array
    {
        $texts = array_filter((array) $texts, fn ($text): bool => filled($text));

        return $texts === [] ? null : $texts;
    }

    /** FR-MEDIA-16: an existing, visible item with the same content. */
    public function duplicateOf(UploadedFile $file, ?User $user = null): ?MediaItem
    {
        return MediaItem::query()->visibleTo($user ?? auth()->user())
            ->where('file_hash', hash_file('sha256', $file->getRealPath()))
            ->first();
    }

    /** BRL-MEDIA-01: the uploader's first own entity, or the main site. */
    public function defaultScope(?User $user): array
    {
        $scope = $user ? UserScope::query()->where('user_id', $user->id)->orderBy('created_at')->orderBy('scopeable_type')->orderBy('scopeable_id')->first() : null;

        return $scope ? [$scope->scopeable_type, (int) $scope->scopeable_id] : ['site', 0];
    }

    /** @return list<string> extensions allowed now (Settings › Media) */
    public function allowedExtensions(): array
    {
        return (array) app(Settings::class)->get('media.allowed_types', []);
    }

    public function maxUploadKilobytes(): int
    {
        return (int) app(Settings::class)->get('media.max_upload_mb', 10) * 1024;
    }

    /**
     * Check and clean the file in place.
     *
     * @return array{0: string, 1: array<string, mixed>} real MIME type, column values
     */
    protected function prepare(string $path, string $name, int $size): array
    {
        if ($size > $this->maxUploadKilobytes() * 1024) {
            throw new MediaRejected(__('platform::media.rejected.size', ['name' => $name, 'max' => $this->maxUploadKilobytes() / 1024]));
        }

        $mime = $this->guard->check($path, $name, $this->allowedExtensions());

        if ($mime === 'image/svg+xml') {
            $this->svg->clean($path, $name);
        }

        if ($mime === 'image/jpeg') {
            $this->stripExif($path);
        }

        clearstatcache(true, $path);
        $dimensions = str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml' ? @getimagesize($path) : false;

        return [$mime, [
            'size_bytes' => filesize($path),
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'file_hash' => hash_file('sha256', $path),
        ]];
    }

    protected function attach(MediaItem $item, UploadedFile $file, string $name): void
    {
        config(['media-library.max_file_size' => $this->maxUploadKilobytes() * 1024]);

        // BRL-MEDIA-03: slug file name; spatie keeps each file in its own folder, so names never collide
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $fileName = (Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'file').'.'.$extension;

        $item->addMedia($file)
            ->usingName(pathinfo($name, PATHINFO_FILENAME))
            ->usingFileName($fileName)
            ->toMediaCollection(MediaItem::COLLECTION, $item->is_private ? 'local' : config('media-library.disk_name'));

        $item->unsetRelation('media');
    }

    /** FR-MEDIA-11: no location (or any other EXIF) in published photos; orientation applied first. */
    protected function stripExif(string $path): void
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($path) : false;

        if (! $exif) {
            return;
        }

        $image = @imagecreatefromjpeg($path);

        if (! $image) {
            return;
        }

        $image = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        imagejpeg($image, $path, 92);
        imagedestroy($image);
    }
}
