<?php

namespace Modules\Waiver\Filament\Resources\WaiverRules\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Waiver\Filament\Resources\WaiverRules\Pages\Concerns\SavesRuleConditions;
use Modules\Waiver\Filament\Resources\WaiverRules\WaiverRuleResource;

class CreateWaiverRule extends CreateRecord
{
    use SavesRuleConditions;

    protected static string $resource = WaiverRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->takeConditions($data);
    }

    protected function afterCreate(): void
    {
        $this->storeConditions($this->record);
    }
}
