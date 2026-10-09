<?php

namespace Modules\User\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * 2FA is compulsory for Super Admins when the setting is on (NFR-SEC-04); optional
 * for everyone else. Filament attaches its MFA middleware when routes are
 * registered, so the panel always registers it and this decides per request.
 */
class RequireTwoFactorForSuperAdmins extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Filament::auth()->user();

        if (! config('platform.security.super_admin_requires_2fa') || ! ($user instanceof User && $user->isSuperAdmin())) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
