<?php

namespace Modules\Administration\Filament\Resources\Categories\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Administration\Filament\Resources\Categories\AdministrationCategoryResource;
use Modules\Administration\Models\AdministrationCategory;

class ManageAdministrationCategories extends ManageRecords
{
    protected static string $resource = AdministrationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->mutateDataUsing(fn (array $data): array => $data + ['sort_order' => (int) AdministrationCategory::query()->max('sort_order') + 1]),
        ];
    }
}
