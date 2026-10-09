<?php

namespace Platform\Theme\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Platform\Theme\Themes;

/** Leave the theme preview (FR-THEME-03). The website itself: Platform\Website\WebsiteController. */
class HomeController
{
    public function stopPreview(Request $request): RedirectResponse
    {
        $request->session()->forget(Themes::PREVIEW_SESSION);

        $back = (string) $request->query('back', '/');

        // Only a path on this site (no open redirect)
        return redirect()->to(str_starts_with($back, '/') && ! str_starts_with($back, '//') ? $back : '/');
    }
}
