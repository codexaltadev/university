<?php

namespace Modules\Seo\Filament\Resources\NotFoundLogs\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Seo\Filament\Resources\NotFoundLogs\NotFoundLogResource;

class ListNotFoundLogs extends ListRecords
{
    protected static string $resource = NotFoundLogResource::class;
}
