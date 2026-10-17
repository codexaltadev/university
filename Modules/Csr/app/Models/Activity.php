<?php

namespace Modules\Csr\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Modules\Csr\Database\Factories\ActivityFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasActiveAt;
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
 * A social responsibility activity (M-36, DB-06 v1.3 §7, SRS Services FR-CSR-01..04): text,
 * pictures from the Media Library, a video, a date; category from csr.category (with sub
 * categories); "Show in", main site and highlight.
 */
class Activity extends BaseModel implements Scopeable
{
    use AppliesUserScope;
    use HasActiveAt;

    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    use HasMediaUsage;      // image, gallery
    use HasSeo;
    use HasSlug;            // /csr/{slug}
    use HasSortOrder;
    use HasTaxonomy;        // csr.category
    use HasTranslations;
    use IsAttachable;
    use IsSearchable;

    public const PATH = 'csr';

    public const CATEGORY = 'csr.category';

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'csr_activities';

    protected $fillable = ['title', 'summary', 'body', 'external_url', 'video_url', 'occurred_on', 'sort_order', 'active_at', 'is_active'];

    public array $translatable = ['title', 'summary', 'body'];

    protected function casts(): array
    {
        return ['occurred_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Activity $activity): void {
            foreach (['external_url', 'video_url'] as $field) {
                if (filled($activity->{$field}) && ! preg_match('#^https?://\S+$#i', (string) $activity->{$field})) {
                    throw ValidationException::withMessages([$field => __('csr::csr.errors.url')]);
                }
            }
        });
        static::creating(function (Activity $activity): void {
            $activity->active_at ??= now();
        });
    }

    public function scopeNewest(Builder $query): Builder
    {
        return $query->reorder()->orderByRaw('COALESCE('.$this->qualifyColumn('occurred_on').', DATE('.$this->qualifyColumn('active_at').')) DESC')->orderByDesc($this->getQualifiedKeyName());
    }

    public function videoEmbed(): ?string
    {
        return preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([\w-]{11})~', (string) $this->video_url, $m)
            ? 'https://www.youtube-nocookie.com/embed/'.$m[1] : null;
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

    protected static function newFactory(): ActivityFactory
    {
        return ActivityFactory::new();
    }
}
