<?php

namespace Modules\Waiver\Support;

use Illuminate\Http\Response;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;

/** /waiver-calculator on the main site (FR-FEE-06): the page holding the Livewire calculator. */
class CalculatorWebsite
{
    public const PAGE = 'waiver-calculator';

    public static function respond(string $rest, SiteContext $context): ?Response
    {
        if ($context->isMicrosite() || trim($rest, '/') !== self::PAGE) {
            return null;
        }

        app(Seo::class)->set(['title' => __('waiver::waiver.calculator.title'), 'path' => '/'.self::PAGE]);

        return response()->view('waiver::calculator.page');
    }
}
