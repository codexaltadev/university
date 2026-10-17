<?php

namespace Modules\Csr\Filament\Resources\Activities\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Csr\Filament\Resources\Activities\ActivityResource;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('csr::csr.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/csr'))->openUrlInNewTab(),
        ];
    }
}
