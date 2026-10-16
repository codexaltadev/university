<?php

namespace Modules\Administration\Filament\Resources\Members\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Administration\Filament\Resources\Members\MemberResource;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;
}
