<?php

namespace Platform\Theme\Http;

use Closure;
use Illuminate\Http\Request;
use Platform\Theme\Themes;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the theme of this request: the active one, or — only for an admin who may
 * activate themes — the one being previewed (FR-THEME-03, SAD Part B §8.5).
 */
class ApplyTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $preview = $request->hasSession() ? $request->session()->get(Themes::PREVIEW_SESSION) : null;

        app(Themes::class)->usePreview(
            $preview && Themes::allows($request->user(), Themes::PREVIEW_PERMISSION) ? $preview : null
        );

        return $next($request);
    }
}
