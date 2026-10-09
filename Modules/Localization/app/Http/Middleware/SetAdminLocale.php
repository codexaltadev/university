<?php

namespace Modules\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin panel in the signed-in user's language (users.locale, UI/UX Part A §9).
 * Admin interface languages are config('platform.locales'); the website's own
 * languages are managed separately in Site › Languages.
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if ($locale && in_array($locale, config('platform.locales', []), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
