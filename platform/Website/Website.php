<?php

namespace Platform\Website;

use Closure;
use Platform\Context\SiteContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Website addresses no module route claimed (SAD Part B §9 request flow): after the
 * microsite part (/department/cse) is resolved, each handler may answer the rest
 * (e.g. the Page module: about/history). First answer wins; none → 404.
 *
 *   app(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale): ?Response => …);
 */
class Website
{
    /** @var list<Closure(string, SiteContext, string): ?Response> */
    protected array $handlers = [];

    public function handle(Closure $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        foreach ($this->handlers as $handler) {
            if (($response = $handler($rest, $context, $locale)) !== null) {
                return $response;
            }
        }

        return null;
    }
}
