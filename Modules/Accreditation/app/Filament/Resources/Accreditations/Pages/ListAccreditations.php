<?php

namespace Modules\Accreditation\Filament\Resources\Accreditations\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Accreditation\Filament\Resources\Accreditations\AccreditationResource;

class ListAccreditations extends ListRecords
{
    protected static string $resource = AccreditationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('accreditation::accreditation.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/accreditations'))->openUrlInNewTab(),
        ];
    }
}
