<?php

namespace Modules\International\Filament\Resources\Visitors\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\International\Filament\Resources\Visitors\VisitorResource;

class CreateVisitor extends CreateRecord
{
    protected static string $resource = VisitorResource::class;
}
