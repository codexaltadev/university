<?php

namespace Modules\Lab\Filament\Resources\Labs\RelationManagers;

/** A lab's scopes / services (FR-LAB-04). */
class ScopesRelationManager extends LabItemsRelationManager
{
    public static function type(): string
    {
        return 'scope';
    }
}
