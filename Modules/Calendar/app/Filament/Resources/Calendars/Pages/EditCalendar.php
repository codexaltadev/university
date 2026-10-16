<?php

namespace Modules\Calendar\Filament\Resources\Calendars\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Calendar\Filament\Resources\Calendars\CalendarResource;
use Modules\Calendar\Models\Calendar;

class EditCalendar extends EditRecord
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('calendar::calendar.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Calendar $record): string => url($record->url()))->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
