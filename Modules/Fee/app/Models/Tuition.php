<?php

namespace Modules\Fee\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Modules\Program\Models\Program;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasSortOrder;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * Tuition fee of a Program in a fee category (FR-FEE-02, DB-03 §11.1 v1.1). The fees
 * are lines (admission, per year, per semester, per credit, other); the totals are
 * worked out from them on every save (AC-FEE-03) unless "totals overridden" is on:
 *
 *   tuition = years + per-semester × semesters + per-credit × credits   ← waiver base
 *   total   = tuition + admission + other
 *
 * min uses the lower credits / amounts, max the higher ones (ranges such as 40–60 credits).
 */
class Tuition extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // Program / Department / Faculty users: their programs' fees
    use HasSortOrder;
    use HasTranslations;

    public static string $titleAttribute = 'label';

    /** @var array<string, string> scope path: through the program */
    public array $scopeColumns = ['program_id' => 'program.program'];

    protected $table = 'fee_tuitions';

    protected $fillable = [
        'program_id', 'fee_category_id', 'session_id', 'label', 'credit_min', 'credit_max', 'total_terms', 'duration_label',
        'tuition_min', 'tuition_max', 'total_min', 'total_max', 'totals_overridden', 'prospectus_url', 'sort_order', 'is_active',
    ];

    public array $translatable = ['label', 'duration_label'];

    protected static function booted(): void
    {
        static::saving(function (Tuition $tuition): void {
            if (! $tuition->totals_overridden && $tuition->exists) {
                $tuition->forceFill($tuition->computeTotals());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'program_id' => 'integer', 'fee_category_id' => 'integer', 'session_id' => 'integer', 'total_terms' => 'integer',
            'credit_min' => 'decimal:1', 'credit_max' => 'decimal:1',
            'tuition_min' => 'decimal:2', 'tuition_max' => 'decimal:2', 'total_min' => 'decimal:2', 'total_max' => 'decimal:2',
            'totals_overridden' => 'boolean',
        ];
    }

    /** Scopeable: a user scoped to the program (or above it) may manage its fees. */
    public function scopeKeys(): array
    {
        return ['program.program:'.$this->program_id];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TuitionLine::class)->ordered();
    }

    /** Shown on the website: active, of a visible program, in an active category (BRL-PROG-03). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->frontendVisible()
            ->whereHas('category', fn (Builder $c) => $c->frontendVisible())
            ->whereHas('program', fn (Builder $p) => $p->frontendVisible()->whereHas('department', fn (Builder $d) => $d->frontendVisible()));
    }

    /**
     * Totals from the lines (ignores the stored ones).
     *
     * @return array{tuition_min: ?float, tuition_max: ?float, total_min: ?float, total_max: ?float}
     */
    public function computeTotals(): array
    {
        $lines = $this->lines()->frontendVisible()->get();

        if ($lines->isEmpty()) {
            return ['tuition_min' => null, 'tuition_max' => null, 'total_min' => null, 'total_max' => null];
        }

        $sum = fn (string $type, bool $max): float => (float) $lines->where('line_type', $type)
            ->sum(fn (TuitionLine $l): float => (float) ($max ? ($l->amount_max ?? $l->amount) : $l->amount));
        $terms = (int) ($this->total_terms ?? 1);
        $credits = fn (bool $max): float => (float) ($max ? ($this->credit_max ?? $this->credit_min) : $this->credit_min) ?: 0.0;

        $tuition = fn (bool $max): float => $sum(TuitionLine::YEAR, $max) + $sum(TuitionLine::TERM, $max) * $terms + $sum(TuitionLine::PER_CREDIT, $max) * $credits($max);
        $total = fn (bool $max): float => $tuition($max) + $sum(TuitionLine::ADMISSION, $max) + $sum(TuitionLine::OTHER, $max);

        $min = ['tuition' => $tuition(false), 'total' => $total(false)];
        $max = ['tuition' => $tuition(true), 'total' => $total(true)];

        return [
            'tuition_min' => $min['tuition'],
            'tuition_max' => $max['tuition'] > $min['tuition'] ? $max['tuition'] : null,
            'total_min' => $min['total'],
            'total_max' => $max['total'] > $min['total'] ? $max['total'] : null,
        ];
    }

    /** Lines of one type that are shown. */
    public function linesOf(string $type): Collection
    {
        return $this->lines->filter(fn (TuitionLine $l): bool => $l->line_type === $type && $l->is_active)->values();
    }

    /** Admission fee (FR-FEE-06: "payable during admission"). */
    public function admissionFee(): ?float
    {
        $lines = $this->linesOf(TuitionLine::ADMISSION);

        return $lines->isEmpty() ? null : (float) $lines->sum('amount');
    }

    /** Other fees: the "other" lines, else whatever the total has beyond tuition (as DIU shows them). */
    public function otherFees(bool $max = false): ?float
    {
        $lines = $this->linesOf(TuitionLine::OTHER);

        if ($lines->isNotEmpty()) {
            return (float) $lines->sum(fn (TuitionLine $l): float => (float) ($max ? ($l->amount_max ?? $l->amount) : $l->amount));
        }

        $total = $max ? ($this->total_max ?? $this->total_min) : $this->total_min;
        $tuition = $max ? ($this->tuition_max ?? $this->tuition_min) : $this->tuition_min;

        return $total === null ? null : (float) $total - (float) $tuition;
    }

    /** "142", "40 – 60" */
    public function creditsText(): ?string
    {
        $min = static::plain($this->credit_min);
        $max = static::plain($this->credit_max);

        return $max !== null && $max !== $min ? "{$min} – {$max}" : $min;
    }

    public function hasRange(): bool
    {
        return $this->total_max !== null && (float) $this->total_max > (float) $this->total_min;
    }

    public static function plain(mixed $value): ?string
    {
        return $value === null ? null : rtrim(rtrim((string) $value, '0'), '.');
    }
}
