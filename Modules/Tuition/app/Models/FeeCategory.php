<?php

namespace Modules\Tuition\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Tuition\Support\TuitionWebsite;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** A fee category (FR-FEE-01, tuition_categories): Local, International … with its currency. */
class FeeCategory extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;

    public const SYMBOLS = ['BDT' => '৳', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹'];

    public static string $titleAttribute = 'name';

    protected $table = 'tuition_categories';

    protected $fillable = ['name', 'code', 'currency', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    public function tuitions(): HasMany
    {
        return $this->hasMany(Tuition::class);
    }

    /** Its page: /tuition-fees/international (owner 2026-11-02: the category in the address, filters in the query) */
    public function url(): string
    {
        return '/'.TuitionWebsite::PAGE.'/'.rawurlencode(Str::lower((string) $this->code));
    }

    public function symbol(): string
    {
        return self::SYMBOLS[$this->currency] ?? $this->currency;
    }

    /** "৳ 9,52,500" style is locale work for later; DIU shows "৳ 952,500". */
    public function money(int|float|string|null $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $value = (float) $amount;

        return $this->symbol().' '.number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2);
    }
}
