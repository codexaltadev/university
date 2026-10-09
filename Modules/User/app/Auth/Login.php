<?php

namespace Modules\User\Auth;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as FilamentLogin;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Modules\User\Services\LoginLock;

/**
 * Admin sign-in with the platform rules:
 * - an account is locked for 15 minutes after 5 wrong passwords (NFR-SEC-03),
 * - an inactive account is told so, after a correct password (AC-USER-01).
 */
class Login extends FilamentLogin
{
    public function authenticate(): ?LoginResponse
    {
        $lock = app(LoginLock::class);
        $email = (string) ($this->data['email'] ?? '');

        if ($lock->isLocked($email)) {
            throw ValidationException::withMessages([
                'data.email' => __('user::user.login.locked', ['minutes' => $lock->minutesLeft($email)]),
            ]);
        }

        return parent::authenticate();
    }

    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        if ($user instanceof User && ! $user->is_active) {
            throw ValidationException::withMessages(['data.email' => __('user::user.login.inactive')]);
        }

        return parent::isUserAllowedToAccessPanel($user);
    }
}
