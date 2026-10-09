<?php

namespace Modules\Fee\Filament\Resources\Tuitions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Fee\Filament\Resources\Tuitions\TuitionResource;

class EditTuition extends EditRecord
{
    protected static string $resource = TuitionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
