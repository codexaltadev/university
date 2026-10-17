<?php

namespace Modules\International\Filament\Resources\Visitors\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\International\Filament\Resources\Visitors\VisitorResource;

class ListVisitors extends ListRecords
{
    protected static string $resource = VisitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
