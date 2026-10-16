<?php

namespace Modules\Leadership\Filament\Resources\Positions\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Leadership\Filament\Resources\Positions\PositionResource;
use Modules\Leadership\Models\LeadershipTerm;

class ListPositions extends ListRecords
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('leadership::leadership.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.LeadershipTerm::PATH))->openUrlInNewTab(),
        ];
    }
}
