<?php

namespace Modules\Program\Metrics;

use Modules\Program\Models\ProgramFacilitator;

/** Facilitators / industry experts listed on the programs (FR-PROG-11). */
class IndustryExpertCount extends ProgramItemCount
{
    public function key(): string
    {
        return 'program.industry-experts';
    }

    public function label(): string
    {
        return __('program::program.metric_industry_experts');
    }

    protected function model(): string
    {
        return ProgramFacilitator::class;
    }
}
