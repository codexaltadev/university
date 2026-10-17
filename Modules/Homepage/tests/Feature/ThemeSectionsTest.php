<?php

/*
| Phase 4.3a — Theme sections (owner decision 2026-10-17; Theme Guide §8.4): homepage sections a
| theme declares in theme.json "sections" — catalogue, builder form, values per language with the
| theme's defaults, a module section as source, checks, and the default theme's DIU homepage.
*/

use Filament\Schemas\Components\Group;
use Illuminate\Support\Facades\File;
use Modules\Accreditation\Models\Accreditation;
use Modules\Events\Models\Event;
use Modules\Homepage\Support\Homepages;
use Platform\Homepage\SectionContext;
use Platform\Homepage\SectionType;
use Platform\Homepage\ThemeSections;
use Platform\Relations\RelationEngine;

beforeEach(function () {
    $this->homepages = app(Homepages::class);
});

it('offers the active theme\'s sections in the catalogue, next to core and module ones', function () {
    $catalogue = $this->homepages->catalogue();

    expect($catalogue)->toHaveKeys(['core.cta', 'theme.hero', 'theme.mission', 'theme.degree', 'theme.events', 'theme.link-button'])
        ->and($catalogue['theme.degree'])->toBeInstanceOf(SectionType::class)
        ->and($catalogue['theme.degree']->view)->toBe('theme::sections.degree')
        ->and($catalogue['theme.degree']->model)->toBeNull()
        ->and($catalogue['theme.mission']->model)->toBe(Accreditation::class)   // "source": "accreditation.recognition" — source modes in the builder
        ->and($catalogue['theme.events']->model)->toBeNull()                    // "events.past" picks its own events
        ->and($this->homepages->catalogueOptions())->toHaveKey('Theme');

    // The form: the source's own settings, then the theme fields under "content"
    $schema = $catalogue['theme.degree']->schema();
    expect(end($schema))->toBeInstanceOf(Group::class);
});

it('gives the view the values in the page\'s language, the theme\'s defaults where nothing is entered', function () {
    $fields = $this->homepages->catalogue()['theme.degree'];
    $content = fn (array $values, string $locale) => $fields->content(new SectionContext('site:0', ['content' => $values], $locale));

    $default = $content([], 'en');
    expect($default['heading'])->toBe('Find Your Degree at DIU')
        ->and($default['button_label'])->toBe('Browse our All Faculties')
        ->and(array_column($default['links'], 'label'))->toBe(['Undergraduate Programs', 'Post Graduate Programs']);

    $entered = ['button_label' => ['en' => 'All faculties', 'bn' => 'সব ফ্যাকাল্টি'], 'links' => [['label' => ['en' => 'Diplomas'], 'url' => '/programs?level=diploma']]];
    expect($content($entered, 'bn')['button_label'])->toBe('সব ফ্যাকাল্টি')
        ->and($content($entered, 'bn')['links'])->toBe([['label' => 'Diplomas', 'url' => '/programs?level=diploma']])   // no Bangla yet: English
        ->and($content($entered, 'en')['button_label'])->toBe('All faculties');

    // A picture's default is a file of the theme
    $partners = $this->homepages->catalogue()['theme.research']->content(new SectionContext('site:0', [], 'en'))['partners'];
    expect($partners[0]['logo']['url'])->toContain('themes/default/images/partners/logo-2.png');
});

it('takes a module section\'s items as its source', function () {
    $event = Event::factory()->create(['title' => ['en' => 'Convocation 2026'], 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)->addHours(4)]);
    app(RelationEngine::class)->sync($event, [], mainSite: true, user: null);   // shown on the main site

    $layout = $this->homepages->create('site:0');
    $layout->sections()->create(['section_type' => 'theme.events', 'settings' => ['source_mode' => 'latest', 'count' => 3], 'sort_order' => 1]);
    $section = $this->homepages->forOwner('site:0', 'en')[0];

    expect($section['key'])->toBe('theme.events')
        ->and($section['items']->pluck('title')->all())->toContain('Convocation 2026')
        ->and($section['content']['title_strong'])->toBe('Recent');
});

it('draws the DIU homepage from the theme\'s sections', function () {
    $layout = $this->homepages->create('site:0');
    foreach (['theme.hero', 'theme.mission', 'theme.degree', 'theme.scholarships', 'theme.research', 'theme.link-button'] as $i => $type) {
        $layout->sections()->create(['section_type' => $type, 'sort_order' => $i + 1]);
    }
    $layout->sections()->where('section_type', 'theme.degree')->first()->update(['heading' => ['en' => 'Pick your degree']]);

    $this->get('/')->assertOk()
        ->assertSee('Beyond Classrooms, We Build Futures.')
        ->assertSee('#1 in Bangladesh')->assertSee('<strong>Pioneering excellence</strong>', false)
        ->assertSee('Pick your degree')->assertDontSee('Find Your Degree at DIU')                      // the builder's heading wins
        ->assertSee('Undergraduate Programs')
        ->assertSee('youtube.com/vi/RUoHuYIa2qQ', false)->assertSee('Up to 100%')
        ->assertSee('x-data="counter(5600', false)->assertSee('Research &amp; Innovation Partners', false)
        ->assertSee('Stay Connected!');
});

it('checks the sections of theme.json (FR-THEME-02)', function () {
    $dir = storage_path('framework/testing/theme-sections-'.uniqid());
    File::ensureDirectoryExists("{$dir}/views/sections");
    File::put("{$dir}/views/sections/ok.blade.php", 'ok');

    expect(ThemeSections::validate(['ok' => ['fields' => [['key' => 'title', 'type' => 'text']]]], $dir))->toBe([])
        ->and(ThemeSections::validate(['missing' => ['fields' => []]], $dir))->toContain('Missing section view: views/sections/missing.blade.php')
        ->and(ThemeSections::validate(['Bad Name' => []], $dir)[0])->toContain('lowercase')
        ->and(ThemeSections::validate(['ok' => ['fields' => [['key' => 'x', 'type' => 'colour'], ['key' => 'rows', 'type' => 'repeater', 'fields' => [['type' => 'text']]]]]], $dir))->toHaveCount(2);

    File::deleteDirectory($dir);
});
