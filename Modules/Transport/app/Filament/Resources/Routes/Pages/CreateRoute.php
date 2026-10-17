<?php

namespace Modules\Transport\Filament\Resources\Routes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Transport\Filament\Resources\Routes\RouteResource;

class CreateRoute extends CreateRecord
{
    protected static string $resource = RouteResource::class;

    /** Stops and times are added on the edit page, per service. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
