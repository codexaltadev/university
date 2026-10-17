<?php

namespace Modules\Partners\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Modules\Partners\Database\Factories\PartnerFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAttachable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A partner (M-37, DB-06 v1.3 §8, SRS Services FR-PRT-01..05): industry, sponsor, membership,
 * discount or other; logo from the Media Library; a discount has its value, terms, category and
 * validity (hidden after valid_until, FR-PRT-04); "Show in" for a lab / department / program's partners.
 */
class Partner extends BaseModel implements Scopeable
{
    use AppliesUserScope;

    /** @use HasFactory<PartnerFactory> */
    use HasFactory;

    use HasMediaUsage;      // logo
    use HasSortOrder;
    use HasTaxonomy;        // partners.discount-category
    use HasTranslations;
    use IsAttachable;

    public const PATH = 'partners';

    public const DISCOUNT_PATH = 'student-discounts';

    public const CATEGORY = 'partners.discount-category';

    public const TYPES = ['industry', 'sponsor', 'membership', 'discount', 'other'];

    public static string $titleAttribute = 'name';

    protected $table = 'partners_partners';

    protected $fillable = ['partner_type', 'name', 'website_url', 'description', 'discount_value', 'discount_terms', 'valid_from', 'valid_until', 'is_international', 'sort_order', 'is_active'];

    protected $attributes = ['partner_type' => 'industry', 'is_international' => false];

    public array $translatable = ['name', 'description', 'discount_value', 'discount_terms'];

    protected function casts(): array
    {
        return ['valid_from' => 'date', 'valid_until' => 'date', 'is_international' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Partner $partner): void {
            if (! in_array($partner->partner_type, self::TYPES, true)) {
                throw ValidationException::withMessages(['partner_type' => __('partners::partners.errors.type')]);
            }
            if (filled($partner->website_url) && ! preg_match('#^https?://\S+$#i', (string) $partner->website_url)) {
                throw ValidationException::withMessages(['website_url' => __('partners::partners.errors.url')]);
            }
            if ($partner->valid_from && $partner->valid_until && $partner->valid_until->lt($partner->valid_from)) {
                throw ValidationException::withMessages(['valid_until' => __('partners::partners.errors.dates')]);
            }
        });
    }

    /** Still valid today (FR-PRT-04): not before valid_from, not after valid_until. */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('valid_from'))->orWhereDate($this->qualifyColumn('valid_from'), '<=', $today))
            ->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('valid_until'))->orWhereDate($this->qualifyColumn('valid_until'), '>=', $today));
    }

    public function scopeOfType(Builder $query, string|array $type): Builder
    {
        return $query->whereIn($this->qualifyColumn('partner_type'), (array) $type);
    }

    public function isDiscount(): bool
    {
        return $this->partner_type === 'discount';
    }

    /** Where the partner's card goes: its website. */
    public function url(?string $locale = null): string
    {
        return (string) ($this->website_url ?: '/'.($this->isDiscount() ? self::DISCOUNT_PATH : self::PATH));
    }

    protected static function newFactory(): PartnerFactory
    {
        return PartnerFactory::new();
    }
}
