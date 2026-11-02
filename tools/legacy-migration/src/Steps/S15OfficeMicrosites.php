<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Faq\Models\Faq;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Support\MenuEditor;
use Modules\Menu\Support\MenuTree;
use Modules\Office\Models\Office;
use Modules\Page\Models\Page;
use Platform\Assignments\Assignment;
use Platform\Relations\RelationEngine;

/**
 * S15T Office sites (Data Migration Plan S15): DIU's new `office_sites` (Exam, Technology Transfer
 * Office) — what its /office/{slug} shows: the banner title and text (→ the office's overview),
 * location, phone, email, extension; `office_site_articles` → the office's pages,
 * `office_site_menus` → its side menu ("Browse by Section"), `office_site_faqs` → FAQs shown in
 * the office, `office_site_teams` → its team (manual persons). An office already there (S10:
 * exam → exam-office) takes the data; a new one (tto) is made. DIU's /office/{slug}/{article}
 * leads to the page. The banner comes with S03, the facilities of an office site with S18F.
 */
class S15OfficeMicrosites extends Step
{
    public static function key(): string
    {
        return 'S15T';
    }

    public static function title(): string
    {
        return 'Office sites (Exam, TTO): overview, pages, menu, FAQs, team';
    }

    public static function requires(): array
    {
        return ['office', 'page', 'menu'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('office_sites')) {
            $this->report->review('No office_sites table in the legacy database: nothing to migrate.');

            return;
        }

        $legacySlugs = array_flip(S10Offices::DIU_PAGES);   // exam => exam-office
        foreach ($this->rows('office_sites') as $row) {
            $existing = Office::findBySlug($legacySlugs[$row->slug] ?? $row->slug, 'en')[0] ?? null;
            $office = $this->upsert('office_sites', $row, Office::class, [
                ...($existing ? [] : ['name' => Normalizer::en(Normalizer::text($row->title)), 'sort_order' => 100 + (int) $row->sort_order]),
                'overview' => Normalizer::en(Normalizer::legacyHtml($row->description)),
                'location' => Normalizer::en(Normalizer::text($row->location)),
                'phone' => mb_substr((string) Normalizer::text($row->phone), 0, 30) ?: null,
                'email' => Normalizer::text($row->email),
                'extension' => Normalizer::text($row->extension),
                'has_microsite' => true,
                'is_active' => (bool) $row->is_active,
            ], after: fn (Office $o, bool $created) => $created ? $this->keepSlug($o, $row->slug, true) : null,
                existing: fn () => $existing);

            $diu = '/office/'.$row->slug;
            $base = parse_url($office->url('en'), PHP_URL_PATH);
            $this->oldAddress($diu.'/faqs', $base.'/faq');
            if ($diu !== $base) {   // the same address (tto) needs none
                $this->oldAddress($diu, $office);
                $this->oldAddress($diu.'/team', $base.'/team');
            }

            $pages = $this->pages($row, $office, $diu);
            $this->menu($row, $office, $pages);
            $faqs = $this->faqs($row, $office);
            $team = $this->team($row, $office);
            $this->report->review("{$office->name}: ".count($pages)." pages, {$faqs} FAQs, {$team} team members (their photos wait for manual-person photos).");
        }

        MenuTree::flush();
    }

    /** @return array<string, Page> by DIU's article slug */
    protected function pages(object $site, Office $office, string $diu): array
    {
        $pages = [];
        foreach ($this->rows('office_site_articles', fn ($q) => $q->where('office_site_id', $site->id)) as $row) {
            $title = Normalizer::text($row->title);
            $pages[$row->slug] = $this->upsert('office_site_articles', $row, Page::class, [
                'owner_type' => $office->getMorphClass(),
                'owner_id' => $office->getKey(),
                'parent_id' => null,
                'title' => Normalizer::en($title),
                'content' => ['en' => [['type' => 'html', 'data' => ['html' => Normalizer::legacyHtml($row->content) ?? '<p></p>']]]],
                'template' => 'default',
                'sort_order' => (int) $row->id,
                'is_active' => (bool) $row->is_active,
            ], after: fn (Page $p, bool $created) => $this->keepSlug($p, Str::slug($title), $created),   // DIU's "service-standards-9hsg" without its random end
                existing: $this->same(Page::class, ['owner_type' => $office->getMorphClass(), 'owner_id' => $office->getKey(), 'title->en' => $title]));
            $this->oldAddress($diu.'/'.$row->slug, $pages[$row->slug]);
        }

        return $pages;
    }

    /** @param array<string, Page> $pages */
    protected function menu(object $site, Office $office, array $pages): void
    {
        $owner = $office->getMorphClass().':'.$office->getKey();
        $editor = app(MenuEditor::class);
        $menu = $editor->own($owner, 'sidebar') ?? $editor->create($owner, 'sidebar');

        foreach ($this->rows('office_site_menus', fn ($q) => $q->where('office_site_id', $site->id)) as $row) {
            $page = $pages[$row->article_link] ?? null;
            $link = match (true) {
                $row->link_type === 'article' && $page !== null => ['type' => 'record', 'linkable_type' => $page->getMorphClass(), 'linkable_id' => $page->getKey(), 'url' => null],
                $row->link_type === 'file' => ['type' => 'url', 'url' => Normalizer::fileUrl($row->file)],
                default => ['type' => 'url', 'url' => Normalizer::url($row->external_link ?: $row->module_link)],
            };
            $this->upsert('office_site_menus', $row, MenuItem::class, [
                'menu_id' => $menu->getKey(),
                'parent_id' => null,
                'title' => Normalizer::en(Normalizer::text($row->title)),
                'sort_order' => max(0, (int) $row->sort_order),
                'open_in_new_tab' => $row->target === '_blank',
                'linkable_type' => null, 'linkable_id' => null, 'route_key' => null,
                ...$link,
                'is_active' => (bool) $row->is_active && ($link['type'] === 'record' || filled($link['url'])),
            ]);
        }
    }

    protected function faqs(object $site, Office $office): int
    {
        if (! class_exists(Faq::class)) {
            return 0;
        }
        $engine = app(RelationEngine::class);
        $rows = $this->rows('office_site_faqs', fn ($q) => $q->where('office_site_id', $site->id));
        foreach ($rows as $row) {
            $question = Normalizer::text(strip_tags((string) $row->question));
            $this->upsert('office_site_faqs', $row, Faq::class, [
                'question' => Normalizer::en($question),
                'answer' => Normalizer::en(Normalizer::html($row->answer) ?? ''),
                'sort_order' => max(0, (int) $row->sort_order),
                'is_active' => (bool) $row->is_active,
            ], after: fn (Faq $faq) => $engine->sync($faq, [$office->getMorphClass().':'.$office->getKey()], mainSite: false, user: null),
                existing: $this->same(Faq::class, ['question->en' => $question]));
        }

        return $rows->count();
    }

    protected function team(object $site, Office $office): int
    {
        $rows = $this->rows('office_site_teams', fn ($q) => $q->where('office_site_id', $site->id));
        foreach ($rows as $row) {
            if (! ($person = $this->rowPerson('office_site_teams', $row))) {
                $this->report->skipped('office_site_teams', 'no name');

                continue;
            }
            $this->upsert('office_site_teams', $row, Assignment::class, [
                'person_type' => $person->getMorphClass(),
                'person_id' => $person->getKey(),
                'assignable_type' => $office->getMorphClass(),
                'assignable_id' => $office->getKey(),
                'role_key' => 'team',
                'sort_order' => max(0, (int) $row->serial),
                'is_public' => (bool) $row->is_active,
                'overrides' => array_filter(['phone' => Normalizer::text($row->phone), 'extension' => Normalizer::text($row->extension)]) ?: null,
                'is_active' => true,
            ]);
        }

        return $rows->count();
    }
}
