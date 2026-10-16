<?php

namespace Modules\Office\Filament\Resources\Offices\RelationManagers;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\TranslatableTabs;

/** Services (FR-OFF-06): what, what to bring, fee, time, where to apply, the form, who handles it. */
class ServicesRelationManager extends OfficePartRelationManager
{
    protected static string $relationship = 'services';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('office::office.services.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("title.{$locale}")->label(__('office::office.fields.title'))->required($isDefault)->maxLength(255),
                RichEditor::make("description.{$locale}")->label(__('office::office.fields.description'))->fileAttachments(false),
                RichEditor::make("required_documents.{$locale}")->label(__('office::office.fields.required_documents'))->fileAttachments(false),
                TextInput::make("fee_note.{$locale}")->label(__('office::office.fields.fee_note'))->maxLength(255),
                TextInput::make("processing_time.{$locale}")->label(__('office::office.fields.processing_time'))->maxLength(255),
            ]),
            Grid::make(3)->columnSpanFull()->schema([
                TextInput::make('fee_amount')->label(__('office::office.fields.fee_amount'))->numeric()->minValue(0),
                TextInput::make('apply_url')->label(__('office::office.fields.apply_url'))->url()->maxLength(2048),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]),
            MediaPicker::make('form')->label(__('office::office.fields.form')),
            PeopleManager::make()->columnSpanFull(),
        ]);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('title')->label(__('office::office.fields.title'))->searchable()->wrap(),
            TextColumn::make('fee_amount')->label(__('office::office.fields.fee_amount'))->numeric(),
        ];
    }
}
