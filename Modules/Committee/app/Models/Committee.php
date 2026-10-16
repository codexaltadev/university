<?php

namespace Modules\Committee\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Committee\Database\Factories\CommitteeFactory;
use Platform\Assignments\AssignmentEngine;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Platform\Relations\RelationEngine;
use Platform\Seo\Seo;
use Platform\Taxonomy\Term;
use Spatie\Translatable\HasTranslations;

/**
 * A committee (M-16, DB-04 §7 v1.4): Syndicate, Academic Council, a Lab's committee. Members
 * are assignments in committee roles (chairperson → convener → member → member secretary)
 * with an optional note ("Nominated by UGC"); where it shows is "Show in" (an entity's
 * microsite, the main site). A term that has ended is a previous term; "Copy to new term"
 * starts the next one with the current members (FR-COM-05).
 */
class Committee extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // scoped users: committees shown in their entities

    /** @use HasFactory<CommitteeFactory> */
    use HasFactory;

    use HasMediaUsage;      // order_pdf
    use HasSeo;
    use HasSlug;            // /committees/{slug}
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;
    use IsAttachable;
    use IsSearchable;

    public const PATH = 'committees';

    public const CATEGORY = 'committee.category';

    public const ROLES = ['chairperson', 'convener', 'member', 'member_secretary'];

    public static string $titleAttribute = 'name';

    protected $table = 'committee_committees';

    protected $fillable = ['category_term_id', 'name', 'description', 'formed_on', 'term_starts_on', 'term_ends_on', 'previous_term_id', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'description'];

    protected string $slugSource = 'name';

    protected function casts(): array
    {
        return ['category_term_id' => 'integer', 'previous_term_id' => 'integer', 'formed_on' => 'date', 'term_starts_on' => 'date', 'term_ends_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Committee $committee): void {
            if ($committee->term_starts_on && $committee->term_ends_on && $committee->term_ends_on->lt($committee->term_starts_on)) {
                throw ValidationException::withMessages(['term_ends_on' => __('committee::committee.errors.term')]);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'category_term_id');
    }

    public function previousTerm(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_term_id');
    }

    public function nextTerm(): HasOne
    {
        return $this->hasOne(self::class, 'previous_term_id');
    }

    /** The term has ended: not in the running list, still reachable as a previous term. */
    public function isPrevious(): bool
    {
        return $this->term_ends_on !== null && $this->term_ends_on->lt(Carbon::today());
    }

    /** "2024 – 2026", "Since 2025", or null. */
    public function termLabel(): ?string
    {
        return match (true) {
            $this->term_starts_on && $this->term_ends_on => $this->term_starts_on->format('d M Y').' – '.$this->term_ends_on->format('d M Y'),
            $this->term_starts_on !== null => __('committee::committee.since', ['date' => $this->term_starts_on->format('d M Y')]),
            $this->term_ends_on !== null => __('committee::committee.until', ['date' => $this->term_ends_on->format('d M Y')]),
            default => null,
        };
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('term_ends_on'))->orWhere($this->qualifyColumn('term_ends_on'), '>=', Carbon::today()->toDateString()));
    }

    /**
     * Earlier terms, newest first (visible ones only).
     *
     * @return Collection<int, Committee>
     */
    public function previousTerms(): Collection
    {
        $terms = collect();
        for ($term = $this->previousTerm, $guard = 0; $term && $guard < 50; $term = $term->previousTerm, $guard++) {
            if ($term->isFrontendVisible()) {
                $terms->push($term);
            }
        }

        return $terms;
    }

    /**
     * Start the next term (FR-COM-05): a copy shown in the same places, with today's members;
     * this term ends the day before the new one starts.
     */
    public function copyToNewTerm(Carbon $startsOn, ?Carbon $endsOn = null): self
    {
        return DB::transaction(function () use ($startsOn, $endsOn): self {
            $next = $this->replicate(['previous_term_id', 'formed_on', 'active_key']);   // active_key is generated
            $next->fill(['previous_term_id' => $this->id, 'term_starts_on' => $startsOn, 'term_ends_on' => $endsOn, 'formed_on' => $startsOn])->save();

            // Same places; the main site only when the user may decide it (else the Admin does, FR-CNT-H07)
            app(RelationEngine::class)->sync($next, $this->targetKeys(), mainSite: $this->isOnMainSite());

            foreach ($this->assignments()->current()->orderBy('sort_order')->with('person')->get() as $a) {
                if ($a->person) {
                    app(AssignmentEngine::class)->assign($a->person, $next, $a->role_key, [
                        'sort_order' => $a->sort_order, 'is_public' => $a->is_public, 'role_label' => $a->getTranslations('role_label') ?: null,
                        'note' => $a->getTranslations('note') ?: null, 'overrides' => $a->overrides,
                    ]);
                }
            }

            $this->update(['term_ends_on' => $startsOn->copy()->subDay()]);

            return $next;
        });
    }

    /** /committees/{slug} on the main site; a committee only shown in entities lives under the first one's microsite. */
    public function url(?string $locale = null): string
    {
        if (! $this->isOnMainSite()) {
            foreach ($this->targetKeys() as $key) {
                [$type, $id] = explode(':', $key, 2);
                $model = Relation::getMorphedModel($type);
                $entity = $model ? $model::query()->withoutGlobalScopes()->find($id) : null;
                if ($entity && ($base = app(MicrositeResolver::class)->urlFor($entity, $locale))) {
                    return rtrim($base, '/').'/'.self::PATH.'/'.$this->slug($locale);
                }
            }
        }

        return '/'.self::PATH.'/'.$this->slug($locale);
    }

    protected function searchBodyAttributes(): array
    {
        return ['description'];
    }

    /** FR-SEO-08 */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'Organization',
            'name' => $this->getTranslation('name', $locale, true),
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
        ]);
    }

    protected static function newFactory(): CommitteeFactory
    {
        return CommitteeFactory::new();
    }
}
