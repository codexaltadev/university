<?php

namespace Modules\Fee\Filament\Resources\FeeCategories\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Fee\Filament\Resources\FeeCategories\FeeCategoryResource;

class ManageFeeCategories extends ManageRecords
{
    protected static string $resource = FeeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
