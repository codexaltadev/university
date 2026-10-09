<?php

namespace Modules\ThemeManager\Http;

use Platform\Theme\Themes;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** A theme's screenshot for the Themes screen; theme folders are not public. */
class ScreenshotController
{
    public function __invoke(string $slug, Themes $themes): BinaryFileResponse
    {
        abort_unless(Themes::allows(auth()->user(), 'View:Theme'), 403);

        $file = $themes->find($slug)?->manifest()->screenshot();
        abort_if($file === null, 404);

        return response()->file($file, ['Cache-Control' => 'private, max-age=3600']);
    }
}
