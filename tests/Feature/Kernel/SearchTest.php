<?php

/*
| Phase 2.10 — Global Search (C-17): index kept current, only visible records,
| grouped results, module filter, highlight, Bangla, "only in this entity",
| instant search, module on / off, scheduled content.
| FR-SRCH-01..06, FR-SRCH-09, AC-SRCH-01.
*/

use Livewire\Livewire;
use Modules\Office\Models\Office;
use Modules\Page\Models\Page;
use Platform\Modules\ModuleManager;
use Platform\Search\Livewire\InstantSearch;
use Platform\Search\SearchDocument;
use Platform\Search\SearchIndex;

beforeEach(function () {
    $this->index = app(SearchIndex::class);

    $this->lab = Page::factory()->create([
        'title' => ['en' => 'Cyber Security Lab', 'bn' => 'সাইবার নিরাপত্তা ল্যাব'],
        'excerpt' => ['en' => 'Hands-on network defence.'],
        'content' => ['en' => [['type' => 'text', 'data' => ['body' => '<p>Penetration testing and <b>forensics</b>.</p>']]],
            'bn' => [['type' => 'text', 'data' => ['body' => '<p>ভর্তি ও বৃত্তি তথ্য।</p>']]]],
    ]);
    $this->office = Office::create(['name' => 'Cyber Security Office']);
});

it('indexes visible records per language, with their text and address (FR-SRCH-01/04/09)', function () {
    $rows = fn () => SearchDocument::query()->where('searchable_type', $this->lab->getMorphClass())->where('searchable_id', $this->lab->id);
    $en = $rows()->where('locale', 'en')->sole();
    $bn = $rows()->where('locale', 'bn')->sole();

    expect($en->module_slug)->toBe('page')
        ->and($en->url)->toBe('/cyber-security-lab')
        ->and($en->body)->toContain('Hands-on network defence. Penetration testing and forensics.')
        ->and($en->body)->not->toContain('<p>')
        ->and($bn->title)->toBe('সাইবার নিরাপত্তা ল্যাব')
        ->and($bn->url)->toStartWith('/bn/');

    // A page with no Bangla title has no Bangla row
    $plain = Page::factory()->create(['title' => ['en' => 'Plain']]);
    expect(SearchDocument::query()->where('searchable_type', $plain->getMorphClass())->where('searchable_id', $plain->id)->pluck('locale')->all())->toBe(['en']);
});

it('keeps only visible records: off, deleted, scheduled leave; restored and published come back (FR-SRCH-03)', function () {
    $count = fn (): int => SearchDocument::query()->where('searchable_type', $this->lab->getMorphClass())->where('searchable_id', $this->lab->id)->count();

    $this->lab->update(['is_active' => false]);
    expect($count())->toBe(0);

    $this->lab->update(['is_active' => true]);
    expect($count())->toBe(2);

    $this->lab->delete();
    expect($count())->toBe(0);

    $this->lab->restore();
    expect($count())->toBe(2);

    // Scheduled: not searchable until its time, then picked up without a save (FR-GEN-11)
    $later = Page::factory()->create(['title' => ['en' => 'Convocation 2027'], 'published_at' => now()->addMinutes(3)]);
    expect($this->index->search('Convocation')['total'])->toBe(0);

    $this->travel(4)->minutes();
    $this->artisan('uwms:search:scheduled')->assertSuccessful();
    expect($this->index->search('Convocation')['total'])->toBe(1);
});

it('groups results by module, filters, highlights (FR-SRCH-02, AC-SRCH-01)', function () {
    Page::factory()->create(['title' => ['en' => 'Cyber Security draft'], 'is_active' => false]);

    $results = $this->index->search('cyber security');

    expect($results['total'])->toBe(2)
        ->and(array_keys($results['groups']))->toEqualCanonicalizing(['page', 'office'])
        ->and($results['groups']['office'])->toBe(['label' => 'Offices', 'count' => 1])
        ->and((string) $results['items']->first()['title'])->toContain('<mark>Cyber</mark> <mark>Security</mark>');

    $pagesOnly = $this->index->search('cyber', module: 'page');
    expect($pagesOnly['items']->total())->toBe(1)
        ->and($pagesOnly['items']->first()['url'])->toBe('/cyber-security-lab')
        ->and((string) $pagesOnly['items']->first()['excerpt'])->toContain('network');

    // Every word must match; short words and unknown words work too
    expect($this->index->search('cyber zebra')['total'])->toBe(0)
        ->and($this->index->search('lab')['total'])->toBe(1)
        ->and($this->index->search('   ')['total'])->toBe(0);
});

it('searches Bangla text in the Bangla website (FR-SRCH-04)', function () {
    expect($this->index->search('বৃত্তি', locale: 'bn')['total'])->toBe(1)
        ->and($this->index->search('নিরাপত্তা', locale: 'bn')['items']->first()['url'])->toBe('/bn'.$this->lab->url('bn'))
        ->and($this->index->search('বৃত্তি', locale: 'en')['total'])->toBe(0);
});

it('searches only inside one entity when asked (FR-SRCH-06)', function () {
    $desk = Page::factory()->ownedBy($this->office)->create(['title' => ['en' => 'Cyber help desk']]);
    $key = $this->office->getMorphClass().':'.$this->office->id;

    $inside = $this->index->search('cyber', ownerKey: $key);
    expect(collect($inside['items']->items())->pluck('url')->all())
        ->toEqualCanonicalizing(['/office/cyber-security-office', $desk->url()]);
});

it('drops a deactivated module\'s records and brings them back on activation', function () {
    app(ModuleManager::class)->syncBundled();

    app(ModuleManager::class)->deactivate('office');
    expect(SearchDocument::query()->where('module_slug', 'office')->count())->toBe(0);

    app(ModuleManager::class)->activate('office');
    expect(SearchDocument::query()->where('module_slug', 'office')->count())->toBe(2);
});

it('rebuilds the index from the records', function () {
    SearchDocument::query()->delete();

    $this->artisan('uwms:search:rebuild')->assertSuccessful();
    expect(SearchDocument::query()->count())->toBe(4);   // lab (en, bn) + office (en, bn)

    $this->artisan('uwms:search:rebuild', ['module' => 'office'])->assertSuccessful();
    expect(SearchDocument::query()->count())->toBe(4);
});

it('shows the search page with groups, filter and scope, not indexed by search engines', function () {
    $this->get('/search?q=cyber+security')->assertOk()
        ->assertSee('2 results for')
        ->assertSee('Offices (1)')
        ->assertSee('<mark>Cyber</mark>', false)
        ->assertSee('noindex', false);

    $this->get('/search?q=cyber&module=office')->assertOk()->assertSee('/office/cyber-security-office')->assertDontSee('/cyber-security-lab');

    $key = $this->office->getMorphClass().':'.$this->office->id;
    $this->get('/search?q=cyber&in='.urlencode($key))->assertOk()->assertSee('Only in Cyber Security Office')->assertDontSee('/cyber-security-lab"', false);

    $this->get('/search')->assertOk()->assertDontSee('results for');
});

it('suggests the top results while typing (FR-SRCH-05)', function () {
    foreach (range(1, 6) as $i) {
        Page::factory()->create(['title' => ['en' => "Notice board {$i}"]]);
    }

    Livewire::test(InstantSearch::class)
        ->set('q', 'n')->assertDontSee('board')                             // one letter: nothing yet
        ->set('q', 'notice')->assertSeeHtml('<mark>Notice</mark> board 1')->assertSee('See all 6 results')
        ->assertDontSee('board 6');

    $this->get('/')->assertSee('instant-search-q', false);                   // in the theme's header
});
