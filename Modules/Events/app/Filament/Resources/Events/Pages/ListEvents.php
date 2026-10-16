<?php

namespace Modules\Events\Filament\Resources\Events\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Events\Filament\Resources\Events\EventResource;
use Modules\Events\Models\Event;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('events::events.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Event::PATH))->openUrlInNewTab(),
        ];
    }
}
