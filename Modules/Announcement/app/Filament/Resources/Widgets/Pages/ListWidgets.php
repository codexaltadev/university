<?php

namespace Modules\Announcement\Filament\Resources\Widgets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Announcement\Filament\Resources\Widgets\WidgetResource;

class ListWidgets extends ListRecords
{
    protected static string $resource = WidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
