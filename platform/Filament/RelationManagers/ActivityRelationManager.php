<?php

namespace Platform\Filament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Platform\Activity\Activity;

/**
 * "Activity" tab on a record's edit page (FR-GEN-83): who changed what, when.
 * Shared platform component; a resource adds it to getRelations().
 * Shown to users who may view the activity log.
 */
class ActivityRelationManager extends RelationManager
{
    protected static string $relationship = 'activitiesAsSubject';

    protected static ?string $title = 'Activity';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Auth::user()?->can('viewAny', Activity::class) ?? false;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')->label(__('When'))->dateTime()->description(fn (Activity $record): string => $record->created_at->diffForHumans()),
                TextColumn::make('causer.name')->label(__('Who'))->placeholder(__('System')),
                TextColumn::make('event')->label(__('What'))->badge(),
                ViewColumn::make('attribute_changes')->label(__('Changes'))->view('platform::activity-changes'),
            ]);
    }
}
