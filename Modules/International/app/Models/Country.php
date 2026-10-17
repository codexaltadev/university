<?php

namespace Modules\International\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\International\Database\Factories\CountryFactory;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** A country (M-35, DB-06 v1.3 §6, FR-INTL-01): name per language, ISO code, flag from the Media Library. */
class Country extends BaseModel
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    use HasMediaUsage;      // flag
    use HasSortOrder;
    use HasTranslations;

    public static string $titleAttribute = 'name';

    protected $table = 'international_countries';

    protected $fillable = ['name', 'iso2', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected static function booted(): void
    {
        static::saving(function (Country $country): void {
            $country->iso2 = Str::upper(trim((string) $country->iso2));
            if (! preg_match('/^[A-Z]{2}$/', $country->iso2)) {
                throw ValidationException::withMessages(['iso2' => __('international::international.errors.iso2')]);
            }
            if (self::query()->whereKeyNot($country->getKey())->where('iso2', $country->iso2)->exists()) {
                throw ValidationException::withMessages(['iso2' => __('international::international.errors.iso2_taken')]);
            }
        });
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Institution::class, 'country_id');
    }

    /** The flag as an emoji from the ISO code, when there is no flag picture. */
    public function emoji(): string
    {
        return implode('', array_map(fn (string $c): string => mb_chr(0x1F1E6 + ord($c) - 65), str_split($this->iso2)));
    }

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }
}
