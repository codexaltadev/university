<?php

namespace Modules\Faculty\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Faculty\Database\Factories\FacultyFactory;
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
 * A Faculty / School (M-01, faculty_faculties): the top of the academic hierarchy,
 * with its microsite /faculty/{slug} (FR-FAC-02). Dean = person assignment "dean"
 * (FR-FAC-03); counters = its own stat set (FR-FAC-04). Departments register
 * themselves as children, so a Faculty with active Departments cannot be deleted
 * (BRL-FAC-01).
 */
class Faculty extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // Faculty users: their own Faculty only

    /** @use HasFactory<FacultyFactory> */
    use HasFactory;

    use HasMediaUsage;      // photo, cover, logo
    use HasSeo;
    use HasSlug;            // /faculty/{slug}
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;       // Dean, Associate Dean, Officer, Contact
    use IsRelationTarget;   // news, notices, events "Show in" this Faculty
    use IsSearchable;

    public static string $titleAttribute = 'name';

    protected $table = 'faculty_faculties';

    protected $fillable = ['name', 'short_name', 'overview', 'dean_message', 'email', 'phone', 'location', 'quick_links', 'campus_id', 'show_in_listing', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'overview', 'dean_message', 'location'];

    protected string $slugSource = 'name';

    protected function casts(): array
    {
        return ['show_in_listing' => 'boolean', 'campus_id' => 'integer', 'quick_links' => 'array'];
    }

    /** FR-FAC-09: listed on /faculties and in "Our Faculties"; hidden ones keep their own address. */
    public function scopeListed(Builder $query): Builder
    {
        return $query->frontendVisible()->where('show_in_listing', true)->ordered();
    }

    /** The Faculty's microsite, e.g. /faculty/fsit. */
    public function url(?string $locale = null): ?string
    {
        return app(MicrositeResolver::class)->urlFor($this, $locale);
    }

    /** Its own scope: "only in this Faculty" searches (FR-SRCH-06). */
    public function searchOwnerKey(): ?string
    {
        return $this->getMorphClass().':'.$this->getKey();
    }

    protected function searchBodyAttributes(): array
    {
        return ['short_name', 'overview'];
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
        ]);
    }

    protected static function newFactory(): FacultyFactory
    {
        return FacultyFactory::new();
    }
}
