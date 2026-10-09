<?php

namespace Modules\Localization\Filament\Resources\Languages\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Platform\Localization\Language;
use Platform\Localization\LanguageRuleViolation;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->paginated(false)
            ->columns([
                TextColumn::make('native_name')->label(__('localization::localization.fields.native_name'))
                    ->description(fn (Language $record): string => $record->name)
                    ->weight('medium'),
                TextColumn::make('code')->label(__('localization::localization.fields.code'))->badge()->color('gray'),
                TextColumn::make('is_default')->label('')
                    ->formatStateUsing(fn (bool $state): ?string => $state ? __('localization::localization.default') : null)
                    ->badge()->color('primary'),
                IconColumn::make('is_active')->label(__('localization::localization.fields.active'))->boolean(),
                TextColumn::make('direction')->label(__('localization::localization.fields.direction'))
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                IconColumn::make('use_native_digits')->label(__('localization::localization.fields.native_digits'))->boolean(),
            ])
            ->recordActions([
                Action::make('makeDefault')
                    ->label(__('localization::localization.actions.make_default'))
                    ->icon(Heroicon::OutlinedStar)
                    ->visible(fn (Language $record): bool => ! $record->is_default && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->modalDescription(__('localization::localization.help.make_default'))
                    ->action(function (Language $record): void {
                        $record->update(['is_default' => true, 'is_active' => true]);
                        Notification::make()->success()->title(__('localization::localization.notifications.default_changed'))->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Language $record): bool => $record->is_default)
                    ->modalDescription(__('localization::localization.help.delete'))
                    ->using(function (Language $record): bool {
                        try {
                            return (bool) $record->delete();
                        } catch (LanguageRuleViolation $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return false;
                        }
                    }),
            ]);
    }
}
