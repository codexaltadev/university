<?php

namespace Modules\Accreditation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Modules\Accreditation\Database\Factories\AccreditationFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * An accreditation (M-34, DB-06 v1.3 §5, SRS Services FR-ACR-01/02/05): title, agency, text; logo
 * and certificate from the Media Library; awarded / expires; category from accreditation.category;
 * "Show in" a program, department or faculty.
 */
class Accreditation extends BaseModel implements Scopeable
{
    use AppliesUserScope;

    /** @use HasFactory<AccreditationFactory> */
    use HasFactory;

    use HasMediaUsage;      // logo, certificate
    use HasSeo;
    use HasSlug;            // /accreditations/{slug}
    use HasSortOrder;
    use HasTaxonomy;        // accreditation.category
    use HasTranslations;
    use IsAttachable;
    use IsSearchable;

    public const PATH = 'accreditations';

    public const CATEGORY = 'accreditation.category';

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'accreditation_accreditations';

    protected $fillable = ['title', 'agency', 'body', 'website_url', 'awarded_on', 'expires_on', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'agency', 'body'];

    protected function casts(): array
    {
        return ['awarded_on' => 'date', 'expires_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Accreditation $a): void {
            if (filled($a->website_url) && ! preg_match('#^https?://\S+$#i', (string) $a->website_url)) {
                throw ValidationException::withMessages(['website_url' => __('accreditation::accreditation.errors.url')]);
            }
            if ($a->awarded_on && $a->expires_on && $a->expires_on->lt($a->awarded_on)) {
                throw ValidationException::withMessages(['expires_on' => __('accreditation::accreditation.errors.dates')]);
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->lt(today());
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('expires_on'))->orWhereDate($this->qualifyColumn('expires_on'), '>=', today()->toDateString()));
    }

    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'/'.($this->slug($locale) ?? $this->getKey());
    }

    protected function searchBodyAttributes(): array
    {
        return ['agency', 'body'];
    }

    protected static function newFactory(): AccreditationFactory
    {
        return AccreditationFactory::new();
    }
}
