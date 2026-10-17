<?php

namespace Modules\Facilities\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Modules\Facilities\Database\Factories\FacilityFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A campus facility (M-33, DB-06 v1.3 §4, SRS Services FR-FCL-01..05): library, hostel, medical
 * centre … with an icon and pictures from the Media Library; its own page when it has text, else a
 * link. Where it shows is "Show in" and the main site.
 */
class Facility extends BaseModel implements Scopeable
{
    use AppliesUserScope;

    /** @use HasFactory<FacilityFactory> */
    use HasFactory;

    use HasMediaUsage;      // icon, image, gallery
    use HasSeo;
    use HasSlug;            // /facilities/{slug}
    use HasSortOrder;
    use HasTaxonomy;        // facilities.category
    use HasTranslations;
    use IsAttachable;
    use IsSearchable;

    public const PATH = 'facilities';

    public const CATEGORY = 'facilities.category';

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'facilities_facilities';

    protected $fillable = ['title', 'summary', 'body', 'external_url', 'open_in_new_tab', 'campus_id', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'summary', 'body'];

    protected function casts(): array
    {
        return ['open_in_new_tab' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Facility $facility): void {
            if (filled($facility->external_url) && ! preg_match('#^(https?://\S+|/\S*)$#i', (string) $facility->external_url)) {
                throw ValidationException::withMessages(['external_url' => __('facilities::facilities.errors.url')]);
            }
        });
    }

    /** Its own page when it has text (FR-FCL-03). */
    public function hasPage(): bool
    {
        return trim(strip_tags((string) $this->body)) !== '';
    }

    /** Where a card links: its page, else the outside address. */
    public function href(?string $base = null): ?string
    {
        if ($this->hasPage()) {
            return url($base !== null && $base !== '' ? $base.'/'.self::PATH.'/'.$this->slug() : $this->url());
        }

        return filled($this->external_url) ? (str_starts_with((string) $this->external_url, '/') ? url($this->external_url) : $this->external_url) : null;
    }

    public function url(?string $locale = null): string
    {
        $slug = $this->slug($locale) ?? (string) $this->getKey();
        if (! $this->isOnMainSite()) {
            foreach ($this->targetKeys() as $key) {
                [$type, $id] = explode(':', $key, 2);
                $model = Relation::getMorphedModel($type);
                $entity = $model ? $model::query()->withoutGlobalScopes()->find($id) : null;
                if ($entity && ($base = app(MicrositeResolver::class)->urlFor($entity, $locale))) {
                    return rtrim($base, '/').'/'.self::PATH.'/'.$slug;
                }
            }
        }

        return '/'.self::PATH.'/'.$slug;
    }

    protected function searchBodyAttributes(): array
    {
        return ['summary', 'body'];
    }

    protected static function newFactory(): FacilityFactory
    {
        return FacilityFactory::new();
    }
}
