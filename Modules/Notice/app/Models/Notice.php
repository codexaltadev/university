<?php

namespace Modules\Notice\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Modules\Notice\Database\Factories\NoticeFactory;
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
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * An official notice (M-20, DB-05 §5 v1.4, SRS Content FR-NOT-01..07): exam, admission, holiday,
 * result, tender … with a reference number, files with their own titles, pinned / urgent.
 * active_at schedules it; expires_at takes it off the board but it stays in the archive and on
 * its own page (FR-NOT-03). Where it shows is "Show in" and the main site (Admin, Q-26).
 */
class Notice extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // entity users: notices shown in their entities
    use HasActiveAt;

    /** @use HasFactory<NoticeFactory> */
    use HasFactory;

    use HasMediaUsage;      // attachments (with meta.title)
    use HasSeo;
    use HasSlug;            // /notices/{slug}
    use HasTaxonomy;        // notice.category
    use HasTranslations;
    use HasViewCount;
    use IsAttachable;
    use IsSearchable;

    public const PATH = 'notices';

    public const CATEGORY = 'notice.category';

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'notice_notices';

    protected $fillable = ['title', 'reference_no', 'body', 'is_pinned', 'is_urgent', 'active_at', 'expires_at', 'is_active'];

    public array $translatable = ['title', 'body'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean', 'is_urgent' => 'boolean', 'expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Notice $notice): void {
            $notice->active_at ??= now();   // every notice has a date to show and sort by
        });
        static::saving(function (Notice $notice): void {
            if ($notice->expires_at && $notice->active_at && $notice->expires_at->lte($notice->active_at)) {
                throw ValidationException::withMessages(['expires_at' => __('notice::notice.errors.expiry')]);
            }
        });
    }

    /** Still on the Notice Board (not past its expiry). */
    public function scopeOnBoard(Builder $query): Builder
    {
        $column = $this->qualifyColumn('expires_at');

        return $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, '>', now()));
    }

    /** Off the board, kept in the archive (FR-NOT-03). */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('expires_at'), '<=', now());
    }

    /** Pinned first while pinned (AC-NOT-02), then newest. */
    public function scopeBoardOrder(Builder $query): Builder
    {
        return $query->orderByDesc($this->qualifyColumn('is_pinned'))->orderByDesc($this->qualifyColumn('active_at'))->orderByDesc($this->getQualifiedKeyName());
    }

    public function isOnBoard(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /** "New" for a few days after it shows (FR-NOT-07; Settings › Notice). */
    public function isNew(): bool
    {
        $days = (int) settings('notice.new_days', 7);

        return $days > 0 && $this->active_at !== null && $this->active_at->gt(now()->subDays($days));
    }

    /** /notices/{slug} on the main site; a notice only shown in entities lives under the first one's microsite. */
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

    /** Search: the title and the reference number (FR-NOT-04), with the text. */
    public function toSearchDocument(string $locale): ?array
    {
        $title = $this->getTranslation('title', $locale, false);
        if (blank($title)) {
            return null;
        }

        return ['title' => $title, 'body' => trim(($this->reference_no ? $this->reference_no.' ' : '').static::plainText($this->getTranslation('body', $locale, false) ?: ''))];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'Article',
            'headline' => $this->getTranslation('title', $locale, true),
            'datePublished' => $this->active_at?->toIso8601String(),
            'dateModified' => $this->updated_at?->toIso8601String(),
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
        ]);
    }

    protected static function newFactory(): NoticeFactory
    {
        return NoticeFactory::new();
    }
}
