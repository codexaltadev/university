<?php

namespace Legacy\Migration\Steps;

use Modules\Page\Models\Page;
use Modules\Settings\Groups\ContactSettings;
use Platform\Relations\RelationEngine;

/**
 * S15P Pages of DIU's new site that are written in its code, not kept in project_diu (DIUFronend app/(public)/students,
 * teachers, career, location): general main-site pages made of blocks — link cards for the Student Access Center and the
 * Teachers Directory, the Career page with the openings from skill.jobs (Jobs block), the Location block (Settings › Contact) — and DIU's campus in Settings › Contact while it has none.
 * A page is made once and then left to Admin; the old article "students" (S15A) becomes the Student Access Center.
 */
class S15DiuPages extends Step
{
    /** slug => title, line under it, blocks (DIU's sections in its order); a new page goes at the end (its place is its id map key) */
    public const PAGES = [
        'students' => [
            'title' => 'Student Access Center',
            'excerpt' => 'Select a faculty to explore its degree programs, admission requirements, and student-life highlights.',
            'blocks' => [
                ['links', 'Academic Essentials', 3, [
                    ['Student Portal', 'https://studentportal.diu.edu.bd/', 'credit-card', true],
                    ['Academic Calendar', '/office/registrar-office/academic-calendar', 'calendar'],
                    ['Notice Board', '/notices', 'bell'],
                ]],
                ['links', 'Learning Resources', 3, [
                    ['Blended Learning Center', 'https://elearn.daffodilvarsity.edu.bd/', 'monitor', true],
                    ['Library', 'https://library.daffodilvarsity.edu.bd/', 'book-open', true],
                    ['Credit Transfer Guidelines', '/credit-transfer', 'file-text'],
                ]],
                ['links', 'Career & Opportunities', 3, [
                    ['Internship Portal', 'https://internship.daffodilvarsity.edu.bd/?app=home', 'briefcase', true],
                    ['Scholarship', '/scholarships', 'graduation-cap'],
                ]],
                ['links', 'Support & Coordination', 3, [
                    ['Coordination Officer', '/coordination-officers', 'user'],
                    ['Support Contact', '/support-contacts', 'headphones'],
                ]],
            ],
        ],
        'teachers' => [
            'title' => 'Teachers Directory',
            'excerpt' => 'Access essential academic services, learning resources, career opportunities, and support',
            'blocks' => [
                ['links', null, 4, [
                    ['Deans & Heads', '/deans-heads', 'credit-card'],
                    ['Associate Deans & Heads', '/associate-deans-heads', 'calendar'],
                    ['Visiting Professors', '/visiting-professors', 'bell'],
                    ['Faculty Members', 'https://faculty.daffodilvarsity.edu.bd/', 'bell', true],
                ]],
            ],
        ],
        'location' => [
            'title' => 'Location',
            'excerpt' => 'Find us at our campus',
            'blocks' => [['location']],
        ],
        'career' => [
            'title' => 'Career at DIU',
            'excerpt' => 'Join our team and be part of an institution that values excellence, innovation, and integrity',
            'blocks' => [
                ['text', '<h2>Culture in DIU</h2><p>As an academic culture, Daffodil International University (DIU) strives for excellence and improvement through innovation in every aspect of the activity of teaching and research while maintaining integrity; a strong collaborative approach; open and effective communications and an inclusive culture based on dignity, courtesy, and respect which prioritizes the development of the student through education that engages and nurtures creativity.</p>'],
                ['text', '<h2>DIU Values</h2><p>Our values guide every action and decision at DIU, ensuring we uphold the highest standards in education and culture.</p>'],
                ['links', null, 3, [
                    ['Ethics', null, 'shield', false, 'We value ethics and are committed to being ethical in our practices and services. We promise not to compromise with ethical values.'],
                    ['Quality', null, 'award', false, 'Quality is measured by the success of the output - in our case, the success of our graduates.'],
                    ['Leadership', null, 'target', false, 'We believe in developing leadership qualities that set examples for others to follow.'],
                    ['Innovation', null, 'lightbulb', false, 'We empower individuals to think innovatively, remain curious, and value new and relevant ideas.'],
                    ['Diversity', null, 'heart', false, 'We value opinions and ideas of all groups irrespective of race, gender, nationality, age, religion, and position.'],
                    ['Ownership & Responsibility', null, 'trending-up', false, 'We take ownership and are responsible for the work we do. We do not blame others for our work.'],
                ], 'boxes'],
                ['jobs', 'Available Job Listings', 'https://studio.skill.jobs/api/company-specific-jobs/49fe-8742-5bebbc26b382/?company_slug=daffodil-international-university-jRuBTdMx&limit=1000'],
            ],
        ],
    ];

    /** DIU's campus as /location shows it */
    public const CAMPUS = [
        'name' => ['en' => 'Daffodil International University'],
        'address' => ['en' => "Daffodil Smart City (DSC),\nBirulia, Savar, Dhaka-1216."],
        'phones' => ['Admission Help Line: 09617901212', 'Telephone: +8809617901233', 'Fax: +8802224441835'],
        'emails' => ['admission@daffodilvarsity.edu.bd'],
        'map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3648.3585722788043!2d90.31758427589985!3d23.87690048390706!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b8ada2664e21%3A0x3c872fd17bc11ddb!2sDaffodil%20International%20University!5e0!3m2!1sen!2sbd!4v1761990285789!5m2!1sen!2sbd',
        'map_link' => 'https://maps.app.goo.gl/istRkPfCLxi9WqAn9',
    ];

    public static function key(): string
    {
        return 'S15P';
    }

    public static function title(): string
    {
        return "Pages of DIU's new site (Students, Teachers, Career, Location)";
    }

    public static function requires(): array
    {
        return ['page', 'settings'];
    }

    protected function handle(): void
    {
        if (blank(settings('contact.campuses'))) {
            settings()->saveFor(new ContactSettings, ['campuses' => [self::CAMPUS]]);
            $this->report->review('Settings › Contact: DIU\'s campus (Daffodil Smart City, phones, map) added — the Location page and the office Contact pages show it.');
        }

        foreach (array_keys(self::PAGES) as $i => $slug) {   // id map key: the page's place in PAGES, from 1
            $config = self::PAGES[$slug];
            $page = Page::query()->withoutGlobalScopes()->ownedBy(RelationEngine::SITE_KEY)->whereNull('parent_id')->get()
                ->first(fn (Page $p): bool => $p->slug('en') === $slug);
            if ($page !== null && $this->ids->find('diu_pages', $i + 1) === $page->getKey()) {
                $this->report->skipped('diu_pages', 'made before: Admin\'s now');

                continue;
            }

            $page ??= new Page;
            $created = ! $page->exists;
            $page->forceFill([
                'owner_type' => RelationEngine::SITE_TYPE, 'owner_id' => 0, 'parent_id' => null,
                'title' => ['en' => $config['title']],
                'excerpt' => ['en' => $config['excerpt']],
                'content' => ['en' => array_map(fn (array $b): array => $this->block($b), $config['blocks'])],
                'template' => 'default',
                'is_active' => true,
            ])->save();
            $this->keepSlug($page, $slug, $created);
            $this->ids->remember('diu_pages', $i + 1, $page);
            $this->report->saved('diu_pages', $created);
        }
    }

    /** @param array{0: string, 1?: ?string, 2?: int, 3?: list<array>} $block */
    protected function block(array $block): array
    {
        if ($block[0] === 'location') {
            return ['type' => 'location', 'data' => ['campus' => 0]];
        }
        if ($block[0] === 'text') {
            return ['type' => 'text', 'data' => ['body' => $block[1]]];
        }
        if ($block[0] === 'jobs') {
            return ['type' => 'jobs', 'data' => ['heading' => $block[1], 'source' => $block[2]]];
        }

        return ['type' => 'links', 'data' => [
            'heading' => $block[1],
            'columns' => $block[2],
            'style' => $block[4] ?? 'tiles',
            'items' => array_map(fn (array $i): array => ['label' => $i[0], 'url' => $i[1], 'icon' => $i[2], 'new_tab' => $i[3] ?? false, 'text' => $i[4] ?? null], $block[3]),
        ]];
    }
}
