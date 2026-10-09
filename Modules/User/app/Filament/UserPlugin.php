<?php

namespace Modules\User\Filament;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\User\Auth\Login;
use Modules\User\Filament\Pages\EditProfile;
use Modules\User\Filament\Resources\Users\UserResource;
use Modules\User\Http\Middleware\EnsureUserIsActive;
use Modules\User\Http\Middleware\RequireTwoFactorForSuperAdmins;

/**
 * User management (C-02): the Users screen and the panel's sign-in rules —
 * login lock, password reset / invitations, profile and 2FA (NFR-SEC-02..04).
 */
class UserPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'user';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([UserResource::class])
            ->login(Login::class)
            ->passwordReset()
            ->profile(EditProfile::class)
            // Registered as "required" so the middleware and set-up page exist;
            // RequireTwoFactorForSuperAdmins decides per request who must have it
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(RequireTwoFactorForSuperAdmins::class)
            // Before Filament's Authenticate (which would answer 403): sign the user out instead
            ->middleware([EnsureUserIsActive::class], isPersistent: true);
    }

    public function boot(Panel $panel): void {}
}
