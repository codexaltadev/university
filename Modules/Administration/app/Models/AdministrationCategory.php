<?php

namespace Modules\Administration\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Platform\Slugs\Slug;
use Spatie\Translatable\HasTranslations;

/**
 * One rung of the staff ladder (FR-ADMN-02, DB-04 §4.1): "Director", "Deputy Registrar",
 * "Driver" — ordered from the top, in a group (management / officer / support), shown in the
 * directory or not (support is not, by default). A category with members is not removed.
 * Its section of the directory is /administration/{slug} (DIU /admin/joint-director); a slug never
 * equals a person's there.
 */
class AdministrationCategory extends BaseModel
{
    use HasSlug;
    use HasSortOrder;
    use HasTranslations;

    protected string $slugSource = 'name';

    public const GROUPS = ['management', 'officer', 'support'];

    public static string $titleAttribute = 'name';

    protected $table = 'administration_categories';

    protected $fillable = ['name', 'group', 'is_public', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (AdministrationCategory $category): void {
            if (! in_array($category->group, self::GROUPS, true)) {
                throw ValidationException::withMessages(['group' => __('administration::administration.errors.group')]);
            }
        });

        static::deleting(function (AdministrationCategory $category): void {
            if ($category->members()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['name' => __('administration::administration.errors.category_in_use')]);
            }
        });
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'category_id');
    }

    public function url(?string $locale = null): string
    {
        return '/'.Member::PATH.'/'.$this->slug($locale);
    }

    /** /administration/{slug} is a section or a person: a slug one of them has is taken for the other too */
    protected function slugTakenByOther(string $locale, string $slug): bool
    {
        return Slug::query()->where('locale', $locale)->where('slug', $slug)
            ->where(fn (Builder $q) => $q->where('sluggable_type', (new Member)->getMorphClass())
                ->orWhere(fn (Builder $own) => $own->where('sluggable_type', $this->getMorphClass())->where('sluggable_id', '!=', $this->getKey())))
            ->exists();
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_public'), true)->where($this->qualifyColumn('is_active'), true);
    }
}
