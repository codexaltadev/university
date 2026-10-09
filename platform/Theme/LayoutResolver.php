<?php

namespace Platform\Theme;

use Platform\Registry\Registry;
use Platform\Settings\Settings;

/**
 * Module layout selection (FR-THEME-08/09/11, SAD Part B §8.3). A module offers
 * layouts for its pages ($r->layouts('faq.listing', ['accordion', 'list'])); the
 * active theme may add more in theme.json. The admin's choice is stored in
 * core_settings group "layouts" as {"layout": …, "options": {…}}; a choice the
 * current theme cannot draw falls back to the module's default (its first layout).
 *
 *   $layout = app(LayoutResolver::class)->for('faq.listing');   // "accordion"
 *   return view("faq::listing.{$layout}", …);
 */
class LayoutResolver
{
    public const GROUP = 'layouts';

    public function __construct(protected Registry $registry, protected Themes $themes, protected Settings $settings) {}

    /** @return array<string, array{module: ?string, layouts: list<string>}> page => who offers it and its layouts */
    public function pages(): array
    {
        $pages = [];

        foreach ($this->registry->all('layouts') as $entry) {
            $pages[$entry['page']] = [
                'module' => $entry['module'],
                'layouts' => array_values(array_unique([...($pages[$entry['page']]['layouts'] ?? []), ...$entry['layouts']])),
            ];
        }

        foreach ($this->themes->manifest()->layouts() as $page => $layouts) {
            if (! isset($pages[$page])) {
                continue;   // a page of a module that is not active (T-4)
            }

            $pages[$page]['layouts'] = array_values(array_unique([...$pages[$page]['layouts'], ...$layouts]));
        }

        ksort($pages);

        return $pages;
    }

    /** @return list<string> */
    public function available(string $page): array
    {
        return $this->pages()[$page]['layouts'] ?? [];
    }

    /** The module's own first layout. */
    public function default(string $page): ?string
    {
        foreach ($this->registry->all('layouts') as $entry) {
            if ($entry['page'] === $page && $entry['layouts'] !== []) {
                return $entry['layouts'][0];
            }
        }

        return null;
    }

    /** The layout to draw now (FR-THEME-10/11). */
    public function for(string $page): ?string
    {
        $chosen = $this->stored($page)['layout'] ?? null;

        return $chosen !== null && in_array($chosen, $this->available($page), true) ? $chosen : $this->default($page);
    }

    /** @return array<string, mixed> per row, sidebar … (FR-THEME-09) */
    public function options(string $page): array
    {
        return (array) ($this->stored($page)['options'] ?? []);
    }

    /** @param array<string, mixed> $options */
    public function choose(string $page, string $layout, array $options = []): void
    {
        $this->settings->save(self::GROUP, [$page => ['layout' => $layout, 'options' => $options]]);
    }

    /** @return array<string, mixed>|null */
    protected function stored(string $page): ?array
    {
        $value = $this->settings->group(self::GROUP)[$page] ?? null;

        return is_array($value) ? $value : null;
    }
}
