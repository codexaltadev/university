<?php

namespace Modules\Administration\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Modules\Administration\Database\Factories\MemberFactory;
use Modules\Administration\Support\MemberPresenter;
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
use Platform\Slugs\Slug;
use Spatie\Translatable\HasTranslations;

/**
 * A member of staff (M-13, DB-04 §4.2 v1.2): everyone but teachers, in one place — offices,
 * committees, admission contacts … assign them (core_assignments), so a phone number changes
 * once (AC-ADMN-01). The directory shows members of public categories; an assignment shows
 * any active member (BRL-ADMN-03); an inactive one disappears everywhere (FR-ADMN-09).
 */
class Member extends BaseModel implements PersonSource, Scopeable
{
    use AppliesUserScope;   // Office users: members whose primary office is theirs (BRL-ADMN-02)

    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    use HasMediaUsage;      // photo
    use HasSeo;
    use HasSlug;            // /administration/{slug}
    use HasSortOrder;
    use HasTranslations;
    use IsPersonSource;
    use IsSearchable;

    public const PATH = 'administration';

    public static string $titleAttribute = 'name';

    protected $table = 'administration_members';

    protected $fillable = [
        'category_id', 'employee_id', 'name', 'designation', 'primary_office_id', 'email', 'phone', 'extension',
        'biography', 'show_email', 'show_phone', 'show_extension', 'sort_order', 'is_active',
    ];

    public array $translatable = ['name', 'designation', 'biography'];

    public array $scopeColumns = ['primary_office_id' => 'office.office'];

    protected string $slugSource = 'name';

    protected function casts(): array
    {
        return [
            'category_id' => 'integer', 'primary_office_id' => 'integer',
            'show_email' => 'boolean', 'show_phone' => 'boolean', 'show_extension' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // BRL-ADMN-01: an employee ID is one person's (blank = none)
        static::saving(function (Member $member): void {
            $member->employee_id = filled($member->employee_id) ? trim((string) $member->employee_id) : null;
            if ($member->employee_id !== null && static::query()->withoutGlobalScopes()->whereNull('deleted_at')
                ->where('employee_id', $member->employee_id)->whereKeyNot($member->getKey())->exists()) {
                throw ValidationException::withMessages(['employee_id' => __('administration::administration.errors.employee_id')]);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AdministrationCategory::class, 'category_id');
    }

    public function primaryOffice(): ?BelongsTo
    {
        $class = 'Modules\\Office\\Models\\Office';

        return class_exists($class) ? $this->belongsTo($class, 'primary_office_id') : null;
    }

    /** Directory, search, sitemap: active members of active public categories. */
    public function scopeFrontendVisible(Builder $query): Builder
    {
        return parent::scopeFrontendVisible($query)->whereHas('category', fn (Builder $c) => $c->public());
    }

    /** Name or designation (any language) contains the words, ignoring case — JSON columns compare in binary. */
    public function scopeSearchText(Builder $query, string $search, array $columns = ['name', 'designation']): Builder
    {
        $like = '%'.mb_strtolower(addcslashes(trim($search), '%_\\')).'%';

        return $query->where(function (Builder $q) use ($columns, $like): void {
            foreach ($columns as $column) {
                $q->orWhereRaw('LOWER(CAST('.$this->qualifyColumn($column).' AS CHAR)) LIKE ?', [$like]);
            }
        });
    }

    /** Directory order: the category ladder, then the order inside it. */
    public function scopeDirectoryOrder(Builder $query): Builder
    {
        return $query->orderBy(AdministrationCategory::query()->select('sort_order')->whereColumn('administration_categories.id', $this->qualifyColumn('category_id')))
            ->orderBy($this->qualifyColumn('sort_order'))->orderBy($this->getQualifiedKeyName());
    }

    /** Scopeable: an Office user manages members of their office (BRL-ADMN-02). */
    public function scopeKeys(): array
    {
        return $this->primary_office_id ? ['office.office:'.$this->primary_office_id] : [];
    }

    public function personPresenter(): PersonPresenter
    {
        return new MemberPresenter($this);
    }

    /** Profile page when profiles are on, else the directory searched for the name. */
    public function url(?string $locale = null): string
    {
        return settings('administration.profile_pages', true)
            ? '/'.self::PATH.'/'.$this->slug($locale)
            : '/'.self::PATH.'?q='.rawurlencode((string) $this->getTranslation('name', $locale ?? app()->getLocale(), true));
    }

    /** /administration/{slug} is a person or a section: a section's slug is taken for a person too */
    protected function slugTakenByOther(string $locale, string $slug): bool
    {
        return Slug::query()->where('locale', $locale)->where('slug', $slug)
            ->where(fn (Builder $q) => $q->where('sluggable_type', (new AdministrationCategory)->getMorphClass())
                ->orWhere(fn (Builder $own) => $own->where('sluggable_type', $this->getMorphClass())->where('sluggable_id', '!=', $this->getKey())))
            ->exists();
    }

    protected function searchBodyAttributes(): array
    {
        return ['designation'];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        $presenter = $this->personPresenter();

        return array_filter([
            '@type' => 'Person',
            'name' => $this->getTranslation('name', $locale, true),
            'jobTitle' => $this->getTranslation('designation', $locale, true) ?: null,
            'email' => $presenter->email(),
            'telephone' => $presenter->phone(),
            'image' => $presenter->photoUrl(),
        ]);
    }

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }
}
