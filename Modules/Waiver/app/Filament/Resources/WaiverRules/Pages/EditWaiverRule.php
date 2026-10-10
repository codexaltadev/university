<?php

namespace Modules\Waiver\Filament\Resources\WaiverRules\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Waiver\Filament\Resources\WaiverRules\Pages\Concerns\SavesRuleConditions;
use Modules\Waiver\Filament\Resources\WaiverRules\WaiverRuleResource;

class EditWaiverRule extends EditRecord
{
    use SavesRuleConditions;

    protected static string $resource = WaiverRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, 'waiver_category_ids' => $this->record->categories->pluck('id')->all(), 'genders' => $this->record->genders()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->takeConditions($data);
    }

    protected function afterSave(): void
    {
        $this->storeConditions($this->record);
    }
}
