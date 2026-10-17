<?php

namespace Modules\Newsletter\Filament\Resources\MailingLists\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Newsletter\Filament\Resources\MailingLists\MailingListResource;

class ManageMailingLists extends ManageRecords
{
    protected static string $resource = MailingListResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
