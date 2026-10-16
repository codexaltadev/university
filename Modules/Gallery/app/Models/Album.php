<?php

namespace Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Gallery\Database\Factories\AlbumFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasActiveAt;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\HasViewCount;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Media\MediaItem;
use Platform\Media\MediaUsage;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * A photo / video album (M-22, DB-05 §7 v1.6, SRS Content FR-GAL-01..07). Its pictures and
 * uploaded videos stay in the Media Library: the album only uses them (field "items", in its
 * order, with an optional caption of its own — BRL-GAL-01); link videos are rows of
 * gallery_videos. Series (documentary, lecture series), an event, a date; where it shows is
 * "Show in" and the main site.
 */
class Album extends BaseModel implements Scopeable
{
    use AppliesUserScope;
    use HasActiveAt;

    /** @use HasFactory<AlbumFactory> */
    use HasFactory;

    use HasMediaUsage;      // items, cover
    use HasSeo;
    use HasSlug;            // /gallery/{slug}
    use HasSortOrder;
    use HasTaxonomy;        // gallery.category
    use HasTranslations;
    use HasViewCount;
    use IsAttachable;
    use IsSearchable;

    public const PATH = 'gallery';

    public const CATEGORY = 'gallery.category';

    public const ITEMS = 'items';

    public const SERIES = ['documentary', 'lecture_series'];

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'gallery_albums';

    protected $fillable = ['title', 'description', 'series_type', 'event_id', 'occurred_on', 'sort_order', 'active_at', 'is_active'];

    public array $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return ['occurred_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Album $album): void {
            if ($album->series_type !== null && ! in_array($album->series_type, self::SERIES, true)) {
                throw ValidationException::withMessages(['series_type' => __('gallery::gallery.errors.series')]);
            }
        });
        static::creating(function (Album $album): void {
            $album->active_at ??= now();
        });
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'album_id')->orderBy('sort_order')->orderBy('id');
    }

    /** Newest first: the date of the pictures, else when it started to show. */
    public function scopeNewest(Builder $query): Builder
    {
        return $query->reorder()->orderByRaw('COALESCE('.$this->qualifyColumn('occurred_on').', DATE('.$this->qualifyColumn('active_at').')) DESC')->orderByDesc($this->getQualifiedKeyName());
    }

    /**
     * The album's media in its order: each with its item and the caption for this album
     * (its own, else the media item's).
     *
     * @return Collection<int, array{item: MediaItem, caption: ?string, usage: MediaUsage}>
     */
    public function entries(?string $kind = null): Collection
    {
        $locale = app()->getLocale();

        return $this->mediaUsagesIn(self::ITEMS)
            ->filter(fn (MediaUsage $u): bool => $kind === null || $u->item->kind === $kind)
            ->map(fn (MediaUsage $u): array => [
                'item' => $u->item,
                'caption' => ($u->meta['caption'][$locale] ?? null) ?: ($u->item->getTranslation('caption', $locale, true) ?: null),
                'usage' => $u,
            ])->values();
    }

    public function coverUrl(string $size = 'medium'): ?string
    {
        $cover = $this->firstMedia('cover')
            ?? $this->mediaUsagesIn(self::ITEMS)->first(fn (MediaUsage $u): bool => $u->item->kind === 'image')?->item;

        return $cover?->url($size) ?? $this->videos->first()?->thumbnailUrl();
    }

    public function photoCount(): int
    {
        return $this->mediaUsages()->where('field', self::ITEMS)->whereHas('item', fn (Builder $q) => $q->where('kind', 'image'))->count();
    }

    /** /gallery/{slug} on the main site; an album only shown in entities lives under the first one's microsite. */
    public function url(?string $locale = null): string
    {
        $slug = $this->slug($locale) ?? (string) $this->getKey();
        if (! $this->isOnMainSite()) {
            foreach ($this->targetKeys() as $key) {
                [$type, $id] = explode(':', $key, 2);
                $model = Relation::getMorphedModel($type);
                $entity = $model ? $model::query()->withoutGlobalScopes()->find($id) : null;
                if ($entity && ($base = app(MicrositeResolver::class)->urlFor($entity, $locale))) {
                    return rtrim($base, '/').'/'.self::PATH.'/'.$slug;
                }
            }
        }

        return '/'.self::PATH.'/'.$slug;
    }

    protected function searchBodyAttributes(): array
    {
        return ['description'];
    }

    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'ImageGallery',
            'name' => $this->getTranslation('title', $locale, true),
            'description' => $this->getTranslation('description', $locale, true) ?: null,
            'image' => $this->coverUrl('large'),
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
        ]);
    }

    protected static function newFactory(): AlbumFactory
    {
        return AlbumFactory::new();
    }
}
