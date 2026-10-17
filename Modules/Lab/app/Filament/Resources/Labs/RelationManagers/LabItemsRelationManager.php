<?php

namespace Modules\Lab\Filament\Resources\Labs\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Lab\Models\LabItem;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabularies;

/**
 * A lab's equipment, scopes, projects or products (FR-LAB-04/10): whoever may change the lab
 * (an Admin, or its people with Manage:Lab#{id}) manages them in a modal; dragged order;
 * category shared by all labs (lab.{type}-category).
 */
abstract class LabItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    /** equipment / scope / project / product */
    abstract public static function type(): string;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('lab::lab.types_plural.'.static::type());
    }

    public function form(Schema $schema): Schema
    {
        $type = static::type();

        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => array_filter([
                TextInput::make("title.{$locale}")->label(__('lab::lab.fields.title'))->required($isDefault)->maxLength(255),
                RichEditor::make("description.{$locale}")->label(__('lab::lab.fields.description'))->fileAttachments(false),
                $type === 'project' ? TextInput::make("student_names.{$locale}")->label(__('lab::lab.fields.student_names'))->maxLength(500) : null,
            ])),
            Grid::make(3)->columnSpanFull()->schema(array_filter([
                TermSelect::make('category')->vocabulary(LabItem::vocabulary($type)),
                $type === 'equipment' ? TextInput::make('quantity')->label(__('lab::lab.fields.quantity'))->numeric()->minValue(0) : null,
                $type === 'project' ? TextInput::make('student_ids')->label(__('lab::lab.fields.student_ids'))->maxLength(255) : null,
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ])),
            MediaPicker::make('image')->label(__('lab::lab.fields.image'))->image()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $type = static::type();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->ofType($type)->with('terms'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns(array_filter([
                ImageColumn::make('image')->label('')->imageSize(36)->state(fn (LabItem $record): ?string => $record->firstMedia('image')?->url('thumb')),
                TextColumn::make('title')->label(__('lab::lab.fields.title'))->searchable()->wrap(),
                TextColumn::make('category')->label(__('lab::lab.fields.category'))->badge()->color('gray')->state(fn (LabItem $record): ?string => $record->termsIn(LabItem::vocabulary($type))->first()?->name),
                $type === 'equipment' ? TextColumn::make('quantity')->label(__('lab::lab.fields.quantity'))->numeric() : null,
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ]))
            ->filters([
                SelectFilter::make('category')->label(__('lab::lab.fields.category'))->options(fn (): array => app(Vocabularies::class)->options(LabItem::vocabulary($type)))
                    ->query(fn (Builder $q, array $data) => filled($data['value'] ?? null) ? $q->withTerm((int) $data['value']) : $q),
            ])
            ->headerActions([CreateAction::make()->modalWidth('4xl')->mutateDataUsing(fn (array $data): array => [...$data, 'item_type' => $type])])
            ->recordActions([EditAction::make()->modalWidth('4xl'), DeleteAction::make()]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('update', $ownerRecord);
    }

    protected function canCreate(): bool
    {
        return (bool) auth()->user()?->can('update', $this->getOwnerRecord());
    }

    protected function canEdit(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function canDelete(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function canReorder(): bool
    {
        return $this->canCreate();
    }
}
