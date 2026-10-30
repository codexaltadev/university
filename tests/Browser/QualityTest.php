<?php

/*
| Phase 4.8d — the default theme's checklist (Theme Guide §13, UI/UX Part B §7–§8):
| no page scrolls sideways from a small phone to a wide screen, in English and Bangla, also
| with long Bangla titles; reduced motion stops every animation; print shows only the content.
*/

use Modules\Notice\Models\Notice;
use Modules\Program\Models\Program;
use Tests\Support\BrowserUniversity;

beforeEach(function () {
    $this->university = BrowserUniversity::make();
});

/** Opens each address once and measures it at every width: [address@width => elements sticking out]. */
function overflowing(array $urls): array
{
    $found = [];
    foreach ($urls as $url) {
        $page = visit($url);
        foreach ([360 => 760, 768 => 1024, 1024 => 768, 1440 => 900] as $width => $height) {
            $page->resize($width, $height);
            if ($elements = $page->script(BrowserUniversity::OVERFLOW_JS)) {
                $found["{$url}@{$width}"] = $elements;
            }
        }
    }

    return $found;
}

it('never scrolls sideways, from a 360px phone to a wide screen (UI/UX §7)', function () {
    $urls = array_values(array_unique([...BrowserUniversity::pages(), ...BrowserUniversity::records($this->university)]));

    expect(overflowing($urls))->toBe([]);
});

it('never scrolls sideways in Bangla, with long Bangla titles in cards and headings', function () {
    $u = $this->university;
    $u['program']->setTranslation('name', 'bn', 'কম্পিউটার বিজ্ঞান ও প্রকৌশল এবং কৃত্রিম বুদ্ধিমত্তা ও ডেটা বিজ্ঞানে স্নাতক (সম্মান) প্রোগ্রাম')->save();
    $notice = Notice::factory()->create(['title' => [
        'en' => 'Long notice',
        'bn' => 'স্নাতক ও স্নাতকোত্তর পর্যায়ের সকল শিক্ষার্থীর জন্য গ্রীষ্মকালীন সেমিস্টারের মধ্যবর্তী পরীক্ষার সংশোধিত সময়সূচি এবং আসন বিন্যাস সংক্রান্ত বিজ্ঞপ্তি',
    ]]);

    $urls = BrowserUniversity::bangla(array_values(array_unique([...BrowserUniversity::pages(), ...BrowserUniversity::records($u), $notice->url()])));

    expect(Program::query()->find($u['program']->id)->getTranslation('name', 'bn'))->toContain('কৃত্রিম')
        ->and(overflowing($urls))->toBe([]);
});

it('stops every animation and the background video when the visitor asks for reduced motion', function () {
    $u = $this->university;

    foreach (['/', '/scholarships', $u['program']->url(), $u['faculty']->url()] as $url) {
        $page = visit($url, ['reducedMotion' => 'reduce']);
        $page->assertScript("matchMedia('(prefers-reduced-motion: reduce)').matches")
            ->assertScript("document.getAnimations().filter((a) => a.playState === 'running').length === 0")
            ->assertScript("[...document.querySelectorAll('video')].every((v) => v.paused)")
            ->assertScript("[...document.querySelectorAll('.reveal')].every((el) => getComputedStyle(el).opacity === '1')");
    }
});

it('prints a notice as its content only, black on white (Theme Guide §13 print view)', function () {
    $page = visit($this->university['notice']->url());
    expect($page->script(BrowserUniversity::PRINT_JS))->toBeGreaterThan(0);

    $page->assertScript("getComputedStyle(document.querySelector('.site-header')).display === 'none'")
        ->assertScript("getComputedStyle(document.querySelector('.site-footer')).display === 'none'")
        ->assertScript("getComputedStyle(document.body).paddingTop === '0px'")
        ->assertScript("getComputedStyle(document.querySelector('#main h1')).color === 'rgb(0, 0, 0)'")
        ->assertScript("[...document.querySelectorAll('.reveal')].every((el) => getComputedStyle(el).opacity === '1')")
        ->assertSee('Mid-term exam schedule');
});

it('prints the homepage without the hero picture, with every counter at its number', function () {
    $page = visit('/');
    $page->script(BrowserUniversity::PRINT_JS);

    $page->assertScript("[...document.querySelectorAll('#main video, #main iframe')].every((el) => getComputedStyle(el).display === 'none')")
        ->assertScript("[...document.querySelectorAll('[x-data^=\"counter\"]')].every((el) => el.textContent.trim() !== '0')");
});
