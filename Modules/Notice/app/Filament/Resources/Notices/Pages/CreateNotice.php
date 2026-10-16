<?php

namespace Modules\Notice\Filament\Resources\Notices\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Notice\Filament\Resources\Notices\NoticeResource;

class CreateNotice extends CreateRecord
{
    protected static string $resource = NoticeResource::class;
}
