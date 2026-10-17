<?php

namespace Modules\International\Filament\Resources\Countries\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\International\Filament\Resources\Countries\CountryResource;

class ManageCountries extends ManageRecords
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
