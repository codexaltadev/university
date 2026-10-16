<?php

namespace Modules\Links\Filament\Resources\Links\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Links\Filament\Resources\Links\LinkResource;

class CreateLink extends CreateRecord
{
    protected static string $resource = LinkResource::class;
}
