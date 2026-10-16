<?php

namespace Modules\Documents\Support;

use Illuminate\Support\Facades\DB;
use Modules\Documents\Models\Document;
use Platform\Relations\RelationEngine;

/**
 * A new version of a document (FR-DOC-05): a copy with the new file, version and dates, shown in
 * the same places with the same category; the old one is marked as superseded by it — it leaves
 * the lists, its address keeps working and points to the new one.
 */
class Versions
{
    /** @param array{file?: ?int, version_label?: ?string, approved_on?: ?string, effective_on?: ?string} $data */
    public function create(Document $old, array $data): Document
    {
        return DB::transaction(function () use ($old, $data): Document {
            $new = $old->replicate(['download_count', 'superseded_by_id', 'active_key']);   // active_key is generated
            $new->fill(array_intersect_key($data, array_flip(['version_label', 'approved_on', 'effective_on'])));
            $new->active_at = now();
            $new->save();

            $new->syncMedia('file', array_filter([$data['file'] ?? $old->firstMedia('file')?->id]));
            if ($thumbnail = $old->firstMedia('thumbnail')) {
                $new->syncMedia('thumbnail', [$thumbnail->id]);
            }
            $new->syncTerms(Document::CATEGORY, $old->termsIn(Document::CATEGORY)->modelKeys());
            app(RelationEngine::class)->sync($new, $old->targetKeys(), mainSite: $old->isOnMainSite());

            $old->olderVersions()->update(['superseded_by_id' => $new->id]);   // the whole history points to the newest
            $old->update(['superseded_by_id' => $new->id]);

            return $new;
        });
    }
}
