<?php

namespace Modules\Office\Filament\Resources\Offices\RelationManagers;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\TranslatableTabs;

/** Sections / sub-units (FR-OFF-04): name, a few words, contact; their team. */
class SectionsRelationManager extends OfficePartRelationManager
{
    protected static string $relationship = 'sections';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('office::office.sections_list.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('office::office.fields.name'))->required($isDefault)->maxLength(255),
                Textarea::make("description.{$locale}")->label(__('office::office.fields.description'))->rows(3),
            ]),
            Grid::make(3)->columnSpanFull()->schema([
                TextInput::make('email')->label(__('office::office.fields.email'))->email()->maxLength(191),
                TextInput::make('phone')->label(__('office::office.fields.phone'))->tel()->maxLength(50),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]),
            PeopleManager::make()->columnSpanFull(),
        ]);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->label(__('office::office.fields.name'))->searchable(),
            TextColumn::make('assignments_count')->counts('assignments')->label(__('office::office.fields.people')),
        ];
    }
}
