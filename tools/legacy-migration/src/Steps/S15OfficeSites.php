<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Support\MenuEditor;
use Modules\Menu\Support\MenuTree;
use Modules\Office\Models\Office;
use Modules\Page\Models\Page;
use Platform\Assignments\Assignment;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\PersonView;

/**
 * S15 (office part, DB-02 §18, DB-04 §11): the Registrar's and the Proctor's own pages —
 * registrar_offices / proctor_offices → the office's pages, their categories → its menu, as on
 * DIU's /registrar-office and /proctor-office (FR-OFF-08: an office microsite, no tables of its own).
 *
 * The "Home" row is the office overview (only while the office has none). The rows of the main
 * category are the office's sidebar menu ("Browse by Section"; the header menu is left alone); every other category becomes a
 * page listing its rows — linked from the menu (Proctor: Complain Committee) or as a Quick Service
 * tile (Registrar: Employee Service, Alumni Service …). Rows with a link only stay links.
 * Old addresses /registrar-office[/{slug}], /proctor-office[/{slug}] lead here.
 */
class S15OfficeSites extends Step
{
    /**
     * legacy office => office slug, tables, the category of the side menu, where the other
     * categories go ("menu" or "quick_services") and DIU's old address.
     */
    public const OFFICES = [
        'registrar' => [
            'office' => 'registrar-office', 'table' => 'registrar_offices', 'categories' => 'registrar_office_categories',
            'category' => 'registrar_office_category_id', 'main' => 6, 'others' => 'quick_services', 'old' => '/registrar-office',
        ],
        'proctor' => [
            'office' => 'proctorial-office', 'table' => 'proctor_offices', 'categories' => 'proctor_office_categories',
            'category' => 'proctor_office_category_id', 'main' => 1, 'others' => 'menu', 'old' => '/proctor-office',
        ],
    ];

    /** Quick Service icon per legacy category (DIU registrar-office: IdCard, GraduationCap, User, Folder, Handshake, FileText) */
    public const ICONS = [
        'student-service' => 'graduation-cap', 'employee-service' => 'user', 'alumni-service' => 'graduation-cap',
        'file-processing' => 'folder', 'publications' => 'file-text', 'meeting' => 'handshake',
    ];

    /** DIU's proctor-office cards (hard-coded on the new site; kept as the office's highlights) */
    public const PROCTOR_HIGHLIGHTS = [
        ['icon' => 'shield', 'title' => 'Our Mission', 'text' => 'To maintain discipline, ensure student welfare, and create a harmonious campus environment through fair and transparent procedures.'],
        ['icon' => 'target', 'title' => 'Our Vision', 'text' => 'To foster a culture of respect, responsibility, and academic excellence while protecting the rights and dignity of all students.'],
        ['icon' => 'users', 'title' => 'Student Support', 'text' => 'We provide guidance, counseling, and support services to help students navigate academic and personal challenges.'],
        ['icon' => 'scale', 'title' => 'Fair Procedures', 'text' => 'All disciplinary actions follow transparent procedures ensuring due process and the right to be heard.'],
    ];

    /** The Academic Calendar page block (Calendar module), on the page of DIU's academic-calendar row */
    public const CALENDAR_BLOCK = 'Modules\\Calendar\\Blocks\\CalendarSessionsBlock';

    public static function key(): string
    {
        return 'S15O';
    }

    public static function title(): string
    {
        return 'Registrar and Proctor office pages and menus';
    }

    public static function requires(): array
    {
        return ['office', 'page', 'menu'];
    }

    protected function handle(): void
    {
        foreach (self::OFFICES as $key => $config) {
            if (! Schema::connection('legacy')->hasTable($config['table'])) {
                $this->report->review("No {$config['table']} in the legacy database: nothing to migrate.");

                continue;
            }

            $office = Office::findBySlug($config['office'], 'en')[0] ?? null;
            if ($office === null) {
                $this->report->review("Office \"{$config['office']}\" not found — run S10 first.");

                continue;
            }

            $this->office($key, $office, $config);
        }

        MenuTree::flush();
    }

    protected function office(string $key, Office $office, array $config): void
    {
        $rows = $this->rows($config['table'])->sortBy([fn ($a, $b) => (int) $a->serial <=> (int) $b->serial, fn ($a, $b) => $a->id <=> $b->id])->values();
        $categories = $this->rows($config['categories'])->keyBy('id');
        $owner = $office->getMorphClass().':'.$office->getKey();

        // Home = the overview; the office gets its own page (Proctorial Office had none)
        $home = $rows->first(fn ($row) => (int) $row->{$config['category']} === $config['main'] && strcasecmp(trim((string) $row->menu_title), 'Home') === 0);
        $updates = ['has_microsite' => true];
        if ($home && blank($office->getTranslation('overview', 'en', false)) && filled($html = Normalizer::legacyHtml($home->article_content))) {
            $updates['overview'] = ['en' => $html];
        }
        if ($key === 'proctor' && blank($office->highlights)) {
            $updates['highlights'] = collect(self::PROCTOR_HIGHLIGHTS)->map(fn (array $h): array => ['icon' => $h['icon'], 'title' => ['en' => $h['title']], 'text' => ['en' => $h['text']]])->all();
        }
        $office->forceFill($updates)->save();
        $this->oldAddress($config['old'], $office);

        // Every row with text is a page; a row with a link only stays a link
        $pages = [];
        foreach ($rows as $row) {
            if ($row === $home || ! $this->hasText($row)) {
                continue;
            }
            $pages[$row->id] = $this->page($config['table'], $row, $office);
            $this->oldAddress($config['old'].'/'.$row->article_slug, $pages[$row->id]);
        }

        // The office's side list ("Browse by Section"); its header menu stays as it is
        $editor = app(MenuEditor::class);
        $menu = $editor->own($owner, 'sidebar') ?? $editor->create($owner, 'sidebar');
        $tiles = [];

        foreach ($rows->groupBy(fn ($row) => (int) $row->{$config['category']}) as $categoryId => $group) {
            if ($categoryId === $config['main']) {
                foreach ($group as $row) {
                    if ($row !== $home) {
                        $this->menuItem($config['table'], $row, $menu->getKey(), $this->link($row, $pages, $office), (bool) $row->publication_status);
                    }
                }

                continue;
            }

            $category = $categories->get($categoryId);
            if ($category === null || $group->where('publication_status', 1)->isEmpty()) {
                continue;
            }

            $list = $this->categoryPage($config['categories'], $category, $office, $group, $pages);
            $this->oldAddress($config['old'].'/'.$category->slug, $list);

            if ($config['others'] === 'menu') {
                $item = (object) ['id' => 'category-'.$category->id, 'menu_title' => $category->title, 'serial' => 100 + (int) $category->id, 'target' => null, 'target_window' => null];
                $this->menuItem($config['categories'], $item, $menu->getKey(), ['type' => 'record', 'linkable_type' => $list->getMorphClass(), 'linkable_id' => $list->getKey(), 'url' => null], (bool) $category->publication_status, (string) $category->id);
                if ($category->slug === 'complain-committee') {
                    $this->oldAddress('/complaint-committee', $list);   // DIU's new site
                }
            } else {
                $tiles[] = ['icon' => self::ICONS[$category->slug] ?? 'link', 'label' => ['en' => Normalizer::text($category->title)], 'url' => parse_url($list->url('en'), PHP_URL_PATH) ?: $list->url('en')];
            }
        }

        if ($tiles !== [] && blank($office->quick_services)) {
            $office->forceFill(['quick_services' => $tiles])->save();
        }

        $this->report->review("{$office->name}: ".count($pages).' pages, '.count($tiles).' Quick Service tiles; overview from the "Home" row only while the office had none.');
    }

    protected function hasText(object $row): bool
    {
        return filled(trim(strip_tags((string) $row->article_content, '<img><iframe>')));
    }

    protected function page(string $table, object $row, Office $office, ?int $parentId = null): Page
    {
        $title = ($row->article_slug === 'contact' ? Normalizer::text($row->menu_title) : null)   // "Contact address Registrar office" → the menu's "Contact" (DIU)
            ?: Normalizer::text($row->article_title) ?: Normalizer::text($row->menu_title);
        $body = (string) Normalizer::legacyHtml($row->article_content);
        if (($link = $this->externalUrl($row)) && ! str_contains($body, $link)) {
            $body .= '<p><a href="'.e($link).'">'.e($link).'</a></p>';
        }
        if (filled($row->article_file ?? null) && ($file = Normalizer::fileUrl($row->article_file))) {
            $body .= '<p><a href="'.e($file).'">'.e(basename($file)).'</a></p>';
        }

        return $this->upsert($table, $row, Page::class, [
            'owner_type' => $office->getMorphClass(),
            'owner_id' => $office->getKey(),
            'parent_id' => $parentId,
            'title' => Normalizer::en($title),
            'content' => ['en' => $this->blocks($row, $office, $body)],
            'template' => 'default',
            'sort_order' => max(0, (int) $row->serial),
            'is_active' => (bool) $row->publication_status,
        ], after: fn (Page $p, bool $created) => $this->keepSlug($p, $row->article_slug, $created),
            existing: $this->same(Page::class, ['owner_type' => $office->getMorphClass(), 'owner_id' => $office->getKey(), 'title->en' => $title]));
    }

    /**
     * A row's page content: the old markup as written — except where the text is data the site
     * now keeps itself: the academic calendar ("Coming Soon" there), the Honour Board table (→ the
     * office's Head terms) and the contact text (→ the office's email, location, hours).
     *
     * @return list<array{type: string, data: array}>
     */
    protected function blocks(object $row, Office $office, string $body): array
    {
        $block = match (true) {
            $row->article_slug === 'academic-calendar' && class_exists(self::CALENDAR_BLOCK) => ['type' => 'calendar-sessions', 'data' => ['count' => 8]],
            $row->article_slug === 'honour-board' && $this->heads($office, $body) > 0 => ['type' => 'office-heads', 'data' => []],
            $row->article_slug === 'contact' && $this->contact($office, $body) => ['type' => 'office-contact', 'data' => []],
            default => ['type' => 'html', 'data' => ['html' => $body !== '' ? $body : '<p></p>']],
        };

        return [$block];
    }

    /**
     * The Honour Board table (Sl, Name, Designation, From, To) as the office's Head terms: each
     * person found among the office's people by name, else a manual person; a term already there
     * is kept. Returns how many rows were read.
     */
    protected function heads(Office $office, string $html): int
    {
        if (! str_contains($html, '<tr')) {
            return 0;
        }
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        $count = 0;

        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $cells = collect(iterator_to_array($tr->getElementsByTagName('td')))->map(fn (\DOMElement $td): string => Normalizer::text($td->textContent))->values();
            if ($cells->count() < 4 || ! ctype_digit($cells[0]) || ! ($from = $this->date($cells[3]))) {
                continue;   // the header row, or not a term
            }
            [$name, $label, $to] = [$cells[1], preg_replace('/\s*\(/', ' (', $cells[2]), $this->date($cells[4] ?? '')];
            $person = $this->person($office, $name, (string) Str::before($label, ' ('));
            $exists = Assignment::query()->where('assignable_type', $office->getMorphClass())->where('assignable_id', $office->getKey())
                ->where('person_type', $person->getMorphClass())->where('person_id', $person->getKey())->where('role_key', 'head')->whereDate('starts_on', $from)->exists();
            if (! $exists) {
                app(AssignmentEngine::class)->assign($person, $office, 'head', ['starts_on' => $from, 'ends_on' => $to, 'role_label' => ['en' => $label], 'sort_order' => (int) $cells[0]]);
            }
            $count++;
        }

        return $count;
    }

    protected function person(Office $office, string $name, string $designation): Model
    {
        $same = fn (string $a): bool => Str::lower(Str::squish($a)) === Str::lower(Str::squish($name));
        $known = $office->people(['head', 'team'])->concat($office->people('head', former: true))->first(fn (PersonView $p): bool => $same($p->name()));

        return $known?->assignment->person
            ?? ManualPerson::query()->get()->first(fn (ManualPerson $p): bool => $same((string) $p->getTranslation('name', 'en', false)))
            ?? ManualPerson::create(['name' => ['en' => $name], 'designation' => ['en' => $designation], 'is_active' => true]);
    }

    protected function date(string $text): ?string
    {
        try {
            return filled($text = trim($text)) ? Carbon::parse($text)->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The contact text (… Ground Floor, Knowledge Tower / Email: … / Hours: … / Closed on …) into the
     * office's location, email and hours — only those still blank. True when an email was found.
     */
    protected function contact(Office $office, string $html): bool
    {
        $lines = collect(preg_split('/\R/', html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>|</h\d>#i', "\n", $html)))))
            ->map(fn (string $l): string => Str::squish($l))->filter()->values();
        $at = $lines->search(fn (string $l): bool => Str::startsWith(Str::lower($l), 'email:'));
        if ($at === false || ! preg_match('/[\w.+-]+@[\w-]+(\.[\w-]+)+/', $lines[$at], $email)) {
            return false;
        }
        $hours = $lines->search(fn (string $l): bool => Str::startsWith(Str::lower($l), 'hours:'));
        $facts = [
            'email' => $email[0],
            'location' => $at > 0 && ! str_contains($lines[$at - 1], ':') ? ['en' => $lines[$at - 1]] : null,
            'office_hours' => $hours !== false ? ['en' => trim(Str::after($lines[$hours], ':')).(Str::startsWith($lines[$hours + 1] ?? '', 'Closed') ? "\n".$lines[$hours + 1] : '')] : null,
        ];
        $office->forceFill(collect($facts)->filter()->filter(fn ($v, string $k): bool => blank($office->getAttribute($k)))->all())->save();

        return true;
    }

    /**
     * A category's page: its rows' pages under it (the theme lists them); the rows that are links
     * only, a list of links.
     *
     * @param  Collection<int, object>  $rows
     * @param  array<int, Page>  $pages
     */
    protected function categoryPage(string $table, object $category, Office $office, Collection $rows, array $pages): Page
    {
        $title = Normalizer::text($category->title);
        $page = $this->upsert($table, $category, Page::class, [
            'owner_type' => $office->getMorphClass(),
            'owner_id' => $office->getKey(),
            'parent_id' => null,
            'title' => Normalizer::en($title),
            'template' => 'default',
            'sort_order' => 100 + (int) $category->id,
            'is_active' => (bool) $category->publication_status,
        ], after: fn (Page $p, bool $created) => $this->keepSlug($p, $category->slug, $created),
            existing: $this->same(Page::class, ['owner_type' => $office->getMorphClass(), 'owner_id' => $office->getKey(), 'title->en' => $title]));

        $items = [];
        foreach ($rows->where('publication_status', 1) as $row) {
            if (isset($pages[$row->id])) {
                $pages[$row->id]->forceFill(['parent_id' => $page->getKey()])->save();
            } elseif ($url = $this->link($row, $pages, $office)['url'] ?? null) {
                $items[] = '<li><a href="'.e($url).'">'.e(Normalizer::text($row->menu_title)).'</a></li>';
            }
        }

        $page->forceFill(['content' => ['en' => $items !== [] ? [['type' => 'html', 'data' => ['html' => '<ul>'.implode('', $items).'</ul>']]] : []]])->save();

        return $page;
    }

    protected function menuItem(string $table, object $row, int $menuId, array $link, bool $active, ?string $legacyId = null): void
    {
        $target = $row->target ?? $row->target_window ?? null;
        $source = $legacyId !== null ? (object) ['id' => $legacyId] : $row;

        $this->upsert($table.'_menu', $source, MenuItem::class, [   // the page of the same row is mapped under $table
            'menu_id' => $menuId,
            'parent_id' => null,
            'title' => Normalizer::en(Normalizer::text($row->menu_title)),
            'sort_order' => max(0, (int) $row->serial),
            'open_in_new_tab' => $target === '_blank',
            'linkable_type' => null, 'linkable_id' => null, 'route_key' => null,
            'type' => 'url',
            ...$link,
            'is_active' => $active && ($link['type'] === 'record' || filled($link['url'] ?? null)),
        ]);
    }

    /**
     * Where a menu row leads: its page; else a module of this site (notices, team, calendar,
     * committees, news) or its own link.
     *
     * @param  array<int, Page>  $pages
     * @return array{type: string, linkable_type: ?string, linkable_id: ?int, url: ?string}
     */
    protected function link(object $row, array $pages, Office $office): array
    {
        if (isset($pages[$row->id]) && ($row->article_slug !== 'academic-calendar' || class_exists(self::CALENDAR_BLOCK))) {
            return ['type' => 'record', 'linkable_type' => $pages[$row->id]->getMorphClass(), 'linkable_id' => $pages[$row->id]->getKey(), 'url' => null];
        }

        $site = parse_url($office->url('en'), PHP_URL_PATH) ?: (string) $office->url('en');
        $url = match (true) {
            $row->article_slug === 'notice' => $site.'/notices',
            $row->article_slug === 'academic-calendar' => '/academic-calendar',
            $row->article_slug === 'staff-directory' => $site.'/team',
            $row->article_slug === 'news-events' => $site.'/news',
            filled($row->module_url ?? null) => $row->module_url === 'committees' ? '/committees' : '/committees/'.$row->module_url,
            default => $this->externalUrl($row),
        };

        return ['type' => 'url', 'linkable_type' => null, 'linkable_id' => null, 'url' => $url];
    }

    /** The row's own link: DIU's pages (committee/…) become ours, the rest stays as it is */
    protected function externalUrl(object $row): ?string
    {
        $url = Normalizer::url($row->article_url);
        if ($url === null) {
            return null;
        }
        if (preg_match('#^https?://(www\.)?daffodilvarsity\.edu\.bd/committee/([^/?\#]+)#i', $url, $m)) {
            return '/committees/'.$m[2];
        }

        return preg_match('#^https?://(www\.)?daffodilvarsity\.edu\.bd/(registrar-office|proctor-office)/?$#i', $url) ? null : $url;
    }
}
