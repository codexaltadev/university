<?php

/*
| Phase 2.9 — SEO & Redirect (C-12): redirects before routing, internal targets,
| loop and chain protection, CSV import, 404 log with one-click redirect, screens.
| FR-SEO-04/05/06/09.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Modules\Page\Models\Page;
use Modules\Seo\Filament\Resources\NotFoundLogs\Pages\ListNotFoundLogs;
use Modules\Seo\Filament\Resources\Redirects\Pages\CreateRedirect;
use Modules\Seo\Filament\Resources\Redirects\Pages\ListRedirects;
use Modules\Seo\Filament\Resources\Redirects\RedirectResource;
use Modules\Seo\Filament\Resources\SearchTerms\Pages\ListSearchTerms;
use Modules\Seo\Models\NotFoundLog;
use Modules\Seo\Models\Redirect;
use Modules\Seo\Models\SearchTerm;
use Modules\Seo\Support\Redirects;
use Platform\Modules\ModulePermissions;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Seo');
    $this->redirects = app(Redirects::class);
    $this->page = Page::factory()->create(['title' => ['en' => 'Admission']]);
});

it('redirects an old address before any route, counting visits (FR-SEO-04)', function () {
    $this->redirects->save(['source_path' => '/Old-Admission/', 'destination_url' => '/admission']);
    $this->redirects->save(['source_path' => '/summer', 'destination_url' => 'https://summer.example.edu', 'status_code' => 302]);

    $this->get('/old-admission?utm_source=fb')->assertStatus(301)->assertRedirect('/admission?utm_source=fb');
    $this->get('/OLD-ADMISSION/')->assertStatus(301);                               // case and slash do not matter
    $this->get('/summer')->assertStatus(302)->assertRedirect('https://summer.example.edu');

    $redirect = Redirect::query()->where('source_path', '/old-admission')->first();
    expect($redirect->hits)->toBe(2)->and($redirect->last_hit_at)->not->toBeNull();

    $redirect->update(['is_active' => false]);
    $this->get('/old-admission')->assertNotFound();
});

it('follows a record even after its address changes (P-15)', function () {
    $this->redirects->save(['source_path' => '/admission.php', 'target' => $this->redirects->targetKey($this->page)]);

    $this->get('/admission.php')->assertRedirect('/admission');

    $this->page->changeSlug('en', 'apply');
    $this->get('/admission.php')->assertRedirect('/apply');
});

it('refuses loops and removes chains (FR-SEO-09)', function () {
    $this->redirects->save(['source_path' => '/a', 'destination_url' => '/b']);

    expect($this->redirects->problem(['source_path' => '/b', 'destination_url' => '/a']))->toBe(__('seo::seo.errors.loop'))
        ->and($this->redirects->problem(['source_path' => '/c', 'destination_url' => '/c']))->toBe(__('seo::seo.errors.loop'))
        ->and($this->redirects->problem(['source_path' => '/a', 'destination_url' => '/x']))->toBe(__('seo::seo.errors.taken'))
        ->and($this->redirects->problem(['source_path' => '/admin/users', 'destination_url' => '/x']))->toBe(__('seo::seo.errors.reserved'));

    // A → B exists; B → C makes A → C, and D → A goes straight to C
    $this->redirects->save(['source_path' => '/b', 'destination_url' => '/c']);
    $this->redirects->save(['source_path' => '/d', 'destination_url' => '/a']);

    expect(Redirect::query()->pluck('destination_url', 'source_path')->all())
        ->toBe(['/a' => '/c', '/b' => '/c', '/d' => '/c']);
});

it('imports the old website\'s addresses from CSV (FR-SEO-05)', function () {
    $this->redirects->save(['source_path' => '/notice.php', 'destination_url' => '/old-target']);
    $csv = tempnam(sys_get_temp_dir(), 'csv');
    File::put($csv, "source,destination,status\n/about.php,/about,301\n/notice.php,/notices,\n/loop,/loop\n/empty,\n/campus,https://maps.example.com,302\n");

    $result = $this->redirects->import($csv);

    expect($result['created'])->toBe(2)
        ->and($result['updated'])->toBe(1)
        ->and($result['skipped'])->toHaveCount(2)
        ->and($result['skipped'][0])->toContain('Line 4')
        ->and(Redirect::query()->where('source_path', '/notice.php')->value('destination_url'))->toBe('/notices')
        ->and(Redirect::query()->where('source_path', '/campus')->value('status_code'))->toBe(302)
        ->and(Redirect::query()->where('source', 'import')->count())->toBe(3);
});

it('logs website 404s, not assets or the admin panel, and redirects them in one click (FR-SEO-06)', function () {
    $this->get('/old-faculty-list', ['referer' => 'https://google.com'])->assertNotFound();
    $this->get('/old-faculty-list')->assertNotFound();
    $this->get('/missing.png')->assertNotFound();
    $this->get('/admin/nothing-here')->assertNotFound();

    $log = NotFoundLog::query()->sole();
    expect($log->path)->toBe('/old-faculty-list')->and($log->hits)->toBe(2);

    $this->actingAs(superAdmin());
    Livewire::test(ListNotFoundLogs::class)
        ->assertCanSeeTableRecords([$log])
        ->callTableAction('redirect', $log, data: ['destination_url' => '/admission', 'status_code' => 301])
        ->assertNotified();

    expect($log->refresh()->is_resolved)->toBeTrue()
        ->and(Redirect::query()->where('source', '404')->value('destination_url'))->toBe('/admission');
    $this->get('/old-faculty-list')->assertRedirect('/admission');

    // Resolved rows go after 90 days
    $log->update(['last_seen_at' => now()->subDays(91)]);
    expect(NotFoundLog::clean())->toBe(1);
});

it('creates redirects on screen to an address or a record; refuses loops there too', function () {
    $this->actingAs(superAdmin());
    $this->redirects->save(['source_path' => '/x', 'destination_url' => '/y']);

    Livewire::test(CreateRedirect::class)
        ->fillForm(['source_path' => '/y', 'kind' => 'url', 'destination_url' => '/x'])
        ->call('create')
        ->assertHasFormErrors(['source_path']);

    Livewire::test(CreateRedirect::class)
        ->fillForm(['source_path' => '/admission-2025', 'kind' => 'record', 'target' => $this->redirects->targetKey($this->page), 'status_code' => 301])
        ->call('create')
        ->assertHasNoFormErrors();

    $redirect = Redirect::query()->where('source_path', '/admission-2025')->sole();
    expect($redirect->target?->is($this->page))->toBeTrue()
        ->and($redirect->destination_url)->toBeNull()
        ->and(array_keys($this->redirects->searchTargets('Admis')))->toContain($this->redirects->targetKey($this->page));
});

it('imports CSV from the Redirects screen', function () {
    $this->actingAs(superAdmin());
    $file = UploadedFile::fake()->createWithContent('map.csv', "/old,/admission\n");

    Livewire::test(ListRedirects::class)->callAction('import', data: ['file' => $file])->assertNotified();

    expect(Redirect::query()->where('source_path', '/old')->exists())->toBeTrue();
});

it('lets only permitted users manage redirects', function () {
    $this->actingAs(makeUser());
    $this->get(RedirectResource::getUrl())->assertForbidden();

    $this->actingAs(makeUser(['ViewAny:Redirect']));
    $this->get(RedirectResource::getUrl())->assertOk();
    $this->get(RedirectResource::getUrl('create'))->assertForbidden();
});

it('counts what visitors search and what they miss, offers the popular searches, and lists them on screen (Phase 4.7g)', function () {
    $this->get('/search?q=Admission')->assertOk();
    $this->get('/search?q=admission%20%20')->assertOk();                        // the same search, whatever the case and spaces
    $this->get('/search?q=Hostel+rent')->assertOk();                        // nothing on the site: a miss

    expect(SearchTerm::query()->orderBy('term')->get(['term', 'hits', 'last_results'])->toArray())->toBe([
        ['term' => 'admission', 'hits' => 2, 'last_results' => 1],
        ['term' => 'hostel rent', 'hits' => 1, 'last_results' => 0],
    ]);

    // Popular searches: the ones in Settings › Search, else the most searched that found something
    $this->get('/search')->assertOk()->assertSeeInOrder(['Popular searches', 'admission'])->assertDontSee('hostel rent');
    settings()->save('search', ['popular' => 'Tuition fees, Waiver']);
    $this->get('/search')->assertOk()->assertSeeInOrder(['Popular searches', 'Tuition fees', 'Waiver']);
    settings()->save('search', ['show_popular' => false]);
    $this->get('/search')->assertOk()->assertDontSee('Popular searches');

    $this->actingAs(superAdmin());
    Livewire::test(ListSearchTerms::class)->assertCanSeeTableRecords(SearchTerm::all())
        ->filterTable('found', false)->assertCanSeeTableRecords(SearchTerm::query()->where('term', 'hostel rent')->get())
        ->assertCanNotSeeTableRecords(SearchTerm::query()->where('term', 'admission')->get());

    // Not searched for 180 days: gone
    SearchTerm::query()->where('term', 'hostel rent')->update(['last_searched_at' => now()->subDays(181)]);
    expect(SearchTerm::clean())->toBe(1);
});
