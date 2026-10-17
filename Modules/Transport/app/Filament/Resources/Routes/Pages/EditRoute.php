<?php

namespace Modules\Transport\Filament\Resources\Routes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Transport\Filament\Resources\Routes\RouteResource;

class EditRoute extends EditRecord
{
    protected static string $resource = RouteResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
