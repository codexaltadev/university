<?php

namespace Modules\Program\Filament\Resources\Programs\Hub;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Modules\Program\Models\ProgramMajor;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabularies;

/** Majors / Specializations (FR-PROG-04). */
class ProgramMajors extends ProgramItemsPage
{
    protected static string $relationship = 'majors';

    protected static ?int $navigationSort = 31;

    public static function hubKey(): string
    {
        return 'majors';
    }

    public static function getNavigationLabel(): string
    {
        return __('program::program.items.majors');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("title.{$locale}")->label(__('program::program.fields.title'))->required($isDefault)->maxLength(255),
                Textarea::make("short_description.{$locale}")->label(__('program::program.fields.short_description'))->rows(3)->maxLength(2000),
            ]),
            Select::make('category_term_id')->label(__('program::program.fields.major_category'))
                ->options(fn (): array => app(Vocabularies::class)->options('program.major-category'))->searchable(),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('title')->label(__('program::program.fields.title'))
                ->description(fn (ProgramMajor $record): ?string => $record->category?->name),
        ];
    }
}
