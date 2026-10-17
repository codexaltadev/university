<?php

namespace Modules\Newsletter\Filament\Resources\Subscribers\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Newsletter\Filament\Resources\Subscribers\SubscriberResource;

class EditSubscriber extends EditRecord
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
