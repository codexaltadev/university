<?php

namespace Modules\Program\Filament\Resources\Programs\Hub;

use Modules\Program\Models\ProgramListItem;

/** Industries (FR-PROG-05). */
class ProgramIndustries extends ProgramCareerPaths
{
    protected const LIST_TYPE = ProgramListItem::INDUSTRY;

    protected static ?int $navigationSort = 33;

    public static function hubKey(): string
    {
        return 'industries';
    }
}
