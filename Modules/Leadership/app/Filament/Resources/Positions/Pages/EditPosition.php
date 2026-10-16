<?php

namespace Modules\Leadership\Filament\Resources\Positions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Leadership\Filament\Resources\Positions\PositionResource;

class EditPosition extends EditRecord
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
