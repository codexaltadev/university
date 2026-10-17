<?php

namespace Modules\Newsletter\Support;

use Illuminate\Http\Response;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;

/** /newsletter (FR-NLT-01): the sign-up page — on the main site only. */
class NewsletterWebsite
{
    public const PATH = 'newsletter';

    public function respond(string $rest, SiteContext $context, string $locale): ?Response
    {
        if (trim($rest, '/') !== self::PATH || $context->isMicrosite()) {
            return null;
        }
        app(Seo::class)->set(['title' => __('newsletter::newsletter.title'), 'path' => '/'.self::PATH]);

        return response()->view('newsletter::index', ['intro' => settings('newsletter.intro')]);
    }
}
