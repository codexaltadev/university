<?php

namespace Modules\Calendar\Filament\Resources\Calendars\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Calendar\Filament\Resources\Calendars\CalendarResource;
use Modules\Calendar\Models\Calendar;

class ListCalendars extends ListRecords
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('calendar::calendar.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Calendar::PATH))->openUrlInNewTab(),
        ];
    }
}
