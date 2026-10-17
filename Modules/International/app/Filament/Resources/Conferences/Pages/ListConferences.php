<?php

namespace Modules\International\Filament\Resources\Conferences\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\International\Filament\Resources\Conferences\ConferenceResource;

class ListConferences extends ListRecords
{
    protected static string $resource = ConferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('international::international.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/international/conferences'))->openUrlInNewTab(),
        ];
    }
}
