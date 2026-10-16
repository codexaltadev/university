<?php

namespace Modules\Committee\Filament\Resources\Committees\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Committee\Filament\Resources\Committees\CommitteeResource;
use Modules\Committee\Models\Committee;

class ListCommittees extends ListRecords
{
    protected static string $resource = CommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('committee::committee.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Committee::PATH))->openUrlInNewTab(),
        ];
    }
}
