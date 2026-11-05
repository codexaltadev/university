<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Page\Models\Page;
use Modules\Seo\Support\Redirects;
use Platform\Registry\Registry;
use Platform\Relations\RelationEngine;

/**
 * S15A main-site articles (Data Migration Plan §5 S15, DB-02 §18): DIU's `articles` (International
 * Advisor, Visiting Professors, Credit Transfer, Rules and Regulation …) → general main-site Pages at
 * /{slug} (owner 2026-10-31: no "article" pages). The old address /article/{slug} leads to the page (301,
 * following the record), and so do the addresses DIU's new site gives some of them (ADDRESSES). The text
 * goes into one HTML block, its relative pictures and file links pointed at webbackend
 * (Normalizer::legacyHtml, as DIU's frontend does) until S03 takes them into the Media Library.
 * Run again: updates, no copies; the former "article" page is removed.
 */
class S15Articles extends Step
{
    /** The page all articles hung under before 2026-10-31 (/article) */
    public const OLD_PARENT_SLUG = 'article';

    /** An article whose slug DIU's new site uses for another page → its own address here */
    public const RENAMED = ['safetyandsecurity' => 'lost-and-found'];

    /**
     * An article DIU's new site shows inside a section, in its frame (DIUFronend admission/schedule): article => [the section's
     * page, the address under it] — a page under that page (/admission/schedule), with its sidebar, as the section's other pages
     */
    public const UNDER = ['admission-test-schedule' => ['/admission', 'schedule']];

    /** DIU's new site: address → the article it shows (DIUFronend app/(public)/…: international/[slug], delegates/[slug], medical/[slug] …) */
    public const ADDRESSES = [
        '/bscic' => 'bscic-diu',
        '/safetyandsecurity' => 'safety-measures-of-daffodil-international-university',
        '/delegates/delegates-abroad' => 'delegates-abroad',
        '/medical/diu-medical-center' => 'diu-medical-center',
        '/international/incoming-student-exchange' => 'incoming-student-exchange',
        '/international/international-alumni' => 'international-alumni',
        '/international/international-contact' => 'international-contact',
        '/international/international-event' => 'international-event',
        '/international/policy-for-int-student' => 'policy-for-int-student',
        '/visiting-professor' => 'international-adviser',
    ];

    /** The numbers on top of a key facts page, taken from its approved text (owner 2026-11-03): article => [[value, label]] */
    public const HIGHLIGHTS = [
        'at-a-glance' => [
            ['2002', 'Year of establishment'], ['30+ Acre', 'Land for Daffodil International University'], ['58', 'Total Approved Program'],
            ['620+', 'International collaborations'], ['51', 'No. of clubs and alliances'], ['9', 'DSC Hall'],
        ],
    ];

    /**
     * A key facts page in chapters (owner 2026-11-03, UI/UX §5.8 v0.6): article => chapter => its facts by label (as on
     * the old page, without the colon), each "label" or "label" => [display, featured]. A fact not named here goes to a
     * last chapter without a title — nothing of the approved text is left out.
     */
    public const CHAPTERS = [
        'at-a-glance' => [
            'University' => [
                'The official name of the University' => ['auto', true], 'Year of establishment', 'Campus buildings and locations',
                'Land for Daffodil International University', 'Branch Campus',
            ],
            'Academics' => [
                'Programs', 'Faculties' => ['cards', false], 'Departments', 'Institute', 'Admission session',
                'Scholarship and waiver status' => ['auto', true],
            ],
            'People' => ['Faculty members', 'Officials' => ['bars', false]],
            'Rankings' => ['Ranking / Award'],
            'Global' => ['International collaborations' => ['stat', true], 'International Affairs' => ['text', false], 'International Affiliation'],
            'Campus' => [
                'Lecture Theatre &Class rooms', 'Full equipped laboratories', 'Specialized Laboratory', 'Laptop Distributation',
                'Library' => ['stat', true], 'DSC Hall' => ['stat', false], 'Digital Amenities', 'Venue & Events', 'Canteens',
                'Central exam control section',
            ],
            'Student life & research' => [
                'No. of clubs and alliances' => ['auto', true], 'Scouting', 'Research centers', 'Events Calendar',
                'Career Development Center', 'Office of the Students\' Affairs', 'Entrepreneurship',
            ],
        ],
    ];

    public static function key(): string
    {
        return 'S15A';
    }

    public static function title(): string
    {
        return 'Main-site articles as general pages';
    }

    public static function requires(): array
    {
        return ['page'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('articles')) {
            $this->report->review('No articles table in the legacy database: nothing to migrate.');

            return;
        }
        $oldParent = $this->oldParent();
        $pages = [];

        foreach ($this->rows('articles') as $row) {
            $title = Normalizer::text($row->article_title);
            $slug = trim((string) $row->article_slug);
            if ($title === null || $slug === '') {
                $this->report->skipped('articles', 'no title or address');

                continue;
            }
            if (isset(S15DiuPages::PAGES[$slug])) {   // DIU's new site made it a page of its own (S15P)
                $this->report->skipped('articles', 'a page of DIU\'s new site (S15P)');
                $this->oldAddress('/article/'.$slug, '/'.$slug);

                continue;
            }

            // A module's own page has the address (/programs: the program list) → the article's title makes its address
            // ("Faculty and Programs" → /faculty-and-programs), "-page" after it when that is a module's too ("FAQ" → /faq-page)
            $address = self::RENAMED[$slug] ?? $slug;
            if (in_array($address, $this->modulePages(), true)) {
                $address = Str::slug($title);
                $address = in_array($address, $this->modulePages(), true) ? $address.'-page' : $address;
            }
            $section = isset(self::UNDER[$slug]) ? $this->sectionPage(self::UNDER[$slug][0]) : null;
            $address = $section ? self::UNDER[$slug][1] : $address;
            $pages[$slug] = $this->upsert('articles', $row, Page::class, [
                'owner_type' => RelationEngine::SITE_TYPE,
                'owner_id' => 0,
                'parent_id' => $section?->getKey(),
                'title' => Normalizer::en($title),
                'content' => ['en' => [$this->block(Normalizer::legacyHtml($row->article_content), $slug)]],
                'template' => $section ? 'with-sidebar' : 'default',
                'is_active' => (bool) $row->publication_status,
            ], after: function (Page $page, bool $created) use ($slug, $address, $section): void {
                $this->keepSlug($page, $address, $created);
                if ($address !== $slug) {
                    $page->slugs()->where('slug', $slug)->where('is_current', false)->delete();   // DIU's /{slug} is another page's (ADDRESSES, a module page)
                }
                if ($section) {   // its own address is no redirect any more (it was one to /{slug} before 2026-11-02); /{slug} leads here
                    $this->dropAddress(parse_url($page->url('en'), PHP_URL_PATH));
                    $this->oldAddress('/'.$slug, $page);
                }
                $this->oldAddress('/article/'.$slug, $page);
            }, existing: $this->same(Page::class, ['owner_type' => RelationEngine::SITE_TYPE, 'parent_id' => $oldParent?->getKey(), 'title->en' => $title]));
        }

        foreach (self::ADDRESSES as $path => $slug) {
            isset($pages[$slug]) ? $this->oldAddress($path, $pages[$slug]) : $this->report->review("DIU's {$path} shows the article \"{$slug}\", which is not in the legacy database.");
        }

        if ($oldParent !== null) {
            $oldParent->delete();
            $this->oldAddress('/'.self::OLD_PARENT_SLUG, '/');
        }

        $this->report->review('Article pictures and files still load from webbackend.daffodilvarsity.edu.bd: S03 brings them into the Media Library later.');
    }

    /**
     * The article's text as written: one two-column table (At a Glance) as the key facts block, its approved
     * text unchanged (UI/UX Part B §5.8), its numbers on top from HIGHLIGHTS; anything else as an HTML block
     * with the old Bootstrap markup.
     */
    protected function block(?string $html, string $slug): array
    {
        $facts = Normalizer::factsTable($html);
        if ($facts === null) {
            return ['type' => 'html', 'data' => ['html' => $html ?? '<p></p>']];
        }
        $facts['highlights'] = array_map(fn (array $h): array => ['value' => $h[0], 'label' => $h[1]], self::HIGHLIGHTS[$slug] ?? []);
        $facts['chapters'] = $this->chapters(array_map(fn (array $row): array => $row + ['display' => 'auto', 'featured' => false], $facts['rows']), self::CHAPTERS[$slug] ?? []);
        unset($facts['rows']);

        return ['type' => 'facts', 'data' => $facts];
    }

    /**
     * The facts in the chapters of CHAPTERS (their order there), the rest after them in a chapter without a title.
     *
     * @param  list<array{label: string, value: string, display: string, featured: bool}>  $rows
     * @return list<array{title: ?string, rows: list<array>}>
     */
    protected function chapters(array $rows, array $plan): array
    {
        $key = fn (string $label): string => Str::lower(preg_replace('/\s+/', ' ', rtrim(trim($label), " :\u{00A0}")));
        $byLabel = collect($rows)->keyBy(fn (array $row): string => $key($row['label']))->all();
        $chapters = [];

        foreach ($plan as $title => $facts) {
            $chapter = [];
            foreach ($facts as $label => $shape) {
                [$label, [$display, $featured]] = is_int($label) ? [$shape, ['auto', false]] : [$label, $shape];
                if ($row = $byLabel[$key($label)] ?? null) {
                    $chapter[] = ['display' => $display, 'featured' => $featured] + $row;
                    unset($byLabel[$key($label)]);
                }
            }
            if ($chapter !== []) {
                $chapters[] = ['title' => $title, 'rows' => $chapter];
            }
        }
        if ($byLabel !== []) {
            $chapters[] = ['title' => null, 'rows' => array_values($byLabel)];
        }

        return $chapters;
    }

    /** The main site's top page at that address (/admission), if there is one */
    protected function sectionPage(string $path): ?Page
    {
        return Page::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('owner_type', RelationEngine::SITE_TYPE)->whereNull('parent_id')
            ->get()->first(fn (Page $p): bool => $p->url('en') === $path);
    }

    /** A redirect away from an address a page now has */
    protected function dropAddress(?string $path): void
    {
        $class = 'Modules\\Seo\\Models\\Redirect';
        if (filled($path) && class_exists($class)) {
            $class::query()->where('source_hash', Redirects::hash(Redirects::normalize($path)))->delete();
        }
    }

    /** @return list<string> one-segment addresses of modules' own pages ("programs", "notices", "administration" …) */
    protected function modulePages(): array
    {
        return collect(app(Registry::class)->all('websitePage'))->map(fn (array $page): string => trim((string) $page['path'], '/'))
            ->filter(fn (string $path): bool => $path !== '' && ! str_contains($path, '/'))->values()->all();
    }

    /** The main-site page "article" the articles hung under before (/article), null when there is none */
    protected function oldParent(): ?Page
    {
        return Page::query()->withoutGlobalScopes()->ownedBy(RelationEngine::SITE_KEY)->whereNull('parent_id')->get()
            ->first(fn (Page $p): bool => $p->slug('en') === self::OLD_PARENT_SLUG);
    }
}
