<?php

namespace Modules\International\Filament\Resources\Visitors\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\International\Filament\Resources\Visitors\VisitorResource;

class EditVisitor extends EditRecord
{
    protected static string $resource = VisitorResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
