<?php

namespace Modules\Seo\Http;

use Closure;
use Illuminate\Http\Request;
use Modules\Seo\Models\NotFoundLog;
use Modules\Seo\Support\Redirects;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware (SAD Part B §9 step 2): an address with a redirect answers
 * 301 / 302 before any route runs (FR-SEO-04); a website address that ends in 404
 * is counted in the 404 log (FR-SEO-06). Admin panel and assets are left alone.
 */
class HandleRedirects
{
    /** Missing files of these types are not worth a 404 log row. */
    public const ASSET_EXTENSIONS = ['css', 'js', 'map', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'avif', 'ico', 'woff', 'woff2', 'ttf', 'txt', 'xml'];

    public function __construct(protected Redirects $redirects) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = $request->getPathInfo();

        if (Redirects::isReserved($path)) {
            return $next($request);
        }

        if ($found = $this->redirects->find($path)) {
            $this->redirects->hit($found['id']);
            $url = $found['url'];

            // Keep tracking parameters (?utm_…) unless the destination has its own
            if ($request->getQueryString() && ! str_contains($url, '?')) {
                $url .= '?'.$request->getQueryString();
            }

            return redirect()->to($url, $found['status']);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 404 && ! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::ASSET_EXTENSIONS, true)) {
            NotFoundLog::record($path, $request->headers->get('referer'));
        }

        return $response;
    }
}
