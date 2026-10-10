<?php

namespace Modules\Scholarship\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Scholarship\Models\Scholarship;
use Platform\Context\SiteContext;
use Platform\Seo\Seo;

/**
 * /scholarships (FR-FEE-10): the top-level scholarships in order; /scholarships/{slug}: one
 * scholarship (details, eligibility, how to apply, apply link, files) with its parts — open
 * ones first, closed ones after. An old address answers 301 to the current one.
 */
class ScholarshipWebsite
{
    public function respond(string $rest, SiteContext $context, string $locale): Response|RedirectResponse|null
    {
        $rest = trim($rest, '/');
        if ($context->isMicrosite() || ($rest !== Scholarship::PATH && ! Str::startsWith($rest, Scholarship::PATH.'/'))) {
            return null;
        }

        $slug = Str::after($rest, Scholarship::PATH.'/');
        if ($rest === Scholarship::PATH) {
            app(Seo::class)->set(['title' => __('scholarship::scholarship.plural'), 'path' => '/'.Scholarship::PATH]);

            return response()->view('scholarship::listing', [
                'scholarships' => Scholarship::query()->frontendVisible()->topLevel()->ordered()
                    ->withCount(['parts' => fn ($q) => $q->frontendVisible()])->get(),
            ]);
        }

        if (str_contains($slug, '/') || ! ($found = Scholarship::findBySlug($slug, $locale))) {
            return null;
        }

        [$scholarship, $current] = $found;
        if (! $scholarship->isFrontendVisible() || ($scholarship->parent && ! $scholarship->parent->isFrontendVisible())) {
            return null;
        }
        if (! $current) {
            return redirect(Seo::localized($scholarship->url($locale), $locale), 301);   // FR-GEN-32
        }

        app(Seo::class)->forRecord($scholarship);

        return response()->view('scholarship::show', [
            'scholarship' => $scholarship,
            'parent' => $scholarship->parent,
            'parts' => $scholarship->parts()->frontendVisible()->get()->sortBy(fn (Scholarship $s): int => $s->isClosed() ? 1 : 0)->values(),
        ]);
    }
}
