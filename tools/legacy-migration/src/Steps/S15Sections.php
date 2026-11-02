<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Menu\Models\Menu;
use Modules\Menu\Support\MenuEditor;
use Modules\Page\Models\Page;
use Platform\Scope\UserScopeFilter;

/**
 * S15X Article sections (DB-02 v0.2 §8.1, Data Migration Plan §5 S15): DIU's new site shows some articles together, a
 * "Browse by Section" list on the left — BSCIC, Safety and Security, Delegates (written in its code: DIUFronend
 * app/(public)/bscic, safetyandsecurity, delegates) and the `side_menu_categories` of project_diu (Medical, a department's
 * DLRC). Each section → a free menu of the pages' owner; its pages (S15A / S15D) get a sidebar layout with that menu (left
 * as DIU's BSCIC / Safety / Delegates, right as its Medical / DLRC), DIU's line under the title while a page has none, and
 * DIU's addresses (/bscic/{slug}, /medical/{slug}, /department/{dept}/dlrc/{slug} …) lead to them (301).
 * A menu is filled once and then left to Admin; a page whose layout or sidebar Admin changed is left alone.
 * After S15A and S15D.
 */
class S15Sections extends Step
{
    /** DIU's "Quick Links" under the BSCIC and Safety lists, as addresses of this site */
    public const QUICK_LINKS = [['Notice', '/notices'], ['Tuition Fees', '/tuition-fees'], ['Waiver Calculator', '/waiver-calculator'], ['Waiver & Scholarship', '/scholarships']];

    /** Sections written in DIU's code: name, its address prefix, quick links or not, [label, article slug] in DIU's order, line under the title */
    public const SECTIONS = [
        'bscic' => ['BSCIC Industrial Support Centre', '/bscic', true, [
            ['Introduction', 'bscic-diu'], ['Recent Events', 'recent-events'], ['Media Coverage', 'media-coverage'], ['Contact', 'contact-bscic'],
        ], 'Bridging Academia and Industry for Innovation and Excellence'],
        'safetyandsecurity' => ['Safety and Security', '/safetyandsecurity', true, [
            ['Introduction', 'safety-measures-of-daffodil-international-university'], ['Lost and Found', 'safetyandsecurity'],
            ['Vehicle Registration', 'vehicle-registration'], ['ID Pass', 'id-pass'], ['Service', 'ssm-service'], ['Contact', 'ssm-contact'],
        ], null],
        // DIU lists the year pages (newest first); all the legacy years are taken (2012 is missing on DIU's list)
        'delegates' => ['DIU Delegates', '/delegates', false, 'diu-delegates-', null],
    ];

    /** A `side_menus` link that names the wrong article: category slug → [legacy link → article slug] */
    public const LINK_FIXES = ['medical' => ['introduction' => 'diu-medical-center']];

    public static function key(): string
    {
        return 'S15X';
    }

    public static function title(): string
    {
        return 'Article sections with a sidebar list';
    }

    public static function requires(): array
    {
        return ['page', 'menu'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('articles')) {
            $this->report->review('No articles table in the legacy database: nothing to group.');

            return;
        }
        $articles = $this->legacy()->table('articles')->pluck('id', 'article_slug');
        $page = fn (string $slug): ?Page => isset($articles[$slug]) ? $this->ids->record('articles', $articles[$slug], Page::class) : null;

        foreach (self::SECTIONS as [$name, $prefix, $quick, $items, $excerpt]) {
            if (is_string($items)) {   // every article whose slug starts so, newest year first
                $items = $articles->keys()->filter(fn (string $s): bool => str_starts_with($s, $items))->sortDesc()
                    ->map(fn (string $s): array => [null, $s])->values()->all();
            }
            $this->section($name, $prefix, $quick, array_map(fn (array $i): array => [$i[0], $i[1], $page($i[1])], $items), excerpt: $excerpt);
        }

        $this->sideMenus($page);
    }

    /** `side_menu_categories` (Medical, DLRC of a department) and their `side_menus` */
    protected function sideMenus(\Closure $page): void
    {
        if (! Schema::connection('legacy')->hasTable('side_menu_categories')) {
            return;
        }
        $deptArticles = Schema::connection('legacy')->hasTable('department_articles') ? $this->legacy()->table('department_articles')->get(['id', 'article_slug', 'department_id']) : collect();

        foreach ($this->rows('side_menu_categories') as $category) {
            $items = [];
            foreach ($this->legacy()->table('side_menus')->where('side_menu_category_id', $category->id)->orderBy('serial')->orderBy('id')->get() as $row) {
                $link = trim((string) $row->link);   // DIU's address keeps the link as written
                $slug = self::LINK_FIXES[$category->slug][$link] ?? $link;
                if ($category->is_department) {
                    $article = $deptArticles->first(fn ($a): bool => $a->article_slug === $slug);
                    $items[] = [Normalizer::text($row->title), $link, $article ? $this->ids->record('department_articles', $article->id, Page::class) : null, $article?->department_id];
                } else {
                    $items[] = [Normalizer::text($row->title), $link, $page($slug)];
                }
            }

            $prefix = '/'.$category->slug;
            if ($category->is_department) {   // DIU: /department/{department}/dlrc/{slug}
                $department = $this->legacy()->table('departments')->find(collect($items)->pluck(3)->filter()->first());
                $prefix = $department ? '/department/'.$department->short_name.'/dlrc' : null;
            }
            $this->section(Normalizer::text($category->title) ?? $category->slug, $prefix, false, $items, (bool) $category->status, 'right-sidebar');
        }
    }

    /** @param list<array{0: ?string, 1: string, 2: ?Page}> $items label, legacy slug, the page */
    protected function section(string $name, ?string $prefix, bool $quick, array $items, bool $active = true, string $template = 'with-sidebar', ?string $excerpt = null): void
    {
        $found = array_values(array_filter($items, fn (array $i): bool => $i[2] !== null));
        foreach (array_filter($items, fn (array $i): bool => $i[2] === null) as $missing) {
            $this->report->review("{$name}: no page for \"{$missing[1]}\" — left out of the list.");
        }
        if ($found === []) {
            return;
        }

        $owner = $found[0][2]->owner_type.':'.$found[0][2]->owner_id;
        $menu = Menu::query()->withoutGlobalScope(UserScopeFilter::class)->ownedBy($owner)->whereNull('location')->where('name', $name)->first();
        $created = $menu === null;
        $menu ??= app(MenuEditor::class)->createFree($owner, $name);
        if ($created || ! $menu->items()->exists()) {   // once: then it is Admin's
            app(MenuEditor::class)->save($menu, array_map(fn (array $i): array => [
                'type' => 'record',
                'record_type' => $i[2]->getMorphClass(),
                'record_id' => $i[2]->getKey(),
                'title' => ['en' => $i[0] ?? (string) $i[2]->getTranslation('title', 'en')],
                'is_active' => $active,
            ], $found));
        }
        $this->report->saved('menu_menus', $created);

        foreach ($found as [, $slug, $page]) {
            // S15A's plain layout, or the one this step gave it: Admin's choice stays
            if (($page->template === 'default' && $page->sidebar_menu_id === null) || $page->sidebar_menu_id === $menu->getKey()) {
                $page->forceFill([
                    'template' => $template,
                    'sidebar_menu_id' => $menu->getKey(),
                    'quick_links' => $quick && blank($page->quick_links) ? array_map(fn (array $l): array => ['label' => ['en' => $l[0]], 'url' => $l[1]], self::QUICK_LINKS) : $page->quick_links,
                ]);
                if ($excerpt !== null && blank($page->getTranslation('excerpt', 'en', false))) {
                    $page->setTranslation('excerpt', 'en', $excerpt);
                }
                $page->save();
            }
            if ($prefix !== null) {
                $this->oldAddress($prefix.'/'.$slug, $page);
            }
        }
    }
}
