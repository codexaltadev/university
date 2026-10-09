<?php

namespace Platform\Media;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;
use Platform\Models\BaseModel;
use Platform\Scope\ScopeResolver;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * One file of the Media Library (core_media_items, DB-02 §3.2). The file itself is
 * spatie's "media" row in collection "file"; content refers to the item through
 * core_media_usages (HasMediaUsage), never to the file. Soft delete keeps the file.
 */
class MediaItem extends BaseModel implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

    public const KINDS = ['image', 'document', 'video', 'audio', 'archive', 'other'];

    public const COLLECTION = 'file';

    public static string $titleAttribute = 'title';

    protected $table = 'core_media_items';

    protected $fillable = [
        'folder_id', 'title', 'alt_text', 'caption', 'description', 'kind', 'mime_type', 'size_bytes',
        'width', 'height', 'file_hash', 'scope_type', 'scope_id', 'is_shared', 'is_private', 'is_active',
    ];

    public array $translatable = ['title', 'alt_text', 'caption', 'description'];

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean', 'is_private' => 'boolean', 'size_bytes' => 'integer',
            'width' => 'integer', 'height' => 'integer', 'usage_count' => 'integer', 'scope_id' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION)->singleFile();
    }

    /** FR-MEDIA-11: thumbnail 150, medium 768, large 1600, all WebP, made on the queue. */
    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media === null || ! str_starts_with((string) $media->mime_type, 'image/') || $media->mime_type === 'image/svg+xml') {
            return;
        }

        $this->addMediaConversion('thumb')->fit(Fit::Crop, 150, 150)->format('webp');
        $this->addMediaConversion('medium')->fit(Fit::Max, 768, 768)->format('webp');
        $this->addMediaConversion('large')->fit(Fit::Max, 1600, 1600)->format('webp');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia(self::COLLECTION);
    }

    public function fileName(): ?string
    {
        return $this->file()?->file_name;
    }

    /**
     * Address of the file (or of a conversion once it exists). Private files only
     * through a signed link that expires (BRL-MEDIA-02).
     */
    public function url(?string $conversion = null): ?string
    {
        $file = $this->file();

        if ($file === null) {
            return null;
        }

        if ($this->is_private) {
            return URL::temporarySignedRoute('media.file', now()->addMinutes(30), ['item' => $this->id, 'name' => $file->file_name]);
        }

        return $conversion && $file->hasGeneratedConversion($conversion) ? $file->getUrl($conversion) : $file->getUrl();
    }

    /** Stable address that survives "replace file" (FR-MEDIA-07): /media/{id}/{file name}. */
    public function permalink(): ?string
    {
        $file = $this->file();

        if ($file === null) {
            return null;
        }

        return $this->is_private ? $this->url() : route('media.file', ['item' => $this->id, 'name' => $file->file_name]);
    }

    public function previewUrl(): ?string
    {
        return $this->kind === 'image' ? $this->url('thumb') : null;
    }

    /**
     * FR-MEDIA-14: Admin (no scope or ManageAll:MediaItem) sees everything; a scoped
     * user sees media of their entities, their own uploads and shared media.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        $scopes = static::restrictedScopesFor($user);

        if ($scopes === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user, $scopes): void {
            $q->where($this->qualifyColumn('created_by'), $user->id)->orWhere($this->qualifyColumn('is_shared'), true);

            foreach ($scopes as $type => $ids) {
                $q->orWhere(fn (Builder $s) => $s->where($this->qualifyColumn('scope_type'), $type)->whereIn($this->qualifyColumn('scope_id'), $ids));
            }
        });
    }

    public function isVisibleTo(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $scopes = static::restrictedScopesFor($user);

        return $scopes === null
            || $this->created_by === $user->id
            || $this->is_shared
            || in_array($this->scope_id, $scopes[$this->scope_type] ?? [], true);
    }

    /** @return array<string, list<int>>|null null = sees everything */
    public static function restrictedScopesFor(User $user): ?array
    {
        if ($user->isSuperAdmin() || $user->can('ManageAll:MediaItem')) {
            return null;
        }

        return app(ScopeResolver::class)->idsByType($user);
    }

    /** image / document / video / audio / archive / other, from the real MIME type. */
    public static function kindOf(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed', 'application/vnd.rar',
                'application/x-7z-compressed', 'application/gzip', 'application/x-tar'], true) => 'archive',
            $mime === 'application/pdf', str_starts_with($mime, 'text/'),
            str_contains($mime, 'word'), str_contains($mime, 'excel'), str_contains($mime, 'spreadsheet'),
            str_contains($mime, 'powerpoint'), str_contains($mime, 'presentation'), str_contains($mime, 'opendocument') => 'document',
            default => 'other',
        };
    }
}
