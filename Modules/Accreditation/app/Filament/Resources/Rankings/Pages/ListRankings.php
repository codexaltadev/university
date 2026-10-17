<?php

namespace Modules\Accreditation\Filament\Resources\Rankings\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accreditation\Filament\Resources\Rankings\RankingResource;

class ListRankings extends ListRecords
{
    protected static string $resource = RankingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
