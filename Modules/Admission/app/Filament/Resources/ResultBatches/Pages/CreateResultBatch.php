<?php

namespace Modules\Admission\Filament\Resources\ResultBatches\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Admission\Filament\Resources\ResultBatches\ResultBatchResource;

class CreateResultBatch extends CreateRecord
{
    protected static string $resource = ResultBatchResource::class;
}
