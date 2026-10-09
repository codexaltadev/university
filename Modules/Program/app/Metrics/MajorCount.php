<?php

namespace Modules\Program\Metrics;

use Modules\Program\Models\ProgramMajor;

/** Majors / specializations (FR-PROG-11). */
class MajorCount extends ProgramItemCount
{
    public function key(): string
    {
        return 'program.majors';
    }

    public function label(): string
    {
        return __('program::program.metric_majors');
    }

    protected function model(): string
    {
        return ProgramMajor::class;
    }
}
