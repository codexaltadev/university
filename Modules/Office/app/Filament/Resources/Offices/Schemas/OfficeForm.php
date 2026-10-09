<?php

namespace Modules\Office\Filament\Resources\Offices\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;

class OfficeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            MediaPicker::make('logo')->image(),
            PeopleManager::make()->columnSpanFull(),   // head, team (FR-REL-10)
        ]);
    }
}
