<?php

namespace Modules\Documents\Sections;

use Illuminate\Support\Collection;
use Modules\Documents\Models\Document;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/**
 * Homepage section "Prospectus" (FR-DOC-08): the place's current prospectus books as covers, each
 * opening its flipbook (or the file); highlighted first, then the newest.
 */
class ProspectusSection extends Section
{
    public static function key(): string
    {
        return 'documents.prospectus';
    }

    public static function label(): string
    {
        return __('documents::documents.section.prospectus');
    }

    public static function view(): string
    {
        return 'documents::sections.prospectus';
    }

    /** @return Collection<int, Document> */
    public function items(SectionContext $ctx): Collection
    {
        return DownloadsSection::pick($ctx, ['prospectus'], (string) $ctx->setting('source_mode', 'highlighted_first'), max(1, (int) $ctx->setting('count', 3)));
    }
}
