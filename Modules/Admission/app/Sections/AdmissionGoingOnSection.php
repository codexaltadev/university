<?php

namespace Modules\Admission\Sections;

use Illuminate\Support\Collection;
use Modules\Admission\Models\Circular;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

/**
 * Homepage section "Admission going on" (FR-ADM-11): the running circulars with their next
 * date (countdown) and important dates, and the Apply button. Nothing running = not shown.
 */
class AdmissionGoingOnSection extends Section
{
    public static function key(): string
    {
        return 'admission.going-on';
    }

    public static function label(): string
    {
        return __('admission::admission.going_on');
    }

    public static function view(): string
    {
        return 'admission::sections.going-on';
    }

    /** @return Collection<int, Circular> */
    public function items(SectionContext $ctx): Collection
    {
        return Circular::query()->frontendVisible()->running()->ordered()->limit(3)->get();
    }
}
