<?php

namespace Modules\Announcement\Filament\Resources\Widgets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Announcement\Filament\Resources\Widgets\WidgetResource;

class EditWidget extends EditRecord
{
    protected static string $resource = WidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
