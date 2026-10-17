<?php

namespace Modules\Lab\Filament\Resources\Labs\RelationManagers;

/** A lab's student projects (FR-LAB-04). */
class ProjectsRelationManager extends LabItemsRelationManager
{
    public static function type(): string
    {
        return 'project';
    }
}
