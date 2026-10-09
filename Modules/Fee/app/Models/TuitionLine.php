<?php

namespace Modules\Fee\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * One fee of a tuition (fee_tuition_lines): admission, a year ("1st Year"), per semester,
 * per credit, or other. Saving or removing a line works the tuition's totals out again
 * (AC-FEE-03). The admission fee is not part of the waiver base.
 */
class TuitionLine extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;

    public const ADMISSION = 'admission';

    public const YEAR = 'year';

    public const TERM = 'term';

    public const PER_CREDIT = 'per_credit';

    public const OTHER = 'other';

    public const TYPES = [self::ADMISSION, self::YEAR, self::TERM, self::PER_CREDIT, self::OTHER];

    public static string $titleAttribute = 'label';

    protected $table = 'fee_tuition_lines';

    protected $fillable = ['tuition_id', 'line_type', 'label', 'amount', 'amount_max', 'sort_order', 'is_active'];

    public array $translatable = ['label'];

    protected static function booted(): void
    {
        $recalculate = function (TuitionLine $line): void {
            $tuition = Tuition::query()->withoutGlobalScopes()->find($line->tuition_id);

            if ($tuition && ! $tuition->totals_overridden) {
                $tuition->save();   // "saving" works the totals out
            }
        };

        static::saved($recalculate);
        static::deleted($recalculate);
        static::restored($recalculate);
    }

    protected function casts(): array
    {
        return ['tuition_id' => 'integer', 'amount' => 'decimal:2', 'amount_max' => 'decimal:2'];
    }

    public function tuition(): BelongsTo
    {
        return $this->belongsTo(Tuition::class);
    }
}
