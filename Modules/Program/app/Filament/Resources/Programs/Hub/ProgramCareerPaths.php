<?php

namespace Modules\Program\Filament\Resources\Programs\Hub;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Modules\Program\Models\ProgramListItem;
use Platform\Filament\Components\TranslatableTabs;

/** Career Paths (FR-PROG-05); Industries share the table (ProgramIndustries). */
class ProgramCareerPaths extends ProgramItemsPage
{
    protected static string $relationship = 'listItems';

    protected const LIST_TYPE = ProgramListItem::CAREER_PATH;

    protected static ?int $navigationSort = 32;

    public static function hubKey(): string
    {
        return 'career-paths';
    }

    public static function getNavigationLabel(): string
    {
        return __('program::program.items.'.static::LIST_TYPE);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("title.{$locale}")->label(__('program::program.fields.title'))->required($isDefault)->maxLength(255),
            ]),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    protected function columns(): array
    {
        return [TextColumn::make('title')->label(__('program::program.fields.title'))];
    }

    protected function defaults(): array
    {
        return ['list_type' => static::LIST_TYPE];
    }

    protected function scopeQuery(Builder $query): Builder
    {
        return $query->where('list_type', static::LIST_TYPE);
    }
}
