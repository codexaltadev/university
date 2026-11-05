<?php

/*
| Phase 2.9 — Pages on the website and their SEO (C-11, C-12): addresses down the
| parents, microsite pages, visibility, old address → 301, blocks, SEO panel and
| automatic tags, JSON-LD, sitemap. FR-PAGE-03/04/05, FR-GEN-32/70/71, FR-SEO-01/02/07/08, AC-SEO-01/02.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Office\Models\Office;
use Modules\Page\Blocks\FactsBlock;
use Modules\Page\Filament\Resources\Pages\Pages\EditPage;
use Modules\Page\Models\Page;
use Platform\Media\MediaLibrary;
use Platform\Seo\Seo;

beforeEach(function () {
    Storage::fake('public');
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->about = Page::factory()->create([
        'title' => ['en' => 'About Us'],
        'excerpt' => ['en' => 'Daffodil International University was founded in 2002 in Dhaka.'],
        'content' => ['en' => [
            ['type' => 'text', 'data' => ['body' => '<p>Founded in <strong>2002</strong>.</p><script>alert(1)</script>']],
            ['type' => 'accordion', 'data' => ['items' => [['title' => 'Mission', 'body' => '<p>Quality education.</p>']]]],
            ['type' => 'embed', 'data' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']],
        ]],
    ]);
    $this->history = Page::factory()->create(['title' => ['en' => 'History'], 'parent_id' => $this->about->id, 'template' => 'with-sidebar']);
});

it('shows a page and its child at their addresses through the theme (FR-PAGE-03/04)', function () {
    $this->get('/about-us')->assertOk()
        ->assertSee('page-hero', false)->assertSeeInOrder(['Home', 'About Us', '</h1>'], false)   // DIU's hero with the title
        ->assertSee('<diu-rich-html', false)
        ->assertSee('<strong>2002</strong>', false)
        ->assertDontSee('<script>alert(1)</script>', false)                    // rich text is sanitised
        ->assertSee('<summary class="accordion__title">Mission</summary>', false)
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

    $this->get('/about-us/history')->assertOk()->assertSeeInOrder(['Home', 'About Us', 'History'])->assertSee('page--with-sidebar', false);   // breadcrumb down the parents; with-sidebar template
    $this->get('/history')->assertNotFound();                                   // only under its parent
});

it('puts the sidebar on the left or the right as the page\'s layout says (FR-PAGE-04 v0.3)', function () {
    $links = [['label' => ['en' => 'Apply Online'], 'url' => 'https://admission.example.edu']];
    $this->history->update(['content' => ['en' => [['type' => 'text', 'data' => ['body' => '<p>Our story.</p>']]]], 'quick_links' => $links]);

    $this->get('/about-us/history')->assertOk()->assertSee('page--with-sidebar', false)->assertSeeInOrder(['Apply Online', 'Our story.']);

    $this->history->update(['template' => 'right-sidebar']);
    $this->get('/about-us/history')->assertOk()->assertSee('page--right-sidebar', false)->assertSeeInOrder(['Our story.', 'Apply Online']);
});

it('lists the openings from skill.jobs in the Jobs block, open ones first visible, and says so when it cannot reach them', function () {
    $source = 'https://studio.skill.jobs/api/company-specific-jobs/x/?company_slug=diu&limit=1000';
    Http::fake(['studio.skill.jobs/*' => Http::sequence()->push(['results' => [
        ['id' => 1, 'slug' => 'assistant-officer-AB', 'title' => 'Assistant Officer', 'type' => 'Full Time', 'level' => 'Beginner', 'location' => 'Savar', 'workplace' => 'Work From Office',
            'no_of_vacancy' => 2, 'end_date' => 'Nov 01, 2026', 'isExpired' => false, 'company_info' => ['name' => 'DIU', 'logo' => '/media/logo/diu.png']],
        ['id' => 2, 'slug' => 'old-post-CD', 'title' => 'Old Post', 'type' => 'Internship', 'level' => 'Mid Level', 'isExpired' => true, 'company_info' => ['name' => 'DIU']],
    ]])->push('down', 503)]);
    $this->about->update(['content' => ['en' => [['type' => 'jobs', 'data' => ['heading' => 'Available Job Listings', 'source' => $source]]]]]);

    $this->get('/about-us')->assertOk()
        ->assertSeeInOrder(['Available Job Listings', 'Full Time', 'Internship', 'Beginner', 'Mid Level', 'Show expired jobs', 'Assistant Officer', '2 vacancies', 'Deadline: Nov 01, 2026', 'Old Post', 'Expired'])
        ->assertSee('https://skill.jobs/jobs/assistant-officer-AB', false)->assertSee('https://studio.skill.jobs/media/logo/diu.png', false);
    $this->get('/about-us')->assertOk();
    Http::assertSentCount(1);   // remembered, not asked on every visit

    Cache::flush();   // the next visit asks again: SkillJobs is down
    $this->get('/about-us')->assertOk()->assertSee('The job listings could not be loaded just now.');
});

it('keeps the classes and collapse attributes of an HTML block, without scripts or handlers', function () {
    $this->about->update(['content' => ['en' => [['type' => 'html', 'data' => ['html' => '<table class="table table-bordered" border="1"><tr><td>Name</td></tr></table>'
        .'<div class="accordion" id="a1"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#c1">Spring</button>'
        .'<div id="c1" class="accordion-collapse collapse" data-bs-parent="#a1"><img src="https://webbackend.example.edu/x.jpg" onerror="alert(1)"></div></div>'
        .'<script>alert(2)</script><a href="javascript:alert(3)">x</a><iframe src="https://evil.example.com"></iframe>']]]]]);

    $this->get('/about-us')->assertOk()
        ->assertSee('class="table table-bordered" border="1"', false)
        ->assertSee('data-bs-toggle="collapse" data-bs-target="#c1"', false)
        ->assertSee('data-bs-parent="#a1"', false)
        ->assertSee('src="https://webbackend.example.edu/x.jpg"', false)
        ->assertDontSee('onerror', false)->assertDontSee('alert(2)', false)->assertDontSee('javascript:', false)->assertDontSee('evil.example.com', false);
});

it('shows the key facts block in chapters, each fact in the shape its text asks for, the text as written (UI/UX Part B §5.8 v0.6)', function () {
    $chapters = [
        ['title' => 'University', 'rows' => [
            ['label' => 'Year of establishment :', 'value' => '2002 With the approval of UGC', 'display' => 'auto', 'featured' => true],
            ['label' => 'Canteens :', 'value' => '<ol><li>Green Gardeen 1</li></ol><script>alert(1)</script>', 'display' => 'auto'],
        ]],
        ['title' => 'Academics', 'rows' => [
            ['label' => 'Programs :', 'value' => '<table><tr><td>Program approved by</td><td>UGC</td></tr><tr><td>Bachelor Program</td><td>: 31</td></tr><tr><td>Master\'s Program</td><td>: 18</td></tr><tr><td>Total Approved Program:</td><td>: 58</td></tr></table>', 'display' => 'auto'],
            ['label' => 'Departments :', 'value' => '<ol>'.collect(range(1, 14))->map(fn ($i) => "<li>Department {$i}</li>")->implode('').'</ol>', 'display' => 'auto'],
            ['label' => 'Faculties :', 'value' => '<ol><li>Faculty of Engineering</li><li>Faculty of Agriculture Sciences</li></ol>', 'display' => 'cards'],
            ['label' => 'Library :', 'value' => '<ul><li>Approximately one million (1,140,500) items</li></ul>', 'display' => 'stat'],
            ['label' => 'Venue & Events:', 'value' => '<ul><li>Hall, Seat Capacity : 500</li><li>Bonomaya 01</li></ul>', 'display' => 'auto'],
        ]],
        ['title' => 'Rankings', 'rows' => [
            ['label' => 'Ranking / Award :', 'value' => '<p><a class="btn btn-primary" href="/rankings">View Details</a></p><ul><li>THE Impact Rankings 2024</li><li>QS World 2025</li><li>SCIMAGO Q1</li><li>THE World 2025</li></ul>', 'display' => 'auto'],
        ]],
    ];
    $this->about->update(['content' => ['en' => [['type' => 'facts', 'data' => [
        'highlights' => [['value' => '2002', 'label' => 'Year of establishment'], ['value' => '30+ Acre', 'label' => 'Land']],
        'chapters' => $chapters, 'show_nav' => true, 'note' => '<p>Note: mail us</p>',
    ]]]]]);

    $this->get('/about-us')->assertOk()
        ->assertSee('page-block--facts', false)->assertDontSee('<diu-rich-html', false)                       // the theme's own look, not page text
        ->assertSee('data-count="2002" data-from="1990">2002</span>', false)                                 // a year counts up from a little before
        ->assertSee('data-count="30" data-from="0">30</span><span class="facts__highlight-rest">+ Acre</span>', false)
        ->assertSee('<a href="#academics" class="facts__chapter-link"', false)->assertSee('<span class="facts__chapter-index">02</span>', false)
        ->assertSee('<h3 class="facts__label">Year of establishment</h3>', false)                             // the colon only hidden
        ->assertSee('facts__tile--stat facts__tile--featured', false)->assertSee('<div class="facts__number">2002</div>', false)
        ->assertSee('With the approval of UGC')
        ->assertSee('<p class="facts__meta">Program approved by UGC</p>', false)                             // bars from the table, total apart
        ->assertSee('<b class="facts__bar-value">31</b>', false)->assertSee('style="width: 58.1%"', false)
        ->assertSee('facts__bar--total"><span class="facts__bar-label">Total Approved Program</span><b class="facts__bar-value">58</b>', false)
        ->assertSee('facts__tile--chips', false)->assertSee('Show all 14')                                    // a long list → chips
        ->assertSee('<ol class="facts__cards">', false)->assertSee('Faculty of Agriculture Sciences')
        ->assertSee('<div class="facts__number">1,140,500</div>', false)->assertSee('Approximately one million items')
        ->assertSee('<article id="venue-events" class="facts__tile facts__tile--m facts__tile--text">', false)->assertSee('Bonomaya 01')   // lines stay text: nothing left out
        ->assertSeeInOrder(['facts__year-tag">2025', 'QS World 2025', 'THE World 2025', 'facts__year-tag">2024', 'THE Impact Rankings 2024', 'facts__year-tag">•', 'SCIMAGO Q1'], false)
        ->assertSee('href="/rankings">View Details</a>', false)
        ->assertSee('Green Gardeen 1')->assertDontSee('alert(1)', false)
        ->assertSee('Note: mail us');

    // Saved before chapters (rows only): one chapter without a title and no chapter bar
    $this->about->update(['content' => ['en' => [['type' => 'facts', 'data' => ['rows' => $chapters[0]['rows'], 'show_nav' => true]]]]]);
    $this->get('/about-us')->assertOk()->assertSee('Green Gardeen 1')->assertDontSee('facts__chapters', false)->assertDontSee('facts__hero', false);

    // Media Library files inside the values and the note count as usage
    expect(FactsBlock::mediaIds(['chapters' => [['rows' => [['label' => 'Map :', 'value' => '<img src="/storage/1/a.jpg" data-media-id="5">']]]], 'note' => '<a href="/storage/2/b.pdf" data-media-id="9">PDF</a>']))
        ->toBe([5, 9]);
});

it('hides pages that are off, scheduled, expired, or under a hidden parent (FR-GEN-12)', function () {
    $this->history->update(['published_at' => now()->addDay()]);
    $this->get('/about-us/history')->assertNotFound();

    $this->history->update(['published_at' => now()->subDay(), 'expires_at' => now()->subHour()]);
    $this->get('/about-us/history')->assertNotFound();

    $this->history->update(['expires_at' => null]);
    $this->about->update(['is_active' => false]);
    $this->get('/about-us')->assertNotFound();
    $this->get('/about-us/history')->assertNotFound();
});

it('shows an entity\'s pages on its microsite only (FR-PAGE-05)', function () {
    $office = Office::create(['name' => 'Exam Office']);
    Page::factory()->ownedBy($office)->create(['title' => ['en' => 'Contact Desk']]);

    $this->get('/office/exam-office/contact-desk')->assertOk()->assertSee('Contact Desk')->assertSee('microsite-name', false);
    $this->get('/contact-desk')->assertNotFound();
    $this->get('/office/no-such-office/contact-desk')->assertNotFound();
});

it('sends an old page address to the new one with 301 (AC-SEO-01, FR-GEN-32)', function () {
    $this->about->changeSlug('en', 'about-diu');

    $this->get('/about-us')->assertRedirect('/about-diu')->assertStatus(301);
    $this->get('/about-us/history')->assertRedirect('/about-diu/history')->assertStatus(301);
    $this->get('/about-diu/history')->assertOk();
});

it('prints SEO tags from the record, filling gaps automatically (FR-GEN-70/71, FR-SEO-01/08)', function () {
    settings()->save('seo', ['title_pattern' => '{title} — {site_name}']);

    $response = $this->get('/about-us')->assertOk();
    $response->assertSee('<title>About Us — '.settings('general.site_name').'</title>', false)
        ->assertSee('<meta name="description" content="Daffodil International University was founded in 2002 in Dhaka.">', false)
        ->assertSee('<link rel="canonical" href="'.url('/about-us').'">', false)
        ->assertSee('"@type":"WebPage"', false)
        ->assertDontSee('name="robots"', false);

    // Homepage: organisation data
    $this->get('/')->assertSee('"@type":"CollegeOrUniversity"', false)->assertDontSee('"@type":"WebPage"', false);
});

it('saves the SEO panel and uses it on the website (FR-GEN-70)', function () {
    $this->actingAs(superAdmin());
    $share = app(MediaLibrary::class)->store(UploadedFile::fake()->image('share.jpg', 1200, 630));

    Livewire::test(EditPage::class, ['record' => $this->about->getRouteKey()])
        ->fillForm(['seoMeta' => [
            'meta_title' => ['en' => 'About DIU'],
            'meta_description' => ['en' => 'A short story of the university.'],
            'og_image' => $share->id,
            'noindex' => true,
        ]])
        ->call('save')
        ->assertHasNoFormErrors();

    $meta = $this->about->refresh()->seoMeta;
    expect($meta->getTranslation('meta_title', 'en'))->toBe('About DIU')
        ->and($meta->firstMedia('og_image')?->is($share))->toBeTrue();

    $this->get('/about-us')
        ->assertSee('About DIU')
        ->assertSee('content="A short story of the university."', false)
        ->assertSee('<meta property="og:image" content="'.Seo::absolute($share->url()).'">', false)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

    // No SEO row for a page whose panel stays empty
    Livewire::test(EditPage::class, ['record' => $this->history->getRouteKey()])->call('save')->assertHasNoFormErrors();
    expect($this->history->refresh()->seoMeta)->toBeNull();
});

it('lists visible pages in sitemap.xml, without noindex or hidden ones (FR-SEO-02, AC-SEO-02)', function () {
    $this->history->seoMeta()->create(['noindex' => true]);

    $this->get('/sitemap.xml')->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.url('/about-us').'</loc>', false)
        ->assertDontSee(url('/about-us/history'), false);

    // A module hidden on the website (FR-SET-05) leaves the sitemap
    settings()->save('modules', ['hidden_on_website' => ['page']]);
    $this->get('/sitemap.xml')->assertDontSee(url('/about-us'), false);
});

it('serves robots.txt from the settings with the sitemap line (FR-SEO-03)', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.url('/sitemap.xml'));

    settings()->save('seo', ['robots_txt' => "User-agent: *\nDisallow: /search"]);
    $this->get('/robots.txt')->assertSee('Disallow: /search')->assertDontSee('Disallow: /admin')->assertSee('Sitemap:');
});
