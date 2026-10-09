<?php

/*
| Phase 1D — slugs, microsite context and the fallback resolver
| (SAD Part B §6, DB-01 §9–10, Test Plan TC-MS, BRD §11 menu criterion).
*/

use Modules\Office\Models\Office;
use Platform\Context\FallbackResolver;
use Platform\Context\MicrositeResolver;
use Platform\Context\MicrositeSetting;
use Platform\Context\SiteContext;
use Platform\Slugs\Slug;
use Tests\Fixtures\Models\FixtureChild;

beforeEach(function () {
    $this->engineering = faculty('Engineering');
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');

    // A stand-in for the Menu module's store (C-08): owner key => [location => menu]
    $this->menus = [];
    app(FallbackResolver::class)->store(
        'menu',
        fn (string $type, int $id, ?string $location) => $this->menus["{$type}:{$id}"][$location] ?? null,
        fn (?string $location) => "theme default {$location}",
    );
});

function resolver(): FallbackResolver
{
    return app(FallbackResolver::class);
}

function ctx($entity = null): SiteContext
{
    return $entity ? SiteContext::for($entity) : SiteContext::site();
}

function ownerKey($entity): string
{
    return $entity->getMorphClass().':'.$entity->getKey();
}

// ─── Slugs (FR-GEN-30..32) ─────────────────────────────────────────────────

it('gives every language a unique slug and keeps old slugs as history', function () {
    expect($this->cse->slug('en'))->toBe('cse')
        ->and($this->cse->slug('bn'))->toBe('cse');   // no Bangla name yet → from the default

    $other = FixtureChild::create(['parent_id' => $this->engineering->id, 'name' => 'CSE', 'code' => 'CSE2']);   // same name, other record
    expect($other->slug('en'))->toBe('cse-2');

    $this->cse->changeSlug('en', 'Computer Science');
    expect($this->cse->slug('en'))->toBe('computer-science');

    $this->cse->changeSlug('en', 'computer-science');                          // same again: stays current
    expect($this->cse->slug('en'))->toBe('computer-science');

    [$found, $current] = $this->cse::findBySlug('cse', 'en');
    expect($found->is($this->cse))->toBeTrue()->and($current)->toBeFalse();   // old URL still finds it

    expect(department($this->engineering, 'CSE Two')->slug('en'))->toBe('cse-two');

    $this->cse->forceDelete();
    expect(Slug::where('sluggable_id', $this->cse->id)->where('sluggable_type', 'test.child')->count())->toBe(0);
});

// ─── Microsite context (§6.1) ──────────────────────────────────────────────

it('turns a public URL into a microsite context', function () {
    $resolve = fn (string $path) => app(MicrositeResolver::class)->resolve($path, 'en');

    $result = $resolve('/test-dept/cse/news/convocation-2026');
    expect($result['context']->entity->is($this->cse))->toBeTrue()
        ->and($result['rest'])->toBe('news/convocation-2026')
        ->and($result['redirect'])->toBeNull();

    expect($resolve('/bn/test-faculty/engineering')['context']->entity->is($this->engineering))->toBeTrue()
        ->and($resolve('/news/x'))->toMatchArray(['rest' => 'news/x'])
        ->and($resolve('/news/x')['context']->isMicrosite())->toBeFalse()
        ->and($resolve('/test-dept/nothing'))->toBeNull();

    $this->eee->deactivate();
    expect($resolve('/test-dept/eee'))->toBeNull();   // inactive microsite → 404
});

it('an old slug resolves with a 301 target to the current URL', function () {
    $this->cse->changeSlug('en', 'Computer Science');

    $result = app(MicrositeResolver::class)->resolve('/en/test-dept/cse/notices', 'en');

    expect($result['redirect'])->toBe('/en/test-dept/computer-science/notices')
        ->and(app(MicrositeResolver::class)->urlFor($this->cse, 'en'))->toBe('/test-dept/computer-science');
});

it('a context knows its chain: entity, ancestors, main site', function () {
    expect(ctx($this->cse)->chain())->toBe([ownerKey($this->cse), ownerKey($this->engineering), 'site:0'])
        ->and(ctx()->chain())->toBe(['site:0']);

    $office = Office::create(['name' => 'Registrar Office']);
    expect(ctx($office)->chain())->toBe([ownerKey($office), 'site:0'])   // offices fall back to the main site
        ->and(app(MicrositeResolver::class)->resolve('/office/registrar-office', 'en')['context']->entity->is($office))->toBeTrue();
});

// ─── Fallback resolver (§6.2) ──────────────────────────────────────────────

it('uses its own menu, else the nearest parent, else the main site (BRD §11)', function () {
    $this->menus = [
        'site:0' => ['primary' => 'main primary', 'footer' => 'main footer'],
        ownerKey($this->engineering) => ['primary' => 'engineering primary'],
        ownerKey($this->cse) => ['primary' => 'cse primary'],
    ];

    expect(resolver()->resolve('menu', 'primary', ctx($this->cse)))->toBe('cse primary')
        ->and(resolver()->resolve('menu', 'primary', ctx($this->eee)))->toBe('engineering primary')
        ->and(resolver()->source('menu', 'primary', ctx($this->eee)))->toBe(ownerKey($this->engineering))
        ->and(resolver()->resolve('menu', 'primary', ctx()))->toBe('main primary');

    // CSE deletes its own menu → back to the faculty's
    unset($this->menus[ownerKey($this->cse)]);
    resolver()->flush();
    expect(resolver()->resolve('menu', 'primary', ctx($this->cse)))->toBe('engineering primary');
});

it('resolves each location separately (TC-MS-03)', function () {
    $this->menus = [
        'site:0' => ['footer' => 'main footer'],
        ownerKey($this->cse) => ['primary' => 'cse primary'],
    ];

    expect(resolver()->resolve('menu', 'primary', ctx($this->cse)))->toBe('cse primary')
        ->and(resolver()->resolve('menu', 'footer', ctx($this->cse)))->toBe('main footer')
        ->and(resolver()->resolve('menu', 'sidebar', ctx($this->cse)))->toBe('theme default sidebar')
        ->and(resolver()->source('menu', 'sidebar', ctx($this->cse)))->toBeNull();
});

it('follows the microsite fallback mode: own, inherit or main (TC-MS-04)', function () {
    $this->menus = [
        'site:0' => ['primary' => 'main primary'],
        ownerKey($this->engineering) => ['primary' => 'engineering primary'],
    ];
    $setting = MicrositeSetting::create(['owner_type' => 'test.child', 'owner_id' => $this->cse->id, 'fallback_modes' => ['menu' => 'main']]);

    expect(resolver()->resolve('menu', 'primary', ctx($this->cse)))->toBe('main primary');   // skips the faculty

    $setting->update(['fallback_modes' => ['menu' => 'own']]);
    resolver()->flush();
    expect(resolver()->resolve('menu', 'primary', ctx($this->cse)))->toBe('theme default primary');

    expect(fn () => $setting->update(['fallback_modes' => ['menu' => 'parent']]))->toThrow(InvalidArgumentException::class);
});

it('the microsite template is inherited the same way', function () {
    MicrositeSetting::create(['owner_type' => 'test.parent', 'owner_id' => $this->engineering->id, 'template' => 'faculty-hero']);

    expect(resolver()->resolve('template', null, ctx($this->cse)))->toBe('faculty-hero')
        ->and(resolver()->resolve('template', null, ctx()))->toBeNull();   // main site: theme setting
});

it('moving a department to another faculty changes what it inherits', function () {
    $business = faculty('Business');
    $this->menus = [
        ownerKey($this->engineering) => ['primary' => 'engineering primary'],
        ownerKey($business) => ['primary' => 'business primary'],
    ];

    expect(resolver()->resolve('menu', 'primary', ctx($this->eee)))->toBe('engineering primary');

    $this->eee->update(['parent_id' => $business->id]);
    resolver()->flush();

    expect(resolver()->resolve('menu', 'primary', ctx($this->eee->fresh())))->toBe('business primary');
});
