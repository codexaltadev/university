<?php

namespace Modules\International\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Modules\International\Database\Factories\ConferenceFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** An international conference (M-35, DB-06 v1.3 §6, FR-INTL-05): dates, venue, organiser, picture; category from international.conference-category. */
class Conference extends BaseModel implements Scopeable
{
    use AppliesUserScope;

    /** @use HasFactory<ConferenceFactory> */
    use HasFactory;

    use HasMediaUsage;      // image
    use HasSeo;
    use HasSlug;            // /international/conferences/{slug}
    use HasTaxonomy;
    use HasTranslations;
    use IsAttachable;
    use IsSearchable;

    public const PATH = 'international/conferences';

    public const CATEGORY = 'international.conference-category';

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'international_conferences';

    protected $fillable = ['title', 'body', 'starts_on', 'ends_on', 'venue', 'website_url', 'organizer_label', 'is_active'];

    public array $translatable = ['title', 'body', 'venue', 'organizer_label'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Conference $c): void {
            if (filled($c->website_url) && ! preg_match('#^https?://\S+$#i', (string) $c->website_url)) {
                throw ValidationException::withMessages(['website_url' => __('international::international.errors.url')]);
            }
            if ($c->starts_on && $c->ends_on && $c->ends_on->lt($c->starts_on)) {
                throw ValidationException::withMessages(['ends_on' => __('international::international.errors.dates')]);
            }
        });
    }

    public function isUpcoming(): bool
    {
        return ($this->ends_on ?? $this->starts_on)?->gte(today()) ?? false;
    }

    public function scopeNewest(Builder $query): Builder
    {
        return $query->reorder()->orderByDesc($this->qualifyColumn('starts_on'))->orderByDesc($this->getQualifiedKeyName());
    }

    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'/'.($this->slug($locale) ?? $this->getKey());
    }

    protected function searchBodyAttributes(): array
    {
        return ['body', 'venue'];
    }

    protected static function newFactory(): ConferenceFactory
    {
        return ConferenceFactory::new();
    }
}
