<?php

namespace Modules\Program\Filament\Resources\Programs\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;

/** Learning Materials: a link or a file from the Media Library (FR-PROG-07). */
class MaterialsRelationManager extends ProgramItemsRelationManager
{
    protected static string $relationship = 'materials';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('program::program.items.materials');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("title.{$locale}")->label(__('program::program.fields.title'))->required($isDefault)->maxLength(255),
            ]),
            TextInput::make('url')->label(__('program::program.fields.link'))->url()->maxLength(2048),
            MediaPicker::make('file')->label(__('program::program.fields.file'))->helperText(__('program::program.help.material_file')),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('title')->label(__('program::program.fields.title')),
            TextColumn::make('url')->label(__('program::program.fields.link'))->limit(50)->toggleable(),
        ];
    }
}
