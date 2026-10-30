<?php

/*
| Phase 4.8b — WCAG 2.1 AA with axe-core (Test Plan NFT-07): every template in English
| and Bangla, no critical or serious issue. Keyboard and screen reader stay manual (NFT-08).
*/

use Tests\Support\BrowserUniversity;

beforeEach(function () {
    $this->university = BrowserUniversity::make();
});

/** Every module page and one record of each most visited kind. */
function accessibilityPages(array $u): array
{
    return array_values(array_unique([...BrowserUniversity::pages(), ...BrowserUniversity::records($u)]));
}

it('has no critical or serious accessibility issue in English', function () {
    visit(accessibilityPages($this->university))->assertNoAccessibilityIssues();
});

it('has no critical or serious accessibility issue in Bangla', function () {
    visit(BrowserUniversity::bangla(accessibilityPages($this->university)))->assertNoAccessibilityIssues();
});

it('has no critical or serious accessibility issue on a phone, with the menu open', function () {
    visit('/')->on()->mobile()->click('.site-header__burger')->assertNoAccessibilityIssues();
});
