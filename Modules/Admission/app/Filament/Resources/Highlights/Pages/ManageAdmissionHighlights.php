<?php

namespace Modules\Admission\Filament\Resources\Highlights\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Admission\Filament\Resources\Highlights\AdmissionHighlightResource;

class ManageAdmissionHighlights extends ManageRecords
{
    protected static string $resource = AdmissionHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
