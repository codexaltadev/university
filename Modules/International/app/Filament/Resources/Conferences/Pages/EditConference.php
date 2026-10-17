<?php

namespace Modules\International\Filament\Resources\Conferences\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\International\Filament\Resources\Conferences\ConferenceResource;

class EditConference extends EditRecord
{
    protected static string $resource = ConferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
