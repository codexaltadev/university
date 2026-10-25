<?php

namespace Modules\Admission\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Admission\Models\Circular;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;

/**
 * The module's own pages (FR-ADM-16, DB-03 v1.5 §10): /admission/result (result lookup), /admission/contact,
 * /admission/circulars and /admission/circulars/{slug}. /admission itself and its section
 * pages are Pages (FR-ADM-13), not answered here.
 */
class AdmissionWebsite
{
    public const RESULT = 'admission/result';

    public const CONTACT = 'admission/contact';

    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($context->isMicrosite()) {
            return null;
        }

        if ($rest === self::RESULT) {
            app(Seo::class)->set(['title' => __('admission::admission.result_page.title'), 'path' => '/'.self::RESULT]);

            return response()->view('admission::result', [
                'intro' => filled($intro = settings('admission.result_page_intro')) ? (string) $intro : null,
            ]);
        }

        if ($rest === self::CONTACT) {   // DIU's /admission-contact
            app(Seo::class)->set(['title' => __('admission::admission.contact_page.title'), 'path' => '/'.self::CONTACT]);

            return response()->view('admission::contact');
        }

        if ($rest === Circular::PATH) {
            app(Seo::class)->set(['title' => __('admission::admission.circular.plural'), 'path' => '/'.Circular::PATH]);

            return response()->view('admission::circulars', [
                'circulars' => Circular::query()->frontendVisible()->ordered()->get()->sortBy(fn (Circular $c): int => $c->isClosed() ? 1 : 0)->values(),
            ]);
        }

        if (! Str::startsWith($rest, Circular::PATH.'/')) {
            return null;
        }

        $slug = Str::after($rest, Circular::PATH.'/');
        if (str_contains($slug, '/') || ! ($found = Circular::findBySlug($slug, $locale))) {
            return null;
        }

        [$circular, $current] = $found;
        if (! $circular->isFrontendVisible()) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($circular->url($locale), $locale), 301);   // FR-GEN-32
        }

        app(Seo::class)->forRecord($circular);

        return response()->view('admission::circular', ['circular' => $circular]);
    }
}
