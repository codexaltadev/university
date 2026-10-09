<?php

namespace Platform\Theme\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewResult;
use Platform\Context\SiteContext;
use Platform\Theme\Themes;

/**
 * The main homepage through the active theme (theme::home). Microsite and module
 * pages come with their modules.
 */
class HomeController
{
    public function __invoke(): ViewResult
    {
        View::share('context', SiteContext::site());

        return view('theme::home');
    }

    /** Leave the theme preview (FR-THEME-03). */
    public function stopPreview(Request $request): RedirectResponse
    {
        $request->session()->forget(Themes::PREVIEW_SESSION);

        $back = (string) $request->query('back', '/');

        // Only a path on this site (no open redirect)
        return redirect()->to(str_starts_with($back, '/') && ! str_starts_with($back, '//') ? $back : '/');
    }
}
