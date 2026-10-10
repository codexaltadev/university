<?php

namespace Modules\Program\Filament\Resources\Tuitions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Program\Filament\Resources\Tuitions\TuitionResource;

class ListTuitions extends ListRecords
{
    protected static string $resource = TuitionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
