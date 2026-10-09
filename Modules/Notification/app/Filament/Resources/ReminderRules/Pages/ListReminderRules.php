<?php

namespace Modules\Notification\Filament\Resources\ReminderRules\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Notification\Filament\Resources\ReminderRules\ReminderRuleResource;

class ListReminderRules extends ListRecords
{
    protected static string $resource = ReminderRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
