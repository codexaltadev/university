<?php

namespace Modules\Program\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Database\Factories\ProgramFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasPublishDate;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsRelationTarget;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Spatie\Translatable\HasTranslations;

/**
 * An academic program (M-03, program_programs) under a Department, with its microsite
 * /program/{slug} (FR-PROG-02). Its Faculty comes from the Department (FR-PROG-01).
 * Level = one term of program.level (FR-PROG-03); tags = program.tag (FR-PROG-03a).
 * Moving it to another Department fires EntityHierarchyChanged (BRL-PROG-02).
 */
class Program extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // Faculty / Department users: their programs; Program users: their own

    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    use HasMediaUsage;      // image, brochure
    use HasPublishDate;
    use HasSeo;
    use HasSlug;            // /program/{slug}, unique site-wide (BRL-PROG-01)
    use HasSortOrder;
    use HasTaxonomy;        // program.tag
    use HasTranslations;
    use IsAssignable;       // Coordinator, Officer, Contact, Industry Expert
    use IsRelationTarget;   // news, notices, events "Show in" this Program
    use IsSearchable;

    public const SHIFTS = ['day', 'evening', 'weekend'];

    public const CLASS_MODES = ['on_campus', 'online', 'hybrid'];

    public static string $titleAttribute = 'name';

    protected $table = 'program_programs';

    protected $fillable = [
        'department_id', 'level_term_id', 'name', 'short_name', 'subtitle', 'degree_name',
        'total_credits', 'duration_years', 'total_terms', 'duration_label', 'medium', 'shift', 'class_mode',
        'summary', 'objective', 'section_intros', 'ai_summary', 'ai_short_summary',
        'eligibility_general', 'eligibility_english_medium', 'eligibility_rules',
        'next_intake_on', 'application_url', 'video_url', 'show_in_listing', 'sort_order', 'published_at', 'is_active',
    ];

    public array $translatable = [
        'name', 'subtitle', 'degree_name', 'duration_label', 'summary', 'objective', 'section_intros',
        'ai_summary', 'ai_short_summary', 'eligibility_general', 'eligibility_english_medium',
    ];

    protected string $slugSource = 'name';

    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'level_term_id' => 'integer',
            'total_credits' => 'decimal:1',
            'duration_years' => 'decimal:1',
            'total_terms' => 'integer',
            'eligibility_rules' => 'array',
            'next_intake_on' => 'date',
            'show_in_listing' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'level_term_id');
    }

    /** FR-PROG-04 */
    public function majors(): HasMany
    {
        return $this->hasMany(ProgramMajor::class)->ordered();
    }

    /** FR-PROG-05: career paths and industries */
    public function listItems(): HasMany
    {
        return $this->hasMany(ProgramListItem::class)->ordered();
    }

    /** FR-PROG-06 */
    public function facilitators(): HasMany
    {
        return $this->hasMany(ProgramFacilitator::class)->ordered();
    }

    /** FR-PROG-07 */
    public function materials(): HasMany
    {
        return $this->hasMany(ProgramMaterial::class)->ordered();
    }

    /** FR-PROG-08 */
    public function memberships(): HasMany
    {
        return $this->hasMany(ProgramMembership::class)->ordered();
    }

    /** FR-PROG-09 */
    public function variants(): HasMany
    {
        return $this->hasMany(ProgramVariant::class)->ordered();
    }

    /** FR-PROG-01: always the Department's Faculty, never stored. */
    public function faculty(): ?Faculty
    {
        return $this->department?->faculty;
    }

    /** Listed on /programs and in the program sections; its Department and Faculty must be visible too. */
    public function scopeListed(Builder $query): Builder
    {
        return $query->frontendVisible()->where('show_in_listing', true)
            ->whereHas('department', fn (Builder $d) => $d->frontendVisible()->whereHas('faculty', fn (Builder $f) => $f->frontendVisible()))
            ->ordered();
    }

    /**
     * Listing filters (FR-PROG-12, AC-PROG-01); empty values are ignored.
     *
     * @param  array{faculty?: ?int, department?: ?int, level?: ?int, shift?: ?string, mode?: ?string, q?: ?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['faculty'] ?? null, fn (Builder $q, $id) => $q->whereHas('department', fn (Builder $d) => $d->where('faculty_id', $id)))
            ->when($filters['department'] ?? null, fn (Builder $q, $id) => $q->where('department_id', $id))
            ->when($filters['level'] ?? null, fn (Builder $q, $id) => $q->where('level_term_id', $id))
            ->when($filters['shift'] ?? null, fn (Builder $q, $shift) => $q->where('shift', $shift))
            ->when($filters['mode'] ?? null, fn (Builder $q, $mode) => $q->where('class_mode', $mode))
            ->when(filled($filters['q'] ?? null), function (Builder $q) use ($filters): void {
                // name is JSON (binary collation): compare lower-cased text, every language
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_strtolower(trim($filters['q']))).'%';
                $q->where(fn (Builder $w) => $w->whereRaw('LOWER(CAST(name AS CHAR)) LIKE ?', [$term])->orWhereRaw('LOWER(short_name) LIKE ?', [$term]));
            });
    }

    /** The Program's microsite, e.g. /program/bsc-in-cse. */
    public function url(?string $locale = null): ?string
    {
        return app(MicrositeResolver::class)->urlFor($this, $locale);
    }

    /** The intro text above a page section (section_intros), in the current language. */
    public function intro(string $section, ?string $locale = null): ?string
    {
        $intros = $this->getTranslation('section_intros', $locale ?? app()->getLocale(), true);

        return is_array($intros) && filled($intros[$section] ?? null) ? $intros[$section] : null;
    }

    /** "144.0" → "144", "3.5" → "3.5" for credits and years. */
    public static function plainNumber(mixed $value): ?string
    {
        return $value === null ? null : rtrim(rtrim((string) $value, '0'), '.');
    }

    /** "4 Years (12 Semesters)" as entered, else the number of years. */
    public function durationText(): ?string
    {
        if (filled($this->duration_label)) {
            return $this->duration_label;
        }

        return $this->duration_years !== null ? __('program::program.years', ['count' => static::plainNumber($this->duration_years)]) : null;
    }

    public function shiftLabel(): ?string
    {
        return $this->shift ? __("program::program.shifts.{$this->shift}") : null;
    }

    public function classModeLabel(): ?string
    {
        return $this->class_mode ? __("program::program.class_modes.{$this->class_mode}") : null;
    }

    /** Its own scope: "only in this Program" searches (FR-SRCH-06). */
    public function searchOwnerKey(): ?string
    {
        return $this->getMorphClass().':'.$this->getKey();
    }

    protected function searchBodyAttributes(): array
    {
        return ['short_name', 'subtitle', 'degree_name', 'summary'];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'EducationalOccupationalProgram',
            'name' => $this->getTranslation('name', $locale, true),
            'alternateName' => $this->short_name,
            'description' => filled($summary = $this->getTranslation('summary', $locale, true)) ? strip_tags($summary) : null,
            'url' => ($url = $this->url($locale)) ? Seo::absolute(Seo::localized($url, $locale)) : null,
            'educationalCredentialAwarded' => $this->getTranslation('degree_name', $locale, true) ?: null,
            'numberOfCredits' => $this->total_credits !== null ? (float) $this->total_credits : null,
            'timeToComplete' => $this->duration_years !== null ? 'P'.static::plainNumber($this->duration_years).'Y' : null,
            'applicationStartDate' => $this->next_intake_on?->toDateString(),
            'provider' => ($faculty = $this->faculty()) ? ['@type' => 'CollegeOrUniversity', 'name' => $faculty->getTranslation('name', $locale, true)] : null,
        ]);
    }

    protected static function newFactory(): ProgramFactory
    {
        return ProgramFactory::new();
    }
}
