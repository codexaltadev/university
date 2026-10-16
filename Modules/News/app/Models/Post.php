<?php

namespace Modules\News\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\News\Database\Factories\PostFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasActiveAt;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\HasViewCount;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Media\MediaItem;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * A news post (M-18, DB-05 §3 v1.2, SRS Content FR-NEWS-01..06): title, subtitle, excerpt and body
 * per language, featured image and gallery, a video link, author (a user or words). Shown in
 * entities and on the main site (core_relations, the main site is the Admin's — Q-26); "featured"
 * is that place's highlight; active_at schedules it (AC-NEWS-02).
 */
class Post extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // entity users: posts shown in their entities
    use HasActiveAt;        // shown from (scheduling)

    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use HasMediaUsage;      // featured_image, gallery
    use HasSeo;
    use HasSlug;            // /news/{slug}
    use HasTaxonomy;        // news.category, shared tags
    use HasTranslations;
    use HasViewCount;       // P-17
    use IsAttachable;       // "Show in", main site, highlight
    use IsSearchable;

    public const CATEGORY = 'news.category';

    public const PATH = 'news';

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'news_posts';

    protected $fillable = ['title', 'subtitle', 'excerpt', 'body', 'author_user_id', 'author_label', 'video_url', 'active_at', 'is_active'];

    public array $translatable = ['title', 'subtitle', 'excerpt', 'body', 'author_label'];

    protected function casts(): array
    {
        return ['author_user_id' => 'integer'];
    }

    protected static function booted(): void
    {
        // Empty "show from" = now: every post has a date to show and sort by (context lists order by active_at)
        static::creating(function (Post $post): void {
            $post->active_at ??= now();
        });
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /** FR-NEWS-06: the words ("Public Relations Office") when given, else the user's name. */
    public function authorName(?string $locale = null): ?string
    {
        $label = $this->getTranslation('author_label', $locale ?? app()->getLocale(), true);

        return filled($label) ? (string) $label : $this->authorUser?->name;
    }

    public function featuredImage(): ?MediaItem
    {
        return $this->firstMedia('featured_image');
    }

    /** @return Collection<int, MediaItem> */
    public function galleryImages(): Collection
    {
        return $this->mediaIn('gallery');
    }

    /** Newest first by the date it shows from (or was made). */
    public function scopeNewest(Builder $query): Builder
    {
        return $query->orderByRaw('COALESCE('.$this->qualifyColumn('active_at').', '.$this->qualifyColumn('created_at').') DESC')
            ->orderByDesc($this->getQualifiedKeyName());
    }

    /** YouTube / Drive link as an embeddable address, or null. */
    public function videoEmbed(): ?string
    {
        $url = (string) $this->video_url;

        return match (true) {
            (bool) preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $url, $m) => 'https://www.youtube-nocookie.com/embed/'.$m[1],
            (bool) preg_match('~drive\.google\.com/file/d/([\w-]+)~', $url, $m) => 'https://drive.google.com/file/d/'.$m[1].'/preview',
            default => null,
        };
    }

    /** /news/{slug} on the main site; a post only shown in entities lives under the first one's microsite. */
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
        return ['subtitle', 'excerpt', 'body'];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'NewsArticle',
            'headline' => $this->getTranslation('title', $locale, true),
            'description' => $this->getTranslation('excerpt', $locale, true) ?: null,
            'datePublished' => $this->shownAt()?->toIso8601String(),
            'dateModified' => $this->updated_at?->toIso8601String(),
            'author' => ($name = $this->authorName($locale)) ? ['@type' => 'Organization', 'name' => $name] : null,
            'image' => $this->featuredImage()?->url('large'),
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
        ]);
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
