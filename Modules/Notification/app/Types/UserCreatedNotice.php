<?php

namespace Modules\Notification\Types;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Platform\Notifications\NotificationType;
use Platform\Notifications\Recipients;

/** FR-NOTI-04: a new admin user — everyone who manages users, except whoever created it. */
class UserCreatedNotice extends NotificationType
{
    public static function key(): string
    {
        return 'core.user_created';
    }

    public static function label(): string
    {
        return __('notification::notification.types.user_created');
    }

    public static function placeholders(): array
    {
        return ['name' => 'New user\'s name', 'email' => 'New user\'s email', 'by' => 'Who created the account'];
    }

    public static function defaultSubject(string $locale): string
    {
        return __('notification::notification.texts.user_created.subject', [], $locale);
    }

    public static function defaultBody(string $locale): string
    {
        return __('notification::notification.texts.user_created.body', [], $locale);
    }

    public function recipients(): Collection
    {
        return Recipients::permitted('ViewAny:User')
            ->reject(fn (User $user): bool => $user->id === $this->subject?->getKey() || $user->id === ($this->data['by_id'] ?? null));
    }

    public function url(): ?string
    {
        return $this->subject && Route::has('filament.admin.resources.users.edit')
            ? route('filament.admin.resources.users.edit', $this->subject)
            : null;
    }
}
