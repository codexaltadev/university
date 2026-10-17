<?php

namespace Modules\Lab\Filament\Resources\Labs\RelationManagers;

/** A lab's equipment and tools (FR-LAB-04). */
class EquipmentRelationManager extends LabItemsRelationManager
{
    public static function type(): string
    {
        return 'equipment';
    }
}
