<?php

namespace Modules\Program\Filament\Resources\Programs\RelationManagers;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;

/** Professional Memberships / Accreditations (FR-PROG-08). */
class MembershipsRelationManager extends ProgramItemsRelationManager
{
    protected static string $relationship = 'memberships';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('program::program.items.memberships');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("title.{$locale}")->label(__('program::program.fields.title'))->required($isDefault)->maxLength(255),
                Textarea::make("short_description.{$locale}")->label(__('program::program.fields.short_description'))->rows(3)->maxLength(2000),
            ]),
            MediaPicker::make('logo')->label(__('program::program.fields.logo'))->image(),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    protected function columns(): array
    {
        return [TextColumn::make('title')->label(__('program::program.fields.title'))];
    }
}
