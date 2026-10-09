<?php

namespace Modules\ModuleManager\Filament\Resources\Modules\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\ModuleManager\Filament\Resources\Modules\ModuleActions;
use Modules\ModuleManager\Services\ModuleOverview;
use Platform\Models\CoreModule;

class ModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('modulemanager::manager.fields.name'))
                    ->description(fn (CoreModule $record): ?string => $record->manifest['description'] ?? null)
                    ->searchable(['name', 'slug'])
                    ->sortable(),
                TextColumn::make('version')->label(__('modulemanager::manager.fields.version')),
                TextColumn::make('type')->label(__('modulemanager::manager.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("modulemanager::manager.type.{$state}"))
                    ->color(fn (string $state): string => $state === 'core' ? 'gray' : 'info'),
                TextColumn::make('status')->label(__('modulemanager::manager.fields.status'))
                    ->badge()
                    ->state(fn (CoreModule $record): string => $record->status === CoreModule::STATUS_UNINSTALLED
                        ? 'uninstalled'
                        : (ModuleActions::isActive($record) ? 'active' : 'inactive'))
                    ->formatStateUsing(fn (string $state): string => __("modulemanager::manager.status.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('relations')->label(__('modulemanager::manager.fields.relations'))
                    ->state(function (CoreModule $record): string {
                        $overview = app(ModuleOverview::class);
                        $needs = array_keys($overview->dependsOn($record));
                        $usedBy = $overview->usedBy($record);

                        return trim(($needs ? '← '.implode(', ', $needs) : '').($usedBy ? '  → '.implode(', ', $usedBy) : '')) ?: '—';
                    }),
                TextColumn::make('activated_at')->label(__('modulemanager::manager.fields.activated'))->since()->placeholder('—'),
            ])
            ->recordActions([
                ModuleActions::activate(),
                ModuleActions::deactivate(),
                ActionGroup::make([
                    ViewAction::make()->label(__('modulemanager::manager.actions.details')),
                    ModuleActions::uninstall(),
                ]),
            ]);
    }
}
