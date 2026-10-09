<?php

namespace Modules\Taxonomy\Filament\Resources\Vocabularies\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Taxonomy\Vocabulary;

/** Terms of one vocabulary: add, nest, reorder, deactivate, delete with usage warning (FR-TAX-02, BRL-TAX-01). */
class TermsRelationManager extends RelationManager
{
    protected static string $relationship = 'terms';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('taxonomy::taxonomy.terms');
    }

    protected function vocabulary(): Vocabulary
    {
        return $this->getOwnerRecord();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('taxonomy::taxonomy.fields.name'))->required($isDefault)->maxLength(150),
                Textarea::make("description.{$locale}")->label(__('taxonomy::taxonomy.fields.description'))->rows(2)->maxLength(1000),
            ]),
            Select::make('parent_id')->label(__('taxonomy::taxonomy.fields.parent'))
                ->visible(fn (): bool => $this->vocabulary()->is_hierarchical)
                ->options(fn (?Term $record): array => array_diff_key(
                    app(Vocabularies::class)->options($this->vocabulary()->key, activeOnly: false),
                    array_flip($record?->descendantIds() ?? []),       // never under itself
                ))
                ->searchable()
                ->placeholder(__('taxonomy::taxonomy.top_level')),
            ColorPicker::make('color')->label(__('taxonomy::taxonomy.fields.color')),
            TextInput::make('icon')->label(__('taxonomy::taxonomy.fields.icon'))->maxLength(100)
                ->helperText(__('taxonomy::taxonomy.help.icon')),
            Toggle::make('is_active')->label(__('taxonomy::taxonomy.fields.active'))->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class])->with('parent'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('taxonomy::taxonomy.fields.name'))
                    ->description(fn (Term $record): ?string => $record->parent?->name)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%")),
                ColorColumn::make('color')->label(''),
                TextColumn::make('usage')->label(__('taxonomy::taxonomy.fields.used_by'))
                    ->state(fn (Term $record): int => $record->usageCount())->numeric(),
                IconColumn::make('is_active')->label(__('taxonomy::taxonomy.fields.active'))->boolean(),
            ])
            ->filters([TrashedFilter::make()])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn (Term $record): string => ($count = $record->usageCount()) > 0
                        ? __('taxonomy::taxonomy.help.delete_used', ['count' => $count])
                        : __('taxonomy::taxonomy.help.delete'))
                    ->using(function (Term $record): bool {
                        try {
                            return (bool) $record->delete();
                        } catch (ParentHasActiveChildren $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return false;
                        }
                    }),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]);
    }
}
