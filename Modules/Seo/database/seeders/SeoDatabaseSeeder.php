<?php

namespace Modules\Seo\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Seo\Settings\SeoSettings;
use Platform\Localization\Languages;

/**
 * Default SEO (Settings › SEO): the site's default description in every active language, built
 * from the university's name, for pages without their own (search results, shares). Only an empty
 * value is filled — what an admin wrote is never replaced, so it can run again at any time.
 * Redirects come from the admin or a CSV.
 */
class SeoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $settings = settings();
        $description = array_filter((array) ($settings->group('seo')['default_description'] ?? []));

        foreach (app(Languages::class)->codes() as $locale) {
            if (filled($description[$locale] ?? null)) {
                continue;
            }

            $name = $settings->get('general.university_name', locale: $locale) ?: $settings->get('general.site_name', locale: $locale);
            if (blank($name)) {
                continue;
            }

            $description[$locale] = Str::limit(trans('seo::seo.seed.description', ['name' => $name], $locale), 157);
        }

        if ($description !== []) {
            $settings->saveFor(new SeoSettings, ['default_description' => $description]);
        }
    }
}
