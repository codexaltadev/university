<?php

namespace Modules\Fee\Filament\Resources\WaiverCategories\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Fee\Filament\Resources\WaiverCategories\WaiverCategoryResource;

class ManageWaiverCategories extends ManageRecords
{
    protected static string $resource = WaiverCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
