<?php

namespace Modules\Calendar\Filament\Resources\Calendars\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Calendar\Filament\Resources\Calendars\CalendarResource;

/** After creating, the edit page opens so the dates can be added. */
class CreateCalendar extends CreateRecord
{
    protected static string $resource = CalendarResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
