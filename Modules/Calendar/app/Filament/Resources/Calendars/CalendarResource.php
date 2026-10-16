<?php

namespace Modules\Calendar\Filament\Resources\Calendars;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Calendar\Filament\Resources\Calendars\Pages\CreateCalendar;
use Modules\Calendar\Filament\Resources\Calendars\Pages\EditCalendar;
use Modules\Calendar\Filament\Resources\Calendars\Pages\ListCalendars;
use Modules\Calendar\Filament\Resources\Calendars\RelationManagers\EventsRelationManager;
use Modules\Calendar\Filament\Resources\Calendars\Schemas\CalendarForm;
use Modules\Calendar\Filament\Resources\Calendars\Tables\CalendarsTable;
use Modules\Calendar\Models\Calendar;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** Academic › Academic Calendar (M-11, FR-CAL-01/02). */
class CalendarResource extends Resource
{
    protected static ?string $model = Calendar::class;

    protected static string|UnitEnum|null $navigationGroup = 'Academic';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 42;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('calendar::calendar.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('calendar::calendar.plural');
    }

    /** Trash and restore need soft-deleted rows (FR-GEN-02). */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return CalendarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CalendarsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [EventsRelationManager::class, ActivityRelationManager::class];   // FR-CAL-02, FR-GEN-83
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalendars::route('/'),
            'create' => CreateCalendar::route('/create'),
            'edit' => EditCalendar::route('/{record}/edit'),
        ];
    }
}
