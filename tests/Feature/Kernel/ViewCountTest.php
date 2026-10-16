<?php

/*
| Phase 3.8 — view counter (P-17, FR-CNT-C08, DB-02 §12.3): page views gathered in the cache,
| written in one go by uwms:views:flush; bots not counted; legacy totals seeded; rows go with
| the record and with an uninstalled module's types.
*/

use Illuminate\Support\Facades\DB;
use Modules\Page\Models\Page;
use Platform\Views\ViewCounter;

beforeEach(function () {
    $this->views = app(ViewCounter::class);
    $this->page = Page::factory()->create(['title' => ['en' => 'About']]);
});

it('gathers views in the cache and writes them on flush, without bots', function () {
    $this->views->hit($this->page, 'Mozilla/5.0');
    $this->views->hit($this->page, 'Mozilla/5.0');
    $this->views->hit($this->page, 'Mozilla/5.0 (compatible; bingbot/2.0)');
    $this->views->hit($this->page, '');

    expect(DB::table('core_view_counts')->count())->toBe(0);                    // nothing written per view

    $this->artisan('uwms:views:flush')->assertSuccessful();
    expect($this->views->total($this->page))->toBe(2);

    $this->views->hit($this->page, 'Mozilla/5.0');
    expect($this->views->flush())->toBe(1)->and($this->views->flush())->toBe(0)
        ->and($this->views->total($this->page))->toBe(3);
});

it('starts at a legacy total without lowering one, and forgets a record\'s views', function () {
    $this->views->seed($this->page, 500);
    $this->views->seed($this->page, 100);
    expect($this->views->total($this->page))->toBe(500);

    $this->views->forgetTypes([$this->page->getMorphClass()]);
    expect($this->views->total($this->page))->toBe(0);
});
