<?php

namespace Modules\Admission\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Admission\Database\Factories\CircularFactory;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsSearchable;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * An admission circular (M-09, DB-03 v1.5 §10): a session's admission with its important
 * dates (apply from / until, test, admission, classes) and a PDF. Closed once the last day to
 * apply has passed (FR-ADM-03). /admission/circulars/{slug}.
 */
class Circular extends BaseModel
{
    /** @use HasFactory<CircularFactory> */
    use HasFactory;

    use HasMediaUsage;      // pdf
    use HasSeo;
    use HasSlug;
    use HasSortOrder;
    use HasTranslations;
    use IsSearchable;

    public const PATH = 'admission/circulars';

    /** The important dates, in the order a student meets them. */
    public const DATES = ['apply_starts_on', 'apply_ends_on', 'test_on', 'admission_on', 'classes_start_on'];

    public static string $titleAttribute = 'title';

    protected $table = 'admission_circulars';

    protected $fillable = ['session_label', 'title', 'content', 'apply_starts_on', 'apply_ends_on', 'test_on', 'admission_on', 'classes_start_on', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'content'];

    protected string $slugSource = 'title';

    protected function casts(): array
    {
        return array_fill_keys(self::DATES, 'date');
    }

    /** Applications closed: the last day to apply has passed (FR-ADM-03). */
    public function isClosed(): bool
    {
        return $this->apply_ends_on !== null && $this->apply_ends_on->endOfDay()->isPast();
    }

    /** Still running: not closed, and its admission or classes not long gone. */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('apply_ends_on')->orWhereDate('apply_ends_on', '>=', today()));
    }

    /**
     * The dates it has, as label key => date.
     *
     * @return array<string, CarbonInterface>
     */
    public function importantDates(): array
    {
        return collect(self::DATES)->mapWithKeys(fn (string $key): array => [$key => $this->{$key}])->filter()->all();
    }

    /** The next date still ahead (countdown), or null. */
    public function nextDate(): ?array
    {
        foreach ($this->importantDates() as $key => $date) {
            if ($date->endOfDay()->isFuture()) {
                return ['key' => $key, 'date' => $date];
            }
        }

        return null;
    }

    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'/'.$this->slug($locale);
    }

    protected function searchBodyAttributes(): array
    {
        return ['content'];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'Article',
            'headline' => $this->getTranslation('title', $locale, true),
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
            'datePublished' => $this->created_at?->toIso8601String(),
        ]);
    }

    protected static function newFactory(): CircularFactory
    {
        return CircularFactory::new();
    }
}
