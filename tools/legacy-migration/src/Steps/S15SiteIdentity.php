<?php

namespace Legacy\Migration\Steps;

use Modules\Settings\Groups\GeneralSettings;

/**
 * S15S Site identity (Settings › General). The old site wrote its name in code — project_diu has
 * no settings table for it — so the name is set here as the live site shows it: "Daffodil
 * International University" for the site name (page titles, "Registrar Office - …" under an office's
 * name, the footer's copyright) and the university's full name. Only while they are still the
 * installer's default (the app name) or blank: a name typed in Admin is never replaced.
 */
class S15SiteIdentity extends Step
{
    public const NAME = ['en' => 'Daffodil International University', 'bn' => 'ড্যাফোডিল ইন্টারন্যাশনাল ইউনিভার্সিটি'];

    public static function key(): string
    {
        return 'S15S';
    }

    public static function title(): string
    {
        return 'Site name and university name (live site)';
    }

    public static function requires(): array
    {
        return ['settings'];
    }

    protected function handle(): void
    {
        $default = fn (mixed $name): bool => blank($name) || $name === config('app.name') || $name === 'University Website Management System';
        $values = array_filter([
            'site_name' => $default(settings('general.site_name')) ? self::NAME : null,
            'university_name' => $default(settings('general.university_name')) ? self::NAME : null,
        ]);

        if ($values === []) {
            $this->report->review('Site name and university name already set in Settings › General: left as they are.');

            return;
        }

        $changed = settings()->saveFor(new GeneralSettings, $values);
        $this->report->review('Settings › General: '.(implode(', ', $changed) ?: 'nothing').' set to "'.self::NAME['en'].'".');
    }
}
