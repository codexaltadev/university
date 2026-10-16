<?php

namespace Platform\Modules;

use Illuminate\Support\Facades\File;

/**
 * A module's own public files (SAD Part A §9 "Asset path modules/{slug}/"): prebuilt JS, CSS,
 * fonts or libraries in the module's public/ folder are copied to public/modules/{slug} when it
 * is activated (and by uwms:modules:sync), removed on uninstall. No CDN, no build step on the
 * server. In Blade: module_asset('documents', 'pdfjs/pdf.min.mjs').
 */
class ModuleAssets
{
    public function publish(Manifest $manifest): bool
    {
        $source = rtrim($manifest->path, '/').'/public';
        if (! is_dir($source)) {
            return false;
        }

        $target = $this->path($manifest->slug());
        File::deleteDirectory($target);   // files removed in a new version go too
        File::ensureDirectoryExists(dirname($target));

        return File::copyDirectory($source, $target);
    }

    public function remove(string $slug): void
    {
        File::deleteDirectory($this->path($slug));
    }

    public function path(string $slug, string $file = ''): string
    {
        return public_path('modules/'.$slug.($file !== '' ? '/'.ltrim($file, '/') : ''));
    }

    /** Public URL, with the file's time as version so browsers fetch a new copy after an update. */
    public function url(string $slug, string $file): string
    {
        $path = $this->path($slug, $file);

        return asset('modules/'.$slug.'/'.ltrim($file, '/')).(is_file($path) ? '?v='.filemtime($path) : '');
    }
}
