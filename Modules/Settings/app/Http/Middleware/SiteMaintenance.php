<?php

namespace Modules\Settings\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Website maintenance (FR-SET-08): visitors get the message with 503; signed-in
 * users see the website as usual; the admin panel is never affected.
 */
class SiteMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! settings('maintenance.enabled') || $request->user() !== null || $this->isAdmin($request)) {
            return $next($request);
        }

        return response()->view('settings::maintenance', [
            'message' => settings('maintenance.message') ?: __('frontend.maintenance'),
            'siteName' => settings('general.site_name'),
            'duration' => filled($d = settings('maintenance.duration')) ? (string) $d : null,
            'email' => filled($e = settings('maintenance.help_email')) ? (string) $e : null,
        ], 503)->header('Retry-After', '3600');
    }

    protected function isAdmin(Request $request): bool
    {
        $path = trim((string) Filament::getPanel('admin')->getPath(), '/');

        return $request->is($path, "{$path}/*", 'livewire*', 'filament/*', 'up');
    }
}
