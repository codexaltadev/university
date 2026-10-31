<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Page\Blocks\PageBlocks;
use Modules\Page\Database\Factories\PageFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasExpiry;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasOwner;
use Platform\Concerns\HasPublishDate;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\IsSearchable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * A page of the main site or of an entity's microsite (C-11, page_pages): About,
 * History, Lab Facilities … Content is a list of blocks per language. Address:
 * owner's microsite + parent pages + own slug, e.g. /department/cse/about/history.
 */
class Page extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // entity users: pages of their entities only (owner path)
    use HasExpiry;

    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use HasMediaUsage;      // featured_image, attachments, and pictures / files inside blocks ("content")
    use HasOwner;
    use HasPublishDate;
    use HasSeo;             // SEO panel (FR-GEN-70/71)
    use HasSlug;
    use HasTranslations;
    use IsSearchable;       // website search (FR-SRCH-01)

    /** FR-PAGE-04 v0.3: a page's layout; "with-sidebar" keeps its key but is the left sidebar. */
    public const TEMPLATES = ['default', 'full-width', 'with-sidebar', 'right-sidebar', 'landing'];

    /** The layouts with a sidebar (its menu and Quick Links). */
    public const SIDEBAR_TEMPLATES = ['with-sidebar', 'right-sidebar'];

    public static string $titleAttribute = 'title';

    protected $table = 'page_pages';

    protected $fillable = ['parent_id', 'title', 'excerpt', 'content', 'template', 'sidebar_menu_id', 'quick_links', 'published_at', 'expires_at', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'excerpt', 'content'];

    protected function casts(): array
    {
        return [...parent::casts(), 'quick_links' => 'array'];
    }

    protected string $slugSource = 'title';

    protected array $guardedChildren = ['children'];

    protected static function booted(): void
    {
        static::creating(function (Page $page): void {
            $page->sort_order ??= (int) static::withTrashed()->where('owner_type', $page->owner_type)->where('owner_id', $page->owner_id)
                ->where('parent_id', $page->parent_id)->max('sort_order') + 1;
        });

        // Pictures and files chosen inside blocks count as usage (FR-MEDIA-09)
        static::saved(function (Page $page): void {
            if ($page->wasChanged('content') || $page->wasRecentlyCreated) {
                $page->syncMedia('content', PageBlocks::mediaIds($page->getTranslations('content')));
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Public address (FR-PAGE-03/05, AC-PAGE-01). */
    /** The page's sidebar menu: its own, else the nearest parent's; null = the owner's "sidebar" menu. */
    public function sidebarMenuId(): ?int
    {
        for ($page = $this; $page !== null; $page = $page->parent) {
            if ($page->sidebar_menu_id) {
                return (int) $page->sidebar_menu_id;
            }
        }

        return null;
    }

    /** @return list<Page> the parents, top first (breadcrumb) */
    public function ancestors(): array
    {
        $ancestors = [];
        for ($page = $this->parent; $page !== null; $page = $page->parent) {
            array_unshift($ancestors, $page);
        }

        return $ancestors;
    }

    public function url(?string $locale = null): string
    {
        $slugs = [];

        for ($page = $this; $page !== null; $page = $page->parent) {
            array_unshift($slugs, $page->slug($locale));
        }

        return $this->ownerUrlPrefix($locale).'/'.implode('/', array_filter($slugs));
    }

    /** Search: title, summary and the text of every block in that language. */
    public function toSearchDocument(string $locale): ?array
    {
        $title = $this->getTranslation('title', $locale, false);

        return blank($title) ? null : [
            'title' => $title,
            'body' => static::plainText([
                $this->getTranslation('excerpt', $locale, false),
                array_column((array) $this->getTranslation('content', $locale, false), 'data'),   // block fields, not block types
            ]),
        ];
    }

    /** "Only in this department" (FR-SRCH-06): the entity that owns the page. */
    public function searchOwnerKey(): ?string
    {
        return $this->isMainSiteOwned() ? null : $this->ownerKey();
    }

    /** FR-SEO-08: a WebPage in schema.org terms. */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'WebPage',
            'name' => $this->getTranslation('title', $locale, true),
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
            'dateModified' => $this->updated_at?->toAtomString(),
        ]);
    }

    /** @return list<int> this page and every page below it */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $level = [$this->id];

        while ($level !== []) {
            $level = static::withTrashed()->whereIn('parent_id', $level)->pluck('id')->all();
            $ids = [...$ids, ...$level];
        }

        return $ids;
    }

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }
}
