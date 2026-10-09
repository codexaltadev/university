<?php

namespace Modules\Notification\Filament\Resources\ReminderRules\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Notification\Filament\Resources\ReminderRules\ReminderRuleResource;

class EditReminderRule extends EditRecord
{
    protected static string $resource = ReminderRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
