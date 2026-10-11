<?php

namespace Modules\Admission\Filament\Resources\ResultBatches\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Admission\Filament\Resources\ResultBatches\ResultBatchResource;

class ListResultBatches extends ListRecords
{
    protected static string $resource = ResultBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
