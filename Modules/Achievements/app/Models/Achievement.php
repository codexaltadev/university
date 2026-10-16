<?php

namespace Modules\Achievements\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Modules\Achievements\Database\Factories\AchievementFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasActiveAt;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\HasViewCount;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * An award or competition win (M-24, DB-05 §9 v1.8, SRS Content FR-ACH-01..04). The achievers
 * are people (assignments, role "achiever", with a note each), a team name optional; pictures
 * from the Media Library; level, competition, issuer, position; where it shows is "Show in" and
 * the main site.
 */
class Achievement extends BaseModel implements Scopeable
{
    use AppliesUserScope;
    use HasActiveAt;

    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    use HasMediaUsage;      // image, gallery
    use HasSeo;
    use HasSlug;            // /achievements/{slug}
    use HasTaxonomy;        // achievements.category
    use HasTranslations;
    use HasViewCount;
    use IsAssignable;       // the achievers
    use IsAttachable;
    use IsSearchable;

    public const PATH = 'achievements';

    public const CATEGORY = 'achievements.category';

    public const ROLE = 'achiever';

    public const LEVELS = ['international', 'national', 'regional', 'institutional'];

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'achievements_achievements';

    protected $fillable = ['title', 'summary', 'body', 'achieved_on', 'team_name', 'competition', 'issuer', 'level', 'position_label', 'related_post_id', 'active_at', 'is_active'];

    public array $translatable = ['title', 'summary', 'body', 'team_name', 'competition', 'issuer', 'position_label'];

    protected function casts(): array
    {
        return ['achieved_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Achievement $achievement): void {
            if (! in_array($achievement->level, self::LEVELS, true)) {
                throw ValidationException::withMessages(['level' => __('achievements::achievements.errors.level')]);
            }
            if ($achievement->achieved_on === null) {
                throw ValidationException::withMessages(['achieved_on' => __('achievements::achievements.errors.date')]);
            }
        });
        static::creating(function (Achievement $achievement): void {
            $achievement->active_at ??= now();
        });
    }

    public function scopeNewest(Builder $query): Builder
    {
        return $query->reorder()->orderByDesc($this->qualifyColumn('achieved_on'))->orderByDesc($this->getQualifiedKeyName());
    }

    public function scopeOfLevel(Builder $query, ?string $level): Builder
    {
        return $level ? $query->where($this->qualifyColumn('level'), $level) : $query;
    }

    /** The related News post when News is installed and the post is visible (FR-ACH-04). */
    public function relatedPost(): ?Model
    {
        $post = 'Modules\\News\\Models\\Post';

        return $this->related_post_id && class_exists($post) ? $post::query()->frontendVisible()->find($this->related_post_id) : null;
    }

    /** /achievements/{slug} on the main site; one only shown in entities lives under the first one's microsite. */
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
        return ['summary', 'body', 'competition', 'issuer', 'team_name'];
    }

    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'Article',
            'headline' => $this->getTranslation('title', $locale, true),
            'description' => $this->getTranslation('summary', $locale, true) ?: null,
            'datePublished' => $this->achieved_on?->toDateString(),
            'image' => $this->firstMedia('image')?->url('large'),
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
        ]);
    }

    protected static function newFactory(): AchievementFactory
    {
        return AchievementFactory::new();
    }
}
