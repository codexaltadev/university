<?php

namespace Modules\ActivityLog\Filament\Resources\Activities\Tables;

use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Platform\Activity\Activity;
use Platform\Models\CoreModule;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label(__('activitylog::activity.fields.when'))
                    ->dateTime()
                    ->description(fn (Activity $record): string => $record->created_at->diffForHumans())
                    ->sortable(),
                TextColumn::make('causer.name')->label(__('activitylog::activity.fields.who'))
                    ->placeholder(__('activitylog::activity.system')),
                TextColumn::make('event')->label(__('activitylog::activity.fields.what'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created', 'restored', 'activated', 'login' => 'success',
                        'deleted', 'force_deleted', 'login_failed' => 'danger',
                        'deactivated', 'logout' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('module_slug')->label(__('activitylog::activity.fields.module'))->placeholder('—'),
                TextColumn::make('subject')->label(__('activitylog::activity.fields.record'))
                    ->state(fn (Activity $record): string => $record->subject_type ? "{$record->subject_type} #{$record->subject_id}" : '—'),
                ViewColumn::make('attribute_changes')->label(__('activitylog::activity.fields.changes'))->view('platform::activity-changes'),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('causer_id')->label(__('activitylog::activity.fields.who'))
                    ->options(fn (): array => User::withTrashed()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('module_slug')->label(__('activitylog::activity.fields.module'))
                    ->options(fn (): array => CoreModule::orderBy('name')->pluck('name', 'slug')->all()),
                SelectFilter::make('event')->label(__('activitylog::activity.fields.what'))
                    ->options(fn (): array => Activity::query()->distinct()->orderBy('event')->pluck('event', 'event')->filter()->all())
                    ->multiple(),
                SelectFilter::make('log_name')->label(__('activitylog::activity.fields.log'))
                    ->options(['model' => __('activitylog::activity.logs.model'), 'auth' => __('activitylog::activity.logs.auth'), 'system' => __('activitylog::activity.logs.system')]),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label(__('activitylog::activity.fields.from')),
                        DatePicker::make('until')->label(__('activitylog::activity.fields.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
