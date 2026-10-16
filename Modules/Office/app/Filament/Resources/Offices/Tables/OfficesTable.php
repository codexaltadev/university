<?php

namespace Modules\Office\Filament\Resources\Offices\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Office\Models\Office;
use Platform\Taxonomy\Vocabularies;

/** Offices by type and order; how many sections, services and support contacts each has. */
class OfficesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('type')->withCount(['sections', 'services', 'supportContacts']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('office::office.fields.name'))->searchable()->wrap()
                    ->description(fn (Office $record): ?string => $record->short_name),
                TextColumn::make('type.name')->label(__('office::office.fields.type'))->badge()->color('gray'),
                TextColumn::make('services_count')->label(__('office::office.services.plural'))->numeric()->toggleable(),
                TextColumn::make('support_contacts_count')->label(__('office::office.support.plural'))->numeric()->toggleable(),
                IconColumn::make('has_microsite')->label(__('office::office.fields.has_microsite'))->boolean(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([
                SelectFilter::make('type_term_id')->label(__('office::office.fields.type'))
                    ->options(fn (): array => app(Vocabularies::class)->options(Office::TYPE)),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }
}
