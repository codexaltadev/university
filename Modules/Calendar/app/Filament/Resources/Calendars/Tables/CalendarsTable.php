<?php

namespace Modules\Calendar\Filament\Resources\Calendars\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Calendar\Models\Calendar;

/** Calendars newest year first; dates count, faculties, views (FR-CNT-C08 "Most viewed" by sorting). */
class CalendarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('events')->withViewTotal())
            ->defaultSort(fn ($query) => $query->newestFirst())
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('calendar::calendar.fields.title'))->searchable()->wrap()
                    ->description(fn (Calendar $record): ?string => $record->duration_label),
                TextColumn::make('session_label')->label(__('calendar::calendar.fields.session_label'))->searchable()->toggleable(),
                TextColumn::make('year')->label(__('calendar::calendar.fields.year'))->sortable(),
                TextColumn::make('faculties')->label(__('calendar::calendar.fields.faculties'))->badge()
                    ->state(fn (Calendar $record): array => static::facultyNames($record))
                    ->placeholder(__('calendar::calendar.every_faculty')),
                TextColumn::make('events_count')->label(__('calendar::calendar.fields.events'))->numeric(),
                TextColumn::make('view_total')->label(__('calendar::calendar.fields.views'))->numeric()->sortable()->toggleable(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([
                SelectFilter::make('year')->label(__('calendar::calendar.fields.year'))
                    ->options(fn (): array => Calendar::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all()),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    /** @return list<string> */
    protected static function facultyNames(Calendar $record): array
    {
        $class = 'Modules\\Faculty\\Models\\Faculty';
        $ids = $record->facultyIds();

        return $ids !== [] && class_exists($class)
            ? $class::query()->withoutGlobalScopes()->whereKey($ids)->ordered()->get()->map(fn ($f): string => (string) ($f->short_name ?: $f->name))->all()
            : [];
    }
}
