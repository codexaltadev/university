<?php

/*
| Phase 3.21 — M-29 Important Links (DB-05 §14 v1.10, SRS Content FR-LINK-01..03): each place's
| links by group in their order; homepage section; the footer block through the new kernel
| footer block hook; the weekly check with a notice for newly broken links.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Links\Filament\Resources\Links\Pages\CreateLink;
use Modules\Links\Models\Link;
use Modules\Links\Sections\ImportantLinksSection;
use Modules\Links\Support\LinkChecker;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Vocabularies;
use Platform\Website\FooterBlocks;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Links');
    app(ModulePermissions::class)->syncAttachable('links', [Link::class]);
    $this->engine = app(RelationEngine::class);
    Vocabularies::ensureTerms(Link::GROUP, [
        ['name' => ['en' => 'Government'], 'slug' => 'government'],
        ['name' => ['en' => 'Student Services'], 'slug' => 'student-services'],
    ]);
    $this->group = fn (string $name): int => (int) array_search($name, app(Vocabularies::class)->options(Link::GROUP), true);

    $this->make = function (string $title, string $url, ?string $group = null, array $places = [], bool $main = true, array $attributes = []): Link {
        $link = Link::factory()->create(['title' => ['en' => $title], 'url' => $url, ...$attributes]);
        $group && $link->syncTerms(Link::GROUP, [($this->group)($group)]);
        $this->engine->sync($link, $places, mainSite: $main, user: null);

        return $link;
    };
});

it('lists the main site\'s links by group, in their order (FR-LINK-01/02)', function () {
    ($this->make)('Student Portal', 'https://portal.example.edu', 'Student Services');
    ($this->make)('UGC', 'https://ugc.gov.bd', 'Government');
    ($this->make)('Library', '/library', 'Student Services');
    ($this->make)('Alumni', 'https://alumni.example.edu');
    ($this->make)('Hidden', 'https://hidden.example.edu', attributes: ['is_active' => false]);
    $cse = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id, 'short_name' => 'CSE']);
    ($this->make)('CSE Lab Booking', 'https://lab.example.edu', places: ['department.department:'.$cse->id], main: false);

    $this->get('/links')->assertOk()
        ->assertSeeInOrder(['Government', 'UGC', 'Student Services', 'Student Portal', 'Library', 'Other links', 'Alumni'])
        ->assertSee('href="'.url('/library').'"', false)->assertSee('target="_blank"', false)
        ->assertDontSee('Hidden')->assertDontSee('CSE Lab Booking');
    $this->get(rtrim($cse->url(), '/').'/links')->assertOk()->assertSee('CSE Lab Booking')->assertDontSee('UGC');

    expect((new ImportantLinksSection)->items(new SectionContext('site:0', ['group' => ($this->group)('Student Services')], 'en'))->pluck('title')->all())
        ->toBe(['Student Portal', 'Library']);
});

it('accepts web, site, mail and phone addresses only', function () {
    expect(fn () => Link::factory()->create(['url' => 'javascript:alert(1)']))->toThrow(ValidationException::class)
        ->and(fn () => Link::factory()->create(['url' => 'ugc.gov.bd']))->toThrow(ValidationException::class);
    expect(Link::factory()->create(['url' => 'mailto:info@example.edu'])->exists)->toBeTrue();

    $this->actingAs(superAdmin());
    Livewire::test(CreateLink::class)
        ->fillForm(['title' => ['en' => 'BTEB'], 'url' => 'https://bteb.gov.bd', 'show_in' => [], 'main_site' => true])
        ->call('create')->assertHasNoFormErrors();
    expect(Link::query()->where('url', 'https://bteb.gov.bd')->exists())->toBeTrue();
});

it('shows the links in a footer column through the footer block hook', function () {
    ($this->make)('UGC', 'https://ugc.gov.bd', 'Government');
    ($this->make)('Student Portal', 'https://portal.example.edu', 'Student Services');

    expect(app(FooterBlocks::class)->all())->toHaveKey('links.footer');
    $html = (string) app(FooterBlocks::class)->render(['type' => 'links.footer', 'links_group' => ($this->group)('Government')]);
    expect($html)->toContain('UGC')->not->toContain('Student Portal');

    $component = Blade::render('<x-platform::footer-block :block="$block" />', ['block' => ['type' => 'links.footer']]);
    expect($component)->toContain('UGC')->toContain('Student Portal');
    expect(app(FooterBlocks::class)->render(['type' => 'no-such.block']))->toBeNull();
});

it('checks outside links every week and tells the editors once about a newly broken one (FR-LINK-03)', function () {
    $admin = superAdmin();
    $ok = ($this->make)('UGC', 'https://ugc.gov.bd');
    $gone = ($this->make)('Old portal', 'https://old.example.edu/x');
    $inside = ($this->make)('Library', '/library');
    Http::fake([
        'ugc.gov.bd/*' => Http::response('', 200),
        'old.example.edu/*' => Http::response('', 404),
    ]);

    expect(app(LinkChecker::class)->run())->toBe(['checked' => 2, 'broken' => 1, 'newly' => 1]);
    expect($ok->fresh()->last_status_code)->toBe(200)->and($gone->fresh()->isBroken())->toBeTrue()
        ->and($inside->fresh()->last_checked_at)->toBeNull()
        ->and($ok->fresh()->updated_at->equalTo($ok->updated_at))->toBeTrue();                          // a check is not an edit
    expect($admin->notifications()->count())->toBe(1);

    app(LinkChecker::class)->run();                                                                    // still broken: no second notice
    expect($admin->notifications()->count())->toBe(1);

    $gone->update(['url' => 'https://new.example.edu']);                                               // a new address is checked again
    expect($gone->fresh()->last_checked_at)->toBeNull();
    $this->artisan('links:check')->assertSuccessful();
});
