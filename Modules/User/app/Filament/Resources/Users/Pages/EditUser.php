<?php

namespace Modules\User\Filament\Resources\Users\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\User\Filament\Resources\Users\UserResource;
use Modules\User\Services\UserAccess;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(UserAccess::class)->syncScopes($this->record, $this->data['scope_keys'] ?? []);
    }
}
