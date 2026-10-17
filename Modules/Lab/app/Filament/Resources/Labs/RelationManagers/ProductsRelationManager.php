<?php

namespace Modules\Lab\Filament\Resources\Labs\RelationManagers;

/** A lab's products (FR-LAB-04). */
class ProductsRelationManager extends LabItemsRelationManager
{
    public static function type(): string
    {
        return 'product';
    }
}
