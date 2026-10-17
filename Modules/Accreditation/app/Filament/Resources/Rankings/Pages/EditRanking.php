<?php

namespace Modules\Accreditation\Filament\Resources\Rankings\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Accreditation\Filament\Resources\Rankings\RankingResource;

class EditRanking extends EditRecord
{
    protected static string $resource = RankingResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
