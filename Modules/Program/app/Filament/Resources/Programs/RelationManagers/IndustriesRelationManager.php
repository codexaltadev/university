<?php

namespace Modules\Program\Filament\Resources\Programs\RelationManagers;

use Modules\Program\Models\ProgramListItem;

/** Industries (FR-PROG-05). */
class IndustriesRelationManager extends CareerPathsRelationManager
{
    protected const LIST_TYPE = ProgramListItem::INDUSTRY;
}
