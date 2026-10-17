<?php

namespace Modules\International\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Modules\International\Database\Factories\InstitutionFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A partner institution abroad (M-35, DB-06 v1.3 §6, FR-INTL-01..04): country, agreement, signed /
 * expires, logo from the Media Library; featured = a Highlight; "Show in" a department or faculty.
 * (Table international_partners; called Institution so its permissions differ from M-37 Partners.)
 */
class Institution extends BaseModel implements Scopeable
{
    use AppliesUserScope;

    /** @use HasFactory<InstitutionFactory> */
    use HasFactory;

    use HasMediaUsage;      // logo
    use HasSortOrder;
    use HasTranslations;
    use IsAttachable;
    use IsSearchable;

    public const AGREEMENTS = ['mou', 'moa', 'exchange', 'dual_degree', 'research', 'other'];

    public static string $titleAttribute = 'name';

    protected $table = 'international_partners';

    protected $fillable = ['name', 'country_id', 'website_url', 'agreement_type', 'signed_on', 'expires_on', 'description', 'sort_order', 'is_active'];

    protected $attributes = ['agreement_type' => 'mou'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return ['signed_on' => 'date', 'expires_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Institution $i): void {
            if (! in_array($i->agreement_type, self::AGREEMENTS, true)) {
                throw ValidationException::withMessages(['agreement_type' => __('international::international.errors.agreement')]);
            }
            if (filled($i->website_url) && ! preg_match('#^https?://\S+$#i', (string) $i->website_url)) {
                throw ValidationException::withMessages(['website_url' => __('international::international.errors.url')]);
            }
            if ($i->signed_on && $i->expires_on && $i->expires_on->lt($i->signed_on)) {
                throw ValidationException::withMessages(['expires_on' => __('international::international.errors.dates')]);
            }
        });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
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
        return '/international/partners?q='.rawurlencode((string) $this->name);
    }

    protected function searchBodyAttributes(): array
    {
        return ['description'];
    }

    protected static function newFactory(): InstitutionFactory
    {
        return InstitutionFactory::new();
    }
}
