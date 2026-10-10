<?php

namespace Modules\Program\Filament\Resources\Programs\Hub;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;

/** Professional Memberships / Accreditations (FR-PROG-08). */
class ProgramMemberships extends ProgramItemsPage
{
    protected static string $relationship = 'memberships';

    protected static ?int $navigationSort = 36;

    public static function hubKey(): string
    {
        return 'memberships';
    }

    public static function getNavigationLabel(): string
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
