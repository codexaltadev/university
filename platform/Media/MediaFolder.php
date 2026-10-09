<?php

namespace Platform\Media;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Models\BaseModel;

/**
 * Media Library folder (core_media_folders, DB-02 §3.1), nested (FR-MEDIA-08).
 * A folder with files or sub-folders cannot be deleted.
 */
class MediaFolder extends BaseModel
{
    public static string $titleAttribute = 'name';

    protected $table = 'core_media_folders';

    protected $fillable = ['parent_id', 'name', 'scope_type', 'scope_id', 'is_active'];

    protected array $guardedChildren = ['children', 'items'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MediaItem::class, 'folder_id');
    }

    /** The main site's folders and those of the user's entities (all for unrestricted users). */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $scopes = $user ? MediaItem::restrictedScopesFor($user) : null;

        return $query->when($scopes !== null, fn (Builder $q) => $q->where(function (Builder $w) use ($scopes): void {
            $w->where('scope_type', 'site');
            foreach ($scopes as $type => $ids) {
                $w->orWhere(fn (Builder $s) => $s->where('scope_type', $type)->whereIn('scope_id', $ids));
            }
        }));
    }

    /** @return list<MediaFolder> from the top folder down to this one (breadcrumb) */
    public function trail(): array
    {
        return [...($this->parent?->trail() ?? []), $this];
    }

    /** "Parent / Child" for selects. */
    public function path(): string
    {
        return $this->parent ? $this->parent->path().' / '.$this->name : $this->name;
    }

    /**
     * id => path, for folder selects: the main site's folders and those of the
     * user's entities (everything for unrestricted users).
     *
     * @return array<int, string>
     */
    public static function options(?User $user = null): array
    {
        return static::query()
            ->visibleTo($user)
            ->with('parent.parent.parent')->get()
            ->mapWithKeys(fn (MediaFolder $folder): array => [$folder->id => $folder->path()])
            ->sort()
            ->all();
    }
}
