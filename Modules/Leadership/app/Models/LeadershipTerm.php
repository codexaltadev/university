<?php

namespace Modules\Leadership\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Leadership\Database\Factories\LeadershipTermFactory;
use Platform\Assignments\Assignment;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\IsSearchable;
use Platform\Contracts\PersonPresenter;
use Platform\Contracts\PersonSource;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * Someone's term in a leadership position (DB-04 §6.2 v1.3): the person and the dates are the
 * core assignment (any person source or a manual person — a Trustee in no directory), the
 * message and biography are the term's own. A term whose end date has passed is "former"
 * (FR-LEAD-05). Web address from the person's name: /leadership/{slug}.
 */
class LeadershipTerm extends BaseModel
{
    /** @use HasFactory<LeadershipTermFactory> */
    use HasFactory;

    use HasSeo;
    use HasSlug;
    use HasTranslations;
    use IsSearchable;

    public const PATH = 'leadership';

    public static string $titleAttribute = 'message';

    protected $table = 'leadership_terms';

    protected $fillable = ['position_id', 'assignment_id', 'message', 'biography', 'is_active'];

    public array $translatable = ['message', 'biography'];

    protected function casts(): array
    {
        return ['position_id' => 'integer', 'assignment_id' => 'integer'];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** Website: an active term of an active position, public, whose person is active. */
    public function scopeFrontendVisible(Builder $query): Builder
    {
        return parent::scopeFrontendVisible($query)
            ->whereHas('position', fn (Builder $p) => $p->where('is_active', true))
            ->whereHas('assignment', fn (Builder $a) => $a->where('is_active', true)->where('is_public', true));
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereHas('assignment', fn (Builder $a) => $a->current());
    }

    public function scopeFormer(Builder $query): Builder
    {
        return $query->whereHas('assignment', fn (Builder $a) => $a->former());
    }

    public function scopeInPeopleOrder(Builder $query): Builder
    {
        return $query->orderBy(Assignment::query()->select('sort_order')->whereColumn('core_assignments.id', $this->qualifyColumn('assignment_id')))
            ->orderBy($this->getQualifiedKeyName());
    }

    public function person(): ?PersonPresenter
    {
        $person = $this->assignment?->person;

        return $person instanceof PersonSource ? $person->personPresenter() : null;
    }

    public function isFormer(): bool
    {
        return $this->assignment?->ends_on !== null && $this->assignment->ends_on->lt(Carbon::today());
    }

    public function name(): string
    {
        return (string) $this->person()?->name();
    }

    /** "2021 – 2025", "Since 2023" */
    public function tenure(): ?string
    {
        $from = $this->assignment?->starts_on;
        $to = $this->assignment?->ends_on;

        return match (true) {
            $from && $to => $from->year.' – '.$to->year,
            $from !== null => __('leadership::leadership.since', ['year' => $from->year]),
            $to !== null => __('leadership::leadership.until', ['year' => $to->year]),
            default => null,
        };
    }

    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'/'.$this->slug($locale);
    }

    /** The address comes from the person's name ("prof-dr-m-r-kabir"). */
    protected function slugBase(string $locale): string
    {
        return $this->name() ?: 'term-'.$this->getKey();
    }

    /** Search: the person's name and position, with the message. */
    public function toSearchDocument(string $locale): ?array
    {
        $name = $this->name();
        if ($name === '') {
            return null;
        }

        return [
            'title' => $name.' — '.$this->position?->getTranslation('title', $locale, true),
            'body' => static::plainText($this->getTranslation('message', $locale, false) ?: ''),
        ];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        $person = $this->person();

        return array_filter([
            '@type' => 'Person',
            'name' => $person?->name(),
            'jobTitle' => $this->position?->getTranslation('title', $locale, true),
            'image' => $person?->photoUrl(),
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
        ]);
    }

    protected static function newFactory(): LeadershipTermFactory
    {
        return LeadershipTermFactory::new();
    }
}
