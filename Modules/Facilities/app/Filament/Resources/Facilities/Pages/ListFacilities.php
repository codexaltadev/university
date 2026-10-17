<?php

namespace Modules\Facilities\Filament\Resources\Facilities\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Facilities\Filament\Resources\Facilities\FacilityResource;

class ListFacilities extends ListRecords
{
    protected static string $resource = FacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('facilities::facilities.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/facilities'))->openUrlInNewTab(),
        ];
    }
}
