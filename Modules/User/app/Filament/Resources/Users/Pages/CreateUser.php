<?php

namespace Modules\User\Filament\Resources\Users\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Modules\User\Filament\Resources\Users\UserResource;
use Modules\User\Services\UserAccess;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Invited users choose their own password; until then nobody knows this one
        if ($this->data['send_invitation'] ?? true) {
            $data['password'] = Str::password(40);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $access = app(UserAccess::class);
        $access->syncScopes($this->record, $this->data['scope_keys'] ?? []);

        if ($this->data['send_invitation'] ?? true) {
            $access->invite($this->record);
            Notification::make()->title(__('user::user.invitation.sent', ['email' => $this->record->email]))->success()->send();
        }
    }
}
