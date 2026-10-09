<?php

namespace Modules\ActivityLog\Filament\Resources\Activities;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\ActivityLog\Filament\Resources\Activities\Pages\ListActivities;
use Modules\ActivityLog\Filament\Resources\Activities\Pages\ViewActivity;
use Modules\ActivityLog\Filament\Resources\Activities\Schemas\ActivityInfolist;
use Modules\ActivityLog\Filament\Resources\Activities\Tables\ActivitiesTable;
use Platform\Activity\Activity;

/** System › Activity Log (C-04, FR-LOG-04/05/09). Read-only. */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'activity-log';

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function getModelLabel(): string
    {
        return __('activitylog::activity.entry');
    }

    public static function getNavigationLabel(): string
    {
        return __('activitylog::activity.title');
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}
