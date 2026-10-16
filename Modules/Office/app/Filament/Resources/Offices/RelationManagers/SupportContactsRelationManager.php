<?php

namespace Modules\Office\Filament\Resources\Offices\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\TranslatableTabs;

/** "Who to contact" (FR-OFF-07): a purpose, where, and the people to contact. */
class SupportContactsRelationManager extends OfficePartRelationManager
{
    protected static string $relationship = 'supportContacts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('office::office.support.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("purpose.{$locale}")->label(__('office::office.fields.purpose'))->required($isDefault)->maxLength(255),
                TextInput::make("location.{$locale}")->label(__('office::office.fields.location'))->maxLength(255),
            ]),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
            PeopleManager::make()->columnSpanFull(),
        ]);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('purpose')->label(__('office::office.fields.purpose'))->searchable()->wrap(),
            TextColumn::make('assignments_count')->counts('assignments')->label(__('office::office.fields.people')),
        ];
    }
}
