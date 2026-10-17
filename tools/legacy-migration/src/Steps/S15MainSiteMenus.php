<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\File;
use Modules\HeaderFooter\Support\Regions;
use Modules\Menu\Support\MenuEditor;
use Platform\Context\FallbackResolver;

/**
 * S15M Main site menus (Data Migration Plan S15, owner 2026-10-17: the default theme is a copy of
 * daffodilvarsity.edu.bd). The live site keeps its main menus in code, not in the legacy database,
 * so they come from a snapshot taken from the live pages (data/diu-site-menus.json):
 * primary = six mega menus, topbar = the utility links, quick-links-icon = the Services grid,
 * footer-1…4 = the footer link columns (with their headings in the footer region), the
 * copyright line and the social links. Running it again replaces these menus with the snapshot.
 */
class S15MainSiteMenus extends Step
{
    public const SNAPSHOT = __DIR__.'/../../data/diu-site-menus.json';

    /** Live (lucide) icon names → <x-theme::icon> names */
    public const ICONS = ['clipboard-list' => 'clipboard', 'calculator' => 'calculator'];

    public static function key(): string
    {
        return 'S15M';
    }

    public static function title(): string
    {
        return 'Main site menus (live site snapshot)';
    }

    public static function requires(): array
    {
        return ['menu', 'header-footer'];
    }

    protected function handle(): void
    {
        $data = json_decode(File::get(self::SNAPSHOT), true);
        $this->report->source('diu-site-menus.json', count($data['primary']) + count($data['topbar']) + count($data['services']) + count($data['footer_columns']));

        $this->menu('primary', $data['primary'], 'mega');
        $this->menu('topbar', $data['topbar']);
        $this->menu('quick-links-icon', array_map(fn (array $s): array => [...$s, 'icon' => self::ICONS[$s['icon']] ?? $s['icon']], $data['services']));

        $columns = [];
        foreach (array_slice($data['footer_columns'], 0, 4) as $i => $column) {
            $location = 'footer-'.($i + 1);
            $this->menu($location, $column['children']);
            $columns[] = ['heading' => ['en' => $column['title']], 'blocks' => [['type' => 'menu', 'menu_location' => $location]]];
        }
        $this->footer($columns, (string) $data['copyright']);
        $this->social($data['social']);

        app(FallbackResolver::class)->flush();
        $this->report->review("Snapshot of {$data['source']} taken {$data['taken_at']}: links to pages this site does not have yet go to the same path here; point them to module pages in Content › Menus as those pages are checked.");
    }

    /** @param list<array<string, mixed>> $items */
    protected function menu(string $location, array $items, ?string $style = null): void
    {
        $editor = app(MenuEditor::class);
        $menu = $editor->own('site:0', $location) ?? $editor->create('site:0', $location);
        $created = $menu->wasRecentlyCreated;

        $editor->save($menu, array_map(fn (array $item): array => $this->item($item, 1), $items), $style ?? 'dropdown');
        $this->report->saved('menu_menus', $created);
    }

    /** @param array<string, mixed> $item */
    protected function item(array $item, int $depth): array
    {
        $url = $item['url'] ?? null;

        return [
            'type' => $url === null ? 'heading' : 'url',
            'url' => $url,
            'title' => ['en' => (string) $item['title']],
            'icon' => $item['icon'] ?? null,
            'open_in_new_tab' => (bool) ($item['new_tab'] ?? false),
            'options' => $depth === 1 && ! empty($item['mega']) ? ['display' => 'mega', 'columns' => max(2, min(6, (int) ($item['columns'] ?? 4)))] : [],
            'is_active' => true,
            'children' => array_map(fn (array $child): array => $this->item($child, $depth + 1), (array) ($item['children'] ?? [])),
        ];
    }

    /** @param list<array<string, mixed>> $columns */
    protected function footer(array $columns, string $copyright): void
    {
        $regions = app(Regions::class);
        $footer = $regions->own('site:0', 'footer') ?? $regions->create('site:0', 'footer');
        $copyright = preg_replace('/(\d{4})/', '{year}', preg_replace('/\.(?=\S)/', '. ', $copyright), 1);

        $footer->config = [...(array) $footer->config, 'columns' => $columns];
        $footer->setTranslation('copyright', 'en', $copyright);
        $footer->save();
        $this->report->saved('core_regions', $footer->wasRecentlyCreated);
    }

    /** @param list<array{platform: string, url: string}> $links */
    protected function social(array $links): void
    {
        settings()->save('social', ['links' => array_map(fn (array $l): array => [
            'platform' => $l['platform'],
            'url' => $l['url'],
            'label' => ucfirst($l['platform']),
        ], $links)]);
        $this->report->saved('core_settings', false);
    }
}
