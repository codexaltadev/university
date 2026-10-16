<?php

namespace Modules\Office\Filament\Resources\Offices\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * An office's sections, services or support contacts (FR-OFF-04/06/07, BRL-OFF-03): whoever
 * may change the office (an Admin, or its Office role with Manage:Office#{id}) manages them;
 * they are edited in a modal with their people.
 */
abstract class OfficePartRelationManager extends RelationManager
{
    /** @return list<Column> */
    abstract protected function columns(): array;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ...$this->columns(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ])
            ->headerActions([CreateAction::make()->modalWidth('4xl')])
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
