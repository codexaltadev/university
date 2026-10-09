<?php

namespace Modules\Faq\Sections;

use Illuminate\Support\Collection;
use Modules\Faq\Models\Faq;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/** Homepage section "Selected questions" (Module Guide §11.1). */
class FaqHighlightsSection extends Section
{
    public static function key(): string
    {
        return 'faq.highlights';
    }

    public static function label(): string
    {
        return __('faq::faq.section_label');
    }

    public static function view(): string
    {
        return 'faq::sections.faq-highlights';
    }

    public static function model(): ?string
    {
        return Faq::class;
    }

    /** Source mode (latest / highlighted / highlighted first / hand-picked) applied by the platform — FR-HOME-11 */
    public function items(SectionContext $ctx): Collection
    {
        return $ctx->applySourceMode(Faq::class, defaultCount: 5);
    }
}
