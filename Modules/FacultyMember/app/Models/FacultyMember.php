<?php

namespace Modules\FacultyMember\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Department\Models\Department;
use Modules\FacultyMember\Database\Factories\FacultyMemberFactory;
use Modules\FacultyMember\Support\FacultyMemberPresenter;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsPersonSource;
use Platform\Concerns\IsSearchable;
use Platform\Contracts\PersonPresenter;
use Platform\Contracts\PersonSource;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Platform\Taxonomy\Term;
use Spatie\Translatable\HasTranslations;

/**
 * A teacher (M-07, DB-03 §8 v1.7): a read-only copy of the university's faculty site, written
 * by faculty-member:sync (FR-FM-08). Only the contact switches and the order are this site's
 * own (FR-FM-11). A person source, so Dean, Head, committee members … point at it and follow
 * every change the faculty site makes (FR-FM-10, AC-FM-02).
 */
class FacultyMember extends BaseModel implements PersonSource, Scopeable
{
    use AppliesUserScope;   // Department users: their own teachers

    /** @use HasFactory<FacultyMemberFactory> */
    use HasFactory;

    use HasSeo;
    use HasSlug;            // /faculty-members/{slug}
    use HasSortOrder;
    use HasTranslations;
    use IsPersonSource;
    use IsSearchable;

    public const PATH = 'faculty-members';

    public const DESIGNATION = 'faculty-member.designation';

    public const EMPLOYMENT_TYPES = ['full_time', 'part_time', 'adjunct', 'on_leave'];

    public const PROFILE_SECTIONS = ['education', 'experience', 'training', 'award', 'membership', 'publication'];

    public const LINKS = ['google_scholar', 'orcid', 'researchgate', 'linkedin', 'website'];

    /** Columns the faculty site owns: sync writes them, nobody else (BRL-FM-03). */
    public const SOURCE_COLUMNS = [
        'external_ref', 'source_path', 'department_id', 'designation_term_id', 'designation_note', 'employee_id', 'name', 'email', 'phone', 'extension',
        'office_room', 'photo_url', 'employment_type', 'biography', 'research_interests', 'links', 'profile_items', 'is_active',
    ];

    /** This site's own (FR-FM-11). */
    public const LOCAL_COLUMNS = ['show_email', 'show_phone', 'show_extension', 'sort_order'];

    public static string $titleAttribute = 'name';

    protected $table = 'faculty_member_profiles';

    protected $fillable = [...self::SOURCE_COLUMNS, ...self::LOCAL_COLUMNS, 'source_hash', 'synced_at'];

    public array $translatable = ['name', 'designation_note', 'biography', 'research_interests'];

    public array $scopeColumns = ['department_id' => 'department.department'];

    protected string $slugSource = 'name';

    protected function casts(): array
    {
        return [
            'department_id' => 'integer', 'designation_term_id' => 'integer',
            'show_email' => 'boolean', 'show_phone' => 'boolean', 'show_extension' => 'boolean',
            'links' => 'array', 'profile_items' => 'array', 'synced_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** Other departments the teacher also belongs to (FR-FM-05). */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'faculty_member_departments', 'profile_id', 'department_id')->withTimestamps();
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'designation_term_id');
    }

    /** Teachers of these departments: primary or also there. */
    public function scopeInDepartments(Builder $query, array $ids): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereIn($this->qualifyColumn('department_id'), $ids)
            ->orWhereHas('departments', fn (Builder $d) => $d->whereIn('department_departments.id', $ids)));
    }

    /** Name, designation note or research interests (any language) contain the words, ignoring case. */
    public function scopeSearchText(Builder $query, string $search, array $columns = ['name', 'designation_note', 'research_interests']): Builder
    {
        $like = '%'.mb_strtolower(addcslashes(trim($search), '%_\\')).'%';

        return $query->where(function (Builder $q) use ($columns, $like): void {
            foreach ($columns as $column) {
                $q->orWhereRaw('LOWER(CAST('.$this->qualifyColumn($column).' AS CHAR)) LIKE ?', [$like]);
            }
        });
    }

    /** Directory order: the designation ladder (Taxonomy order, FR-FM-05), then the order inside it. */
    public function scopeDirectoryOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('(SELECT sort_order FROM core_terms WHERE core_terms.id = '.$this->qualifyColumn('designation_term_id').') IS NULL')
            ->orderBy(Term::query()->select('sort_order')->whereColumn('core_terms.id', $this->qualifyColumn('designation_term_id')))
            ->orderBy($this->qualifyColumn('sort_order'))->orderBy($this->getQualifiedKeyName());
    }

    public function isOnLeave(): bool
    {
        return $this->employment_type === 'on_leave';
    }

    /** "Professor and Head" */
    public function designationLabel(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return collect([$this->designation?->getTranslation('name', $locale, true), $this->getTranslation('designation_note', $locale, true)])
            ->filter()->implode(' ') ?: null;
    }

    /** @return list<array{title: ?string, institution: ?string, start_year: ?int, end_year: ?int, description: ?string}> */
    public function profileSection(string $section): array
    {
        return array_values((array) (($this->profile_items ?? [])[$section] ?? []));
    }

    public function scopeKeys(): array
    {
        return $this->department_id ? ['department.department:'.$this->department_id] : [];
    }

    public function personPresenter(): PersonPresenter
    {
        return new FacultyMemberPresenter($this);
    }

    /** Here: the directory searched for the name — the profile is on the faculty website (profileUrl()). */
    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'?q='.rawurlencode((string) $this->getTranslation('name', $locale ?? app()->getLocale(), true));
    }

    /**
     * The teacher's profile on the faculty website (owner 2026-10-25: profiles are always there):
     * Settings › Faculty Members › Faculty website / {faculty}/{department}/{teacher}; null without an FMS address.
     */
    public function profileUrl(): ?string
    {
        $site = rtrim((string) settings('faculty-member.profile_site'), '/');

        return $site !== '' && filled($this->source_path) ? $site.'/'.implode('/', array_map('rawurlencode', explode('/', (string) $this->source_path))) : null;
    }

    protected function searchBodyAttributes(): array
    {
        return ['designation_note', 'research_interests'];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        $presenter = $this->personPresenter();

        return array_filter([
            '@type' => 'Person',
            'name' => $this->getTranslation('name', $locale, true),
            'jobTitle' => $this->designationLabel($locale),
            'worksFor' => ['@type' => 'CollegeOrUniversity', 'name' => (string) config('app.name')],
            'email' => $presenter->email(),
            'image' => $presenter->photoUrl(),
            'sameAs' => array_values(array_filter((array) $this->links)) ?: null,
        ]);
    }

    protected static function newFactory(): FacultyMemberFactory
    {
        return FacultyMemberFactory::new();
    }
}
