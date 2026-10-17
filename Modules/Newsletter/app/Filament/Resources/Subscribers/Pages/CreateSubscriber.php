<?php

namespace Modules\Newsletter\Filament\Resources\Subscribers\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Newsletter\Filament\Resources\Subscribers\SubscriberResource;

class CreateSubscriber extends CreateRecord
{
    protected static string $resource = SubscriberResource::class;
}
