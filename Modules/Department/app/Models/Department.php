<?php

namespace Modules\Department\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Department\Database\Factories\DepartmentFactory;
use Modules\Faculty\Models\Faculty;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsRelationTarget;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * A Department (M-02, department_departments) under a Faculty, with its microsite
 * /department/{slug} (FR-DEPT-02). Head = person assignment "head" (FR-DEPT-03);
 * counters = its own stat set (FR-DEPT-05). Programs and Faculty Members register
 * themselves as children, so a Department with active ones cannot be deleted
 * (BRL-DEPT-02). Moving it to another Faculty fires EntityHierarchyChanged (FR-DEPT-08).
 */
class Department extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // Faculty users: their departments; Department users: their own

    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    use HasMediaUsage;      // photo, cover
    use HasSeo;
    use HasSlug;            // /department/{slug}
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;       // Head, Coordinator, Officer, Contact, Industry Expert
    use IsRelationTarget;   // news, notices, events "Show in" this Department
    use IsSearchable;

    public static string $titleAttribute = 'name';

    protected $table = 'department_departments';

    protected $fillable = ['faculty_id', 'name', 'short_name', 'about_title', 'about', 'head_message', 'external_url', 'email', 'phone', 'location', 'campus_id', 'show_in_listing', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'about_title', 'about', 'head_message', 'location'];

    protected string $slugSource = 'name';

    protected function casts(): array
    {
        return ['faculty_id' => 'integer', 'show_in_listing' => 'boolean', 'campus_id' => 'integer'];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /** Listed on /departments and in the "Departments" section; hidden ones keep their own address. */
    public function scopeListed(Builder $query): Builder
    {
        return $query->frontendVisible()->where('show_in_listing', true)
            ->whereHas('faculty', fn (Builder $q) => $q->frontendVisible())
            ->ordered();
    }

    /** The Department's microsite, e.g. /department/cse. */
    public function url(?string $locale = null): ?string
    {
        return app(MicrositeResolver::class)->urlFor($this, $locale);
    }

    /** FR-DEPT-04: listings link to the department's own website when it has one. */
    public function listingUrl(?string $locale = null): ?string
    {
        return $this->external_url ?: $this->url($locale);
    }

    /** Its own scope: "only in this Department" searches (FR-SRCH-06). */
    public function searchOwnerKey(): ?string
    {
        return $this->getMorphClass().':'.$this->getKey();
    }

    protected function searchBodyAttributes(): array
    {
        return ['short_name', 'about_title', 'about'];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'CollegeOrUniversity',
            'name' => $this->getTranslation('name', $locale, true),
            'alternateName' => $this->short_name,
            'url' => ($url = $this->url($locale)) ? Seo::absolute(Seo::localized($url, $locale)) : null,
            'email' => $this->email,
            'telephone' => $this->phone,
            'parentOrganization' => $this->faculty ? ['@type' => 'CollegeOrUniversity', 'name' => $this->faculty->getTranslation('name', $locale, true)] : null,
        ]);
    }

    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }
}
