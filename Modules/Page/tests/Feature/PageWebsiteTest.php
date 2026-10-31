<?php

/*
| Phase 2.9 — Pages on the website and their SEO (C-11, C-12): addresses down the
| parents, microsite pages, visibility, old address → 301, blocks, SEO panel and
| automatic tags, JSON-LD, sitemap. FR-PAGE-03/04/05, FR-GEN-32/70/71, FR-SEO-01/02/07/08, AC-SEO-01/02.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Office\Models\Office;
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
