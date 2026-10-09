<?php

namespace Modules\Program\Filament\Resources\Programs\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Program\Models\ProgramListItem;
use Platform\Filament\Components\TranslatableTabs;

/** Career Paths (FR-PROG-05); Industries share the table (IndustriesRelationManager). */
class CareerPathsRelationManager extends ProgramItemsRelationManager
{
    protected static string $relationship = 'listItems';

    protected const LIST_TYPE = ProgramListItem::CAREER_PATH;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
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
