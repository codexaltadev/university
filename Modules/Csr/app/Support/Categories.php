<?php

namespace Modules\Csr\Support;

use Illuminate\Support\Collection;
use Modules\Csr\Models\Activity;
use Platform\Media\MediaItem;
use Platform\Media\MediaUsage;
use Platform\Media\MediaUsages;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * CSR categories (FR-CSR-01): the csr.category terms in their order, each with a picture (a
 * Media Library use of the term, field "csr_image") and its description.
 */
class Categories
{
    public const FIELD = 'csr_image';

    /** @return Collection<int, Term> all terms of the vocabulary, in its order */
    public function all(): Collection
    {
        $order = array_keys(app(Vocabularies::class)->options(Activity::CATEGORY));

        return Term::query()->whereKey($order)->get()->sortBy(fn (Term $t): int => array_search($t->id, $order, true))->values();
    }

    public function image(Term $term): ?MediaItem
    {
        $usage = MediaUsage::query()->where(['usable_type' => $term->getMorphClass(), 'usable_id' => $term->id, 'field' => self::FIELD])->with('item')->first();

        return $usage?->item;
    }

    public function setImage(Term $term, ?int $mediaId): void
    {
        app(MediaUsages::class)->sync($term, self::FIELD, array_filter([$mediaId]));
    }
}
