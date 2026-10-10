<?php

namespace Modules\Scholarship\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Modules\Scholarship\Database\Factories\ScholarshipFactory;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsSearchable;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * A scholarship (M-10, DB-03 §11.3 v1.4), or one part of it — a call or opportunity under
 * a scholarship (Erasmus → "Spring 2025 exchange at …"); one level only. Shown on the
 * website when active; a part past its deadline shows as closed. Media: image, attachments.
 */
class Scholarship extends BaseModel
{
    /** @use HasFactory<ScholarshipFactory> */
    use HasFactory;

    use HasMediaUsage;      // image, attachments
    use HasSeo;
    use HasSlug;            // /scholarships/{slug}
    use HasSortOrder;
    use HasTaxonomy;        // scholarship.category, on top-level scholarships
    use HasTranslations;
    use IsSearchable;

    public const PATH = 'scholarships';

    public const CATEGORY = 'scholarship.category';

    public static string $titleAttribute = 'title';

    protected $table = 'scholarship_scholarships';

    protected $fillable = ['parent_id', 'title', 'summary', 'details', 'eligibility', 'how_to_apply', 'apply_url', 'deadline_at', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'summary', 'details', 'eligibility', 'how_to_apply'];

    protected string $slugSource = 'title';

    protected function casts(): array
    {
        return ['parent_id' => 'integer', 'deadline_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // One level: a part has no parts, a scholarship with parts is not someone's part
        static::saving(function (Scholarship $scholarship): void {
            if ($scholarship->parent_id === null) {
                return;
            }
            if ($scholarship->exists && $scholarship->parts()->exists()) {
                throw ValidationException::withMessages(['parent_id' => __('scholarship::scholarship.errors.has_parts')]);
            }
            if ((int) $scholarship->parent_id === (int) $scholarship->getKey() || static::query()->withoutGlobalScopes()->whereKey($scholarship->parent_id)->whereNotNull('parent_id')->exists()) {
                throw ValidationException::withMessages(['parent_id' => __('scholarship::scholarship.errors.one_level')]);
            }
        });

        // A scholarship with parts is not removed (FR-GEN-08)
        static::deleting(function (Scholarship $scholarship): void {
            if ($scholarship->parts()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['parent_id' => __('scholarship::scholarship.errors.delete_parts_first')]);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull($this->qualifyColumn('parent_id'));
    }

    public function isClosed(): bool
    {
        return $this->deadline_at !== null && $this->deadline_at->isPast();
    }

    /** /scholarships/erasmus-scholarship — parts too, their trail names the parent. */
    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'/'.$this->slug($locale);
    }

    protected function searchBodyAttributes(): array
    {
        return ['summary', 'details', 'eligibility'];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'MonetaryGrant',
            'name' => $this->getTranslation('title', $locale, true),
            'description' => filled($text = $this->getTranslation('summary', $locale, true) ?: $this->getTranslation('details', $locale, true))
                ? mb_substr(trim(strip_tags($text)), 0, 300) : null,
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
        ]);
    }

    protected static function newFactory(): ScholarshipFactory
    {
        return ScholarshipFactory::new();
    }
}
