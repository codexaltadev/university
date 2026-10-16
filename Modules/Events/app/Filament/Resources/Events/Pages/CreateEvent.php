<?php

namespace Modules\Events\Filament\Resources\Events\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Events\Filament\Resources\Events\EventResource;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
