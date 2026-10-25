<?php

/*
 * S08's part for DIU's /admission (DIUFronend admission/layout, routes): the installed page gets DIU's line,
 * "Browse by Section", Quick Links and its blocks in DIU's order — only where still as installed.
 */

use Legacy\Migration\Steps\S08Admission;
use Modules\Admission\Database\Seeders\AdmissionDatabaseSeeder;
use Modules\Menu\Models\MenuItem;
use Modules\Page\Models\Page;

it('arranges the installed /admission page as DIU\'s and keeps an editor\'s changes', function () {
    (new AdmissionDatabaseSeeder)->run();
    $page = Page::query()->sole();

    $arrange = function (): void {
        $step = app(S08Admission::class);
        (fn () => $this->landingPage())->call($step);
    };
    $arrange();
    $page->refresh();

    expect($page->getTranslation('excerpt', 'en'))->toStartWith('Browse 51 undergraduate')
        ->and(array_column($page->quick_links, 'url'))->toBe(['/waiver-calculator', '/article/credit-transfer', '/scholarships', 'https://photoframe.daffodilvarsity.edu.bd/admission-photo-frame'])
        ->and(array_column($page->getTranslation('content', 'en'), 'type'))->toBe(['admission-apply', 'admission-circulars', 'admission-subscribe', 'admission-highlights', 'admission-steps', 'admission-contacts'])
        ->and($page->getTranslation('content', 'en')[0]['data']['heading'])->toBe('Admission Information')
        ->and(MenuItem::query()->where('menu_id', $page->sidebar_menu_id)->orderBy('sort_order')->get()->map(fn ($i) => $i->title)->all())
        ->toBe(array_column(S08Admission::LANDING['sections'], 0));

    // a second run changes nothing an editor made
    $page->update(['excerpt' => ['en' => 'Our own line'], 'quick_links' => [['label' => ['en' => 'Fees'], 'url' => '/tuition-fees']]]);
    MenuItem::query()->where('menu_id', $page->sidebar_menu_id)->first()->update(['title' => ['en' => 'Overview']]);
    $arrange();
    $page->refresh();

    expect($page->getTranslation('excerpt', 'en'))->toBe('Our own line')
        ->and($page->quick_links)->toHaveCount(1)
        ->and(MenuItem::query()->where('menu_id', $page->sidebar_menu_id)->count())->toBe(7)
        ->and(MenuItem::query()->where('menu_id', $page->sidebar_menu_id)->pluck('title')->all())->toContain('Overview');
});
