<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Support\MenuEditor;
use Modules\Menu\Support\MenuTree;
use Modules\Page\Models\Page;
use Modules\Program\Models\Program;

/**
 * S15 (department part, DB-02 §18): department_articles → the department's pages,
 * department_menus → the department's own primary menu (order and dropdowns kept), and every
 * program's menu set to "inherit" — a program shows its department's menu (BRL-MENU-01).
 *
 * Menu links: article → the page; external / file → a link (files from webbackend until S03);
 * no link → a heading; module → a link to where that module's page will be. A module that is
 * not built or not active yet leaves its item inactive (hidden on the website); run the step
 * again once it is there.
 */
class S15DepartmentSites extends Step
{
    /** legacy module_link => [module slug, path; {ms} = the department's microsite, {dept} = its short name] */
    public const MODULE_LINKS = [
        'notice' => ['notice', '{ms}/notices'],
        'events' => ['event', '{ms}/events'],
        'faq' => ['faq', '{ms}/faq'],
        'contact-us' => ['contact', '{ms}/contact'],
        'alumni' => ['alumni', '{ms}/alumni'],
        'visitors' => ['visitor', '{ms}/visitors'],
        'lab-facility' => ['lab', '{ms}/labs'],
        'admission' => ['admission', '/admission'],
        'admission-eligibility' => ['admission', '/admission/eligibility'],
        'admission-test' => ['admission', '/admission/test'],
        'local-tuition' => ['tuition', '/tuition-fees?category=local&department={dept}'],
        'international-tuition' => ['tuition', '/tuition-fees?category=international&department={dept}'],
        'prospectus' => ['download', '{ms}/downloads/prospectus'],
        'handbook' => ['download', '{ms}/downloads/handbook'],
        'section-guidebook' => ['download', '{ms}/downloads/guidebook'],
        'layout-plan' => ['download', '{ms}/downloads/layout-plan'],
        'service-charter' => ['download', '{ms}/downloads/service-charter'],
    ];

    public static function key(): string
    {
        return 'S15D';
    }

    public static function title(): string
    {
        return 'Department pages and menus; programs inherit';
    }

    public static function requires(): array
    {
        return ['department', 'page', 'menu'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('department_menus')) {
            $this->report->review('No department menus in the legacy database: nothing to migrate.');

            return;
        }

        $pages = $this->pages();
        $this->menus($pages);
        $this->programsInherit();
        MenuTree::flush();
    }

    /** @return array<string, int> legacy article slug => page id */
    protected function pages(): array
    {
        $bySlug = [];

        foreach ($this->rows('department_articles', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $department = $this->department($row->department_id);
            if ($department === null) {
                $this->report->skipped('department_articles', 'department not migrated');

                continue;
            }

            $title = Normalizer::text($row->article_title) ?: Normalizer::text($row->menu_title);
            $body = (string) Normalizer::legacyHtml($row->article_content);
            if ($link = Normalizer::url($row->article_link)) {
                $body .= '<p><a href="'.e($link).'">'.e($link).'</a></p>';
            }
            if ($file = Normalizer::fileUrl($row->article_file)) {
                $body .= '<p><a href="'.e($file).'">'.e(basename($file)).'</a></p>';
            }

            $page = $this->upsert('department_articles', $row, Page::class, [
                'owner_type' => $department->getMorphClass(),
                'owner_id' => $department->getKey(),
                'parent_id' => null,
                'title' => Normalizer::en($title),
                'content' => ['en' => [['type' => 'html', 'data' => ['html' => $body !== '' ? $body : '<p></p>']]]],   // as written: the old Bootstrap markup
                'template' => 'default',
                'sort_order' => max(0, (int) $row->serial),   // some legacy serials are negative
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Page $p, bool $created) => $this->keepSlug($p, $row->article_slug, $created),
                existing: $this->same(Page::class, ['owner_type' => $department->getMorphClass(), 'owner_id' => $department->getKey(), 'title->en' => $title]));

            $bySlug[(string) $row->article_slug] = $page->getKey();
        }

        return $bySlug;
    }

    /** @param array<string, int> $pages */
    protected function menus(array $pages): void
    {
        $rows = $this->rows('department_menus');
        $editor = app(MenuEditor::class);
        $menus = [];

        foreach ($rows as $row) {
            $department = $this->department($row->department_id);
            if ($department === null) {
                $this->report->skipped('department_menus', 'department not migrated');

                continue;
            }

            $owner = $department->getMorphClass().':'.$department->getKey();
            $menu = $menus[$owner] ??= ($editor->own($owner, 'primary') ?? $editor->create($owner, 'primary'));
            $editor->setMode($owner, 'own');

            $this->upsert('department_menus', $row, MenuItem::class, [
                'menu_id' => $menu->getKey(),
                'parent_id' => null,   // set below, once every item exists
                'title' => Normalizer::en(Normalizer::text($row->title)),
                'sort_order' => max(0, (int) $row->serial),
                'open_in_new_tab' => $row->target === '_blank',
                ...$this->link($row, $department, $pages),
            ]);
        }

        foreach ($rows as $row) {
            if ((int) $row->parent_id === 0 || ($id = $this->ids->find('department_menus', $row->id)) === null) {
                continue;
            }
            MenuItem::query()->whereKey($id)->update(['parent_id' => $this->ids->find('department_menus', $row->parent_id)]);
        }

        $this->report->review('Module links point to where those modules\' pages will be; the ones of modules not built yet are inactive until a later run.');
    }

    /** @param array<string, int> $pages */
    protected function link(object $row, Department $department, array $pages): array
    {
        $active = (int) $row->status === 1;
        $plain = ['type' => 'url', 'linkable_type' => null, 'linkable_id' => null, 'route_key' => null, 'url' => null];

        return match ($row->link_type) {
            'article' => isset($pages[(string) $row->article_link])
                ? [...$plain, 'type' => 'record', 'linkable_type' => (new Page)->getMorphClass(), 'linkable_id' => $pages[(string) $row->article_link], 'is_active' => $active]
                : [...$plain, 'type' => 'heading', 'is_active' => false],
            'external' => [...$plain, 'url' => Normalizer::url($row->external_link), 'is_active' => $active && filled($row->external_link)],
            'file' => [...$plain, 'url' => Normalizer::fileUrl($row->file), 'is_active' => $active && filled($row->file)],
            'module' => $this->moduleLink((string) $row->module_link, $department, $active),
            default => [...$plain, 'type' => 'heading', 'is_active' => $active],   // nolink: a dropdown heading; sidemenu
        };
    }

    protected function moduleLink(string $key, Department $department, bool $active): array
    {
        [$module, $path] = self::MODULE_LINKS[$key] ?? [null, '{ms}/'.$key];
        $url = strtr($path, ['{ms}' => (string) $department->url(), '{dept}' => (string) $department->short_name]);
        $ready = $module !== null && DB::table('core_modules')->where('slug', $module)->where('status', 'active')->exists();

        if (! $ready) {
            $this->report->review("Menu link \"{$key}\" of {$department->short_name}: module \"".($module ?? '?').'" not built yet — item inactive.');
        }

        return ['type' => 'url', 'linkable_type' => null, 'linkable_id' => null, 'route_key' => null, 'url' => $url, 'is_active' => $active && $ready];
    }

    protected function programsInherit(): void
    {
        $editor = app(MenuEditor::class);
        $count = 0;

        foreach (Program::query()->withoutGlobalScopes()->whereNull('deleted_at')->get() as $program) {
            $editor->setMode($program->getMorphClass().':'.$program->getKey(), 'inherit');
            $count++;
        }

        $this->report->review("{$count} programs use their department's menu (inherit).");
    }

    protected function department(int|string $legacyId): ?Department
    {
        $id = $this->ids->find('departments', $legacyId);

        return $id ? Department::query()->withoutGlobalScopes()->find($id) : null;
    }
}
