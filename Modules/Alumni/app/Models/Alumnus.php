<?php

namespace Modules\Alumni\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Modules\Alumni\Database\Factories\AlumnusFactory;
use Modules\Alumni\Support\AlumnusPresenter;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsPersonSource;
use Platform\Concerns\IsSearchable;
use Platform\Contracts\PersonPresenter;
use Platform\Contracts\PersonSource;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A graduate (M-17, DB-04 §8 v1.5): where they studied (Department, Program, batch, year),
 * what they do now, their story and video, and "Pride Alumni". A person source, so committees
 * and events can pick them. Contacts are hidden unless switched on (FR-ALUM-06); a visitor's
 * submission stays off until the Alumni Office checks it (FR-ALUM-07, AC-ALUM-02).
 */
class Alumnus extends BaseModel implements PersonSource, Scopeable
{
    use AppliesUserScope;   // Department / Program users: their own alumni

    /** @use HasFactory<AlumnusFactory> */
    use HasFactory;

    use HasMediaUsage;      // photo
    use HasSeo;
    use HasSlug;            // /alumni/{slug}
    use HasSortOrder;
    use HasTranslations;
    use IsPersonSource;
    use IsSearchable;

    public const PATH = 'alumni';

    public const JOB_TYPES = ['job', 'business', 'higher_study', 'other'];

    public const SOCIAL = ['facebook', 'x', 'linkedin'];

    public const DEPARTMENT = 'Modules\\Department\\Models\\Department';

    public const PROGRAM = 'Modules\\Program\\Models\\Program';

    public static string $titleAttribute = 'name';

    protected $table = 'alumni_alumni';

    protected $fillable = [
        'name', 'student_id', 'department_id', 'program_id', 'batch', 'passing_year', 'current_designation', 'organization', 'job_type',
        'office_address', 'email', 'phone', 'show_email', 'show_phone', 'show_address', 'social_links', 'video_url', 'story',
        'is_pride', 'submission_source', 'sort_order', 'is_active',
    ];

    public array $translatable = ['name', 'current_designation', 'organization', 'office_address', 'story'];

    public array $scopeColumns = ['department_id' => 'department.department', 'program_id' => 'program.program'];

    protected string $slugSource = 'name';

    protected function casts(): array
    {
        return [
            'department_id' => 'integer', 'program_id' => 'integer', 'passing_year' => 'integer',
            'show_email' => 'boolean', 'show_phone' => 'boolean', 'show_address' => 'boolean', 'is_pride' => 'boolean',
            'social_links' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Alumnus $alumnus): void {
            $alumnus->student_id = filled($alumnus->student_id) ? trim((string) $alumnus->student_id) : null;
            if ($alumnus->student_id !== null && static::query()->withoutGlobalScopes()->whereNull('deleted_at')
                ->where('student_id', $alumnus->student_id)->whereKeyNot($alumnus->getKey())->exists()) {
                throw ValidationException::withMessages(['student_id' => __('alumni::alumni.errors.student_id')]);   // BRL-ALUM-01
            }
            if ($alumnus->job_type !== null && ! in_array($alumnus->job_type, self::JOB_TYPES, true)) {
                throw ValidationException::withMessages(['job_type' => __('alumni::alumni.errors.job_type')]);
            }
            // A program says which department
            if ($alumnus->program_id && class_exists(self::PROGRAM) && ($program = (self::PROGRAM)::query()->withoutGlobalScopes()->find($alumnus->program_id))) {
                $alumnus->department_id = $program->department_id;
            }
        });
    }

    public function scopePride(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_pride'), true);
    }

    /** Visitor submissions not yet checked (admin "New submissions"). */
    public function scopeNewSubmissions(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('submission_source'), 'public_form')->where($this->qualifyColumn('is_active'), false);
    }

    public function scopeKeys(): array
    {
        return array_values(array_filter([
            $this->department_id ? 'department.department:'.$this->department_id : null,
            $this->program_id ? 'program.program:'.$this->program_id : null,
        ]));
    }

    public function personPresenter(): PersonPresenter
    {
        return new AlumnusPresenter($this);
    }

    public function department(): mixed
    {
        return $this->department_id && class_exists(self::DEPARTMENT) ? (self::DEPARTMENT)::query()->withoutGlobalScopes()->find($this->department_id) : null;
    }

    public function program(): mixed
    {
        return $this->program_id && class_exists(self::PROGRAM) ? (self::PROGRAM)::query()->withoutGlobalScopes()->find($this->program_id) : null;
    }

    /** "Head of HR, Eastern Bank" */
    public function headline(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return collect([$this->getTranslation('current_designation', $locale, true), $this->getTranslation('organization', $locale, true)])
            ->filter()->implode(', ') ?: null;
    }

    /** YouTube / Drive link as an embeddable address, or null. */
    public function videoEmbed(): ?string
    {
        $url = (string) $this->video_url;

        return match (true) {
            (bool) preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $url, $m) => 'https://www.youtube-nocookie.com/embed/'.$m[1],
            (bool) preg_match('~drive\.google\.com/file/d/([\w-]+)~', $url, $m) => 'https://drive.google.com/file/d/'.$m[1].'/preview',
            default => null,
        };
    }

    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'/'.$this->slug($locale);
    }

    protected function searchBodyAttributes(): array
    {
        return ['current_designation', 'organization', 'story'];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'Person',
            'name' => $this->getTranslation('name', $locale, true),
            'jobTitle' => $this->getTranslation('current_designation', $locale, true) ?: null,
            'worksFor' => ($org = $this->getTranslation('organization', $locale, true)) ? ['@type' => 'Organization', 'name' => $org] : null,
            'alumniOf' => ['@type' => 'CollegeOrUniversity', 'name' => (string) config('app.name')],
            'image' => $this->firstMedia('photo')?->url('medium'),
        ]);
    }

    protected static function newFactory(): AlumnusFactory
    {
        return AlumnusFactory::new();
    }
}
