<?php

namespace Modules\{{Name}}\Filament\Resources\{{Name}}Items;

use Filament\Resources\Resource;
use Modules\{{Name}}\Models\{{Name}}Item;

class {{ResourceName}} extends Resource
{
    protected static ?string $model = {{Name}}Item::class;

    public static function getPages(): array
    {
        return [];
    }
}
