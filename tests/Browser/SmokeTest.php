<?php

/*
| Phase 4.8a — the default theme in a real browser (Test Plan §5 Browser E2E):
| every module's website page and the most visited records open without JavaScript
| errors or broken images, on a desktop and on a phone (390px), in English and Bangla.
*/

use Tests\Support\BrowserUniversity;

beforeEach(function () {
    $this->university = BrowserUniversity::make();
});

it('opens every module page without JavaScript errors or broken images', function () {
    $pages = BrowserUniversity::pages();
    expect($pages)->toContain('/programs', '/waiver-calculator', '/scholarships', '/notices');

    visit($pages)->assertNoJavaScriptErrors()->assertNoBrokenImages();
});

it('opens the most visited records', function () {
    foreach (BrowserUniversity::records($this->university) as $url) {
        $this->get($url)->assertOk();                              // the record's own page, not the 404 page
    }

    visit(BrowserUniversity::records($this->university))->assertNoJavaScriptErrors()->assertNoBrokenImages();
});

it('opens every module page on a phone', function () {
    visit(BrowserUniversity::pages())->on()->mobile()->assertNoJavaScriptErrors();
});

it('opens every module page in Bangla', function () {
    visit(BrowserUniversity::bangla(BrowserUniversity::pages()))->assertNoJavaScriptErrors();
});
