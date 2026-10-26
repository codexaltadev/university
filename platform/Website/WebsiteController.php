<?php

namespace Platform\Website;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Platform\Context\FallbackResolver;
use Platform\Context\MicrositeOwners;
use Platform\Context\MicrositeResolver;
use Platform\Context\SiteContext;
use Platform\Events\SearchPerformed;
use Platform\Localization\Languages;
use Platform\Search\SearchIndex;
use Platform\Seo\Seo;
use Platform\Seo\Sitemap;
use Platform\Theme\Themes;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public website: homepage, language prefix (/bn/…, FR-GEN-33), microsite homepages,
 * module handlers (pages), sitemap.xml and robots.txt.
 */
class WebsiteController
{
    public function home(): Response
    {
        View::share('context', SiteContext::site());
        app(Seo::class)->set(['home' => true]);

        return response()->view('theme::home');
    }

    /** Everything without its own route. */
    public function resolve(Request $request): Response
    {
        $path = '/'.ltrim($request->path(), '/');
        $first = explode('/', ltrim($path, '/'))[0];
        $languages = app(Languages::class);

        if ($first !== '' && in_array($first, $languages->activeCodes(), true)) {
            app()->setLocale($first);
        }

        $resolved = app(MicrositeResolver::class)->resolve($path, app()->getLocale());
        abort_if($resolved === null, 404);

        if ($resolved['redirect']) {
            return redirect($resolved['redirect'], 301);   // old slug of the entity (FR-GEN-32)
        }

        $context = $resolved['context'];
        View::share('context', $context);

        if ($resolved['rest'] === '') {
            return $context->isMicrosite() ? $this->micrositeHome($context) : $this->home();
        }

        return app(Website::class)->respond($resolved['rest'], $context, app()->getLocale()) ?? abort(404);
    }

    /** FR-SRCH-02/06: grouped results, module filter, "only in this entity", pagination. */
    public function search(Request $request): Response
    {
        View::share('context', SiteContext::site());
        app(Seo::class)->set(['title' => __('platform::search.title'), 'noindex' => true]);

        $module = $request->string('module')->toString() ?: null;
        $scope = $request->string('in')->toString() ?: null;
        $results = app(SearchIndex::class)->search(
            $request->string('q')->toString(),
            module: $module,
            ownerKey: $scope,
            page: max(1, $request->integer('page', 1)),
        );

        $results['items']->withPath(route('search'))->appends(array_filter(['q' => $results['query'], 'module' => $module, 'in' => $scope]));

        if ($results['query'] !== '' && $results['items']->currentPage() === 1) {
            SearchPerformed::dispatch($results['query'], $results['total'], $module, $scope);   // what people look for (and miss)
        }

        return response()->view('theme::search', [
            'results' => $results,
            'shortcuts' => $module || $scope ? [] : app(SearchIndex::class)->shortcuts($results['query']),   // the page a visitor most likely wants
            'module' => $module,
            'scope' => $scope,
            'scopeLabel' => $scope ? MicrositeOwners::label($scope) : null,
        ]);
    }

    public function sitemap(): Response
    {
        return response(app(Sitemap::class)->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** FR-SEO-03: the admin's text, else allow everything but the admin panel. */
    public function robots(): Response
    {
        $text = trim((string) settings('seo.robots_txt')) ?: "User-agent: *\nDisallow: /admin\n";

        if (! str_contains(strtolower($text), 'sitemap:')) {
            $text .= "\nSitemap: ".url('/sitemap.xml');
        }

        return response($text."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** BR-MS-05: the entity's microsite template wraps its homepage. */
    protected function micrositeHome(SiteContext $context): Response
    {
        $templates = app(Themes::class)->manifest()->micrositeTemplates();
        $chosen = app(FallbackResolver::class)->resolve('template', null, $context);
        $template = in_array($chosen, $templates, true) ? $chosen : $templates[0];

        $context->entity && method_exists($context->entity, 'seo')
            ? app(Seo::class)->forRecord($context->entity)
            : app(Seo::class)->set(['title' => $context->entity?->name]);

        return response()->view(view()->exists("theme::microsite-templates.{$template}") ? "theme::microsite-templates.{$template}" : 'theme::microsite-templates.standard');
    }
}
