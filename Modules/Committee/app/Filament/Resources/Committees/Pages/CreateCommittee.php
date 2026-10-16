<?php

namespace Modules\Committee\Filament\Resources\Committees\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Committee\Filament\Resources\Committees\CommitteeResource;
use Modules\Committee\Models\Committee;

class CreateCommittee extends CreateRecord
{
    protected static string $resource = CommitteeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['sort_order' => (int) Committee::query()->withoutGlobalScopes()->max('sort_order') + 1];
    }
}
