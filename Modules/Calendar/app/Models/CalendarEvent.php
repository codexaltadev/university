<?php

namespace Modules\Calendar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Calendar\Database\Factories\CalendarEventFactory;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Models\BaseModel;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Spatie\Translatable\HasTranslations;

/**
 * One date of an academic calendar (DB-03 §12 v1.6): a day or a range, or only words
 * ("1st week of March") when no exact date is known — then it is not in .ics nor in
 * "Upcoming Academic Dates". Type (calendar.event-type, with colour) and audience
 * (calendar.audience, many; none = everyone).
 */
class CalendarEvent extends BaseModel
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    use HasSortOrder;
    use HasTaxonomy;        // calendar.audience
    use HasTranslations;

    public const TYPE = 'calendar.event-type';

    public const AUDIENCE = 'calendar.audience';

    public static string $titleAttribute = 'title';

    protected $table = 'calendar_events';

    protected $fillable = ['calendar_id', 'title', 'starts_on', 'ends_on', 'date_label', 'type_term_id', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'date_label'];

    protected function casts(): array
    {
        return ['calendar_id' => 'integer', 'type_term_id' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (CalendarEvent $event): void {
            if ($event->ends_on && ! $event->starts_on) {
                throw ValidationException::withMessages(['starts_on' => __('calendar::calendar.errors.end_without_start')]);
            }
            if ($event->ends_on && $event->ends_on->lt($event->starts_on)) {
                throw ValidationException::withMessages(['ends_on' => __('calendar::calendar.errors.end_before_start')]);
            }
            if ($event->ends_on && $event->ends_on->eq($event->starts_on)) {
                $event->ends_on = null;   // one day
            }
            $words = array_filter($event->getTranslations('date_label'), fn ($v): bool => trim((string) $v) !== '');
            if (! $event->starts_on && $words === []) {
                throw ValidationException::withMessages(['starts_on' => __('calendar::calendar.errors.no_date')]);
            }
            if ($words === []) {
                $event->attributes['date_label'] = null;   // not {"en": null}
            }
        });
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'type_term_id');
    }

    public function scopeDated(Builder $query): Builder
    {
        return $query->whereNotNull($this->qualifyColumn('starts_on'));
    }

    /** Dates still running or to come. */
    public function scopeUpcoming(Builder $query, ?Carbon $today = null): Builder
    {
        $today = ($today ?? Carbon::today())->toDateString();

        return $query->dated()->where(fn (Builder $q) => $q->where($this->qualifyColumn('starts_on'), '>=', $today)
            ->orWhere($this->qualifyColumn('ends_on'), '>=', $today))
            ->orderBy($this->qualifyColumn('starts_on'));
    }

    /**
     * Only the given audiences' dates; dates for everyone stay (AC-CAL-01).
     *
     * @param  int|list<int>  $termIds
     */
    public function scopeForAudience(Builder $query, int|array $termIds): Builder
    {
        $termIds = (array) $termIds;
        $vocabulary = app(Vocabularies::class)->find(self::AUDIENCE)->id;

        return $query->where(fn (Builder $q) => $q->whereHas('terms', fn (Builder $t) => $t->whereIn('core_terms.id', $termIds))
            ->orWhereDoesntHave('terms', fn (Builder $t) => $t->where('core_terms.vocabulary_id', $vocabulary)));
    }

    /** The type's colour when it is a plain #hex, for style attributes. */
    public function color(string $fallback = 'var(--color-primary, #034EA2)'): string
    {
        $color = (string) $this->type?->color;

        return preg_match('/^#[0-9a-f]{3,8}$/i', $color) ? $color : $fallback;
    }

    public function lastDay(): ?Carbon
    {
        return $this->ends_on ?? $this->starts_on;
    }

    /** "9 May 2026", "9 – 15 May 2026", "29 December 2026 – 9 January 2027", then the words if any ("· Week 1"); words alone without a date. */
    public function when(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $words = trim((string) $this->getTranslation('date_label', $locale, true));
        if (! $this->starts_on) {
            return $words;
        }

        $start = $this->starts_on->copy()->locale($locale);
        $end = $this->ends_on?->copy()->locale($locale);
        $dates = match (true) {
            $end === null => $start->translatedFormat('j F Y'),
            $start->isSameMonth($end) => $start->translatedFormat('j').' – '.$end->translatedFormat('j F Y'),
            $start->isSameYear($end) => $start->translatedFormat('j F').' – '.$end->translatedFormat('j F Y'),
            default => $start->translatedFormat('j F Y').' – '.$end->translatedFormat('j F Y'),
        };

        return $words !== '' ? $dates.' · '.$words : $dates;
    }

    protected static function newFactory(): CalendarEventFactory
    {
        return CalendarEventFactory::new();
    }
}
