<?php

namespace Modules\Seo\Filament\Resources\Redirects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\Seo\Models\Redirect;

class RedirectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('source_path')->label(__('seo::seo.fields.source_path'))->searchable()->limit(60)->copyable(),
                TextColumn::make('destination')->label(__('seo::seo.fields.destination'))
                    ->state(fn (Redirect $record): string => $record->destination() ?? '⚠ '.__('seo::seo.redirects.missing_target'))
                    ->limit(60),
                TextColumn::make('status_code')->label(__('seo::seo.fields.status_code'))->badge()
                    ->color(fn (int $state): string => $state === 301 ? 'success' : 'warning'),
                TextColumn::make('hits')->label(__('seo::seo.fields.hits'))->numeric()->sortable(),
                TextColumn::make('last_hit_at')->label(__('seo::seo.fields.last_hit_at'))->since()->sortable()->toggleable(),
                TextColumn::make('source')->label(__('seo::seo.fields.source'))->badge()->color('gray')
                    ->formatStateUsing(fn (string $state): string => __("seo::seo.sources.{$state}"))->toggleable(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ])
            ->filters([
                SelectFilter::make('source')->label(__('seo::seo.fields.source'))
                    ->options(collect(Redirect::SOURCES)->mapWithKeys(fn (string $s): array => [$s => __("seo::seo.sources.{$s}")])->all()),
                SelectFilter::make('status_code')->label(__('seo::seo.fields.status_code'))->options([301 => '301', 302 => '302']),
                TernaryFilter::make('is_active')->label(__('platform::relations.active')),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
