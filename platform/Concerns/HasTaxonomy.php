<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * Categories and tags on any model (C-16, core_termables P-12). The form field is
 * TermSelect::make('category')->vocabulary('faq.category'); in code:
 *
 *     $faq->syncTerms('faq.category', [$termId]);
 *     $faq->termsIn('faq.category');
 *     Faq::query()->withTerm($term);
 *
 * Deleted terms drop out of every list automatically; force delete removes the links.
 */
trait HasTaxonomy
{
    public static function bootHasTaxonomy(): void
    {
        $event = method_exists(static::class, 'forceDeleted') ? 'forceDeleted' : 'deleted';

        static::$event(fn (Model $model) => $model->terms()->detach());
    }

    public function terms(): MorphToMany
    {
        return $this->morphToMany(Term::class, 'termable', 'core_termables')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /** @return Collection<int, Term> */
    public function termsIn(string $vocabulary): Collection
    {
        $id = app(Vocabularies::class)->find($vocabulary)->id;

        return $this->terms()->where('vocabulary_id', $id)->get();
    }

    /** @return list<int> */
    public function termIdsIn(string $vocabulary): array
    {
        return $this->termsIn($vocabulary)->pluck('id')->all();
    }

    /**
     * Replace this record's terms of one vocabulary; the others stay. Order is kept
     * (first = primary category). A single-choice vocabulary keeps only the first.
     *
     * @param  list<int|string>  $termIds
     */
    public function syncTerms(string $vocabulary, array $termIds): void
    {
        $vocab = app(Vocabularies::class)->find($vocabulary);

        $ids = Term::withTrashed()->where('vocabulary_id', $vocab->id)->whereIn('id', $termIds)->pluck('id')->all();
        $ids = array_values(array_filter(array_map('intval', $termIds), fn (int $id): bool => in_array($id, $ids, true)));

        if (! $vocab->allows_multiple) {
            $ids = array_slice($ids, 0, 1);
        }

        $existing = Term::withTrashed()->where('vocabulary_id', $vocab->id)->pluck('id')->all();
        $this->terms()->withTrashed()->detach(array_diff($existing, $ids));

        $this->terms()->withTrashed()->syncWithoutDetaching(collect($ids)->mapWithKeys(fn (int $id, int $i): array => [
            $id => ['sort_order' => $i, 'created_at' => now()],
        ])->all());
    }

    public function scopeWithTerm(Builder $query, Term|int $term): Builder
    {
        $id = $term instanceof Term ? $term->getKey() : $term;

        return $query->whereHas('terms', fn (Builder $q) => $q->whereKey($id));
    }
}
