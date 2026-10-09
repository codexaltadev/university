<?php

namespace Modules\Localization\Filament\Resources\Languages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Localization\Filament\Resources\Languages\LanguageResource;

class ManageLanguages extends ManageRecords
{
    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
