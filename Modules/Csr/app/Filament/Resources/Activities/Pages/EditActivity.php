<?php

namespace Modules\Csr\Filament\Resources\Activities\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Csr\Filament\Resources\Activities\ActivityResource;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
