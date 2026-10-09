<?php

namespace Modules\Fee\Filament\Resources\Boards\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Fee\Filament\Resources\Boards\BoardResource;

class ManageBoards extends ManageRecords
{
    protected static string $resource = BoardResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
