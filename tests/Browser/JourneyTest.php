<?php

/*
| Phase 4.8a — the visitors' main journeys and the theme's JavaScript (Test Plan §5, §7.2):
| the header's search window and instant search, the phone menu, the Waiver Calculator,
| the scholarship list — what the feature tests cannot see (Alpine, Livewire in a browser).
*/

use Tests\Support\BrowserUniversity;

beforeEach(function () {
    $this->university = BrowserUniversity::make();
});

it('opens the search window, suggests while typing, and closes with Escape', function () {
    visit('/')
        ->click('[aria-controls="site-search"] >> visible=true')
        ->assertVisible('#site-search')
        ->type('#instant-search-q', 'software')
        ->waitForText('B. Sc. in Software Engineering')
        ->keys('#instant-search-q', 'Escape')
        ->assertMissing('#site-search')
        ->assertNoJavaScriptErrors();
});

it('opens and closes the menu on a phone', function () {
    visit('/')->on()->mobile()
        ->assertMissing('#site-drawer button >> nth=0')
        ->click('.site-header__burger')
        ->assertVisible('#site-drawer button >> nth=0')                    // the drawer itself has no size: its panel is fixed
        ->assertAttribute('.site-header__burger', 'aria-expanded', 'true')
        ->keys('#site-drawer button >> nth=0', 'Escape')
        ->assertMissing('#site-drawer button >> nth=0');
});

it('finds a program and works out its waiver (the Waiver Calculator journey)', function () {
    $page = visit('/waiver-calculator')
        ->type('#waiver-program', 'Software')
        ->click('B. Sc. in Software Engineering')
        ->click('label:has(input[name="waiver-category"][value="'.$this->university['waiver']->id.'"])')   // the tile: its radio is sr-only
        ->fill('#waiver-ssc', '5')
        ->fill('#waiver-hsc', '5')
        ->select('select[wire\:model\.live="boardId"]', (string) $this->university['board']->id)
        ->click('label:has(input[name="waiver-gender"][value="male"])')
        ->click('button[type="submit"][wire\:target="calculate"]')
        ->waitForText('Congratulations!')
        ->assertSee('20%')
        ->assertSee('৳ 810,600')                                   // 952,500 − 20% of 709,500
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();                           // with the program's facts and the result panel (NFT-07)

    $page->script(BrowserUniversity::PRINT_JS);                    // Print: the result only, without the form and its buttons (Theme Guide §13)
    $page->assertMissing('button[type="submit"][wire\:target="calculate"]')
        ->assertMissing('.site-header')
        ->assertSee('৳ 810,600');
});

it('opens a scholarship in the list and turns its arrow', function () {
    $title = $this->university['scholarship']->getTranslation('title', 'en');

    visit('/scholarships')
        ->click($title)
        ->assertPresent('#main button.group[aria-expanded="true"]')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});
