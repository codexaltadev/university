<?php

/*
| Phase 2.7a — Pages (C-11): owner, nesting, address, blocks, files, duplicate,
| scope (FR-PAGE-01..05/08, AC-PAGE-01).
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Page\Filament\Resources\Pages\Pages\CreatePage;
use Modules\Page\Filament\Resources\Pages\Pages\EditPage;
use Modules\Page\Filament\Resources\Pages\Pages\ListPages;
use Modules\Page\Filament\Resources\Pages\Schemas\PageForm;
use Modules\Page\Models\Page;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Media\MediaLibrary;
use Platform\Modules\ModulePermissions;
use Platform\Scope\UserScopeFilter;

beforeEach(function () {
    Storage::fake('public');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Page');
    app(ModulePermissions::class)->sync('Media');

    $this->engineering = faculty();
    $this->cse = department($this->engineering, 'CSE');
    $this->eee = department($this->engineering, 'EEE');
    $this->cseKey = $this->cse->getMorphClass().':'.$this->cse->id;
});

const PAGE_EDITOR = ['ViewAny:Page', 'View:Page', 'Create:Page', 'Update:Page', 'Delete:Page', 'Replicate:Page', 'ViewAny:MediaItem', 'View:MediaItem'];

function blocks(array ...$blocks): array
{
    return collect($blocks)->mapWithKeys(fn (array $b, int $i): array => ["b{$i}" => $b])->all();
}

it('builds main-site pages with blocks and nesting (FR-PAGE-01/03)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreatePage::class)
        ->assertFormSet(['owner_key' => 'site:0', 'template' => 'default'])
        ->fillForm([
            'title' => ['en' => 'About Us', 'bn' => 'আমাদের সম্পর্কে'],
            'content' => ['en' => blocks(['type' => 'text', 'data' => ['body' => '<p>Founded in 2002.</p>']])],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $about = Page::first();
    expect($about->isMainSiteOwned())->toBeTrue()
        ->and($about->url('en'))->toBe('/about-us')
        ->and($about->getTranslation('content', 'en')[0]['type'] ?? array_values($about->getTranslation('content', 'en'))[0]['type'])->toBe('text');

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => ['en' => 'History'], 'parent_id' => $about->id])
        ->call('create')
        ->assertHasNoFormErrors();

    $history = Page::where('parent_id', $about->id)->first();
    expect($history->url('en'))->toBe('/about-us/history')
        ->and(PageForm::parentOptions('site:0', $about))->not->toHaveKey($history->id)       // never under itself / its children
        ->and(fn () => $about->delete())->toThrow(ParentHasActiveChildren::class);
});

it('puts an entity user\'s page on their microsite (AC-PAGE-01, FR-PAGE-05)', function () {
    $this->actingAs(makeUser(PAGE_EDITOR, [$this->cse]));

    Livewire::test(CreatePage::class)
        ->assertFormSet(['owner_key' => $this->cseKey])                                     // own entity, no main site
        ->fillForm(['title' => ['en' => 'Lab Facilities'], 'owner_key' => 'site:0'])
        ->call('create')
        ->assertHasFormErrors(['owner_key']);

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => ['en' => 'Lab Facilities']])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::first()->url('en'))->toBe('/engineering/cse/lab-facilities');
});

it('lists only the pages of the user\'s entities', function () {
    $cse = Page::factory()->ownedBy($this->cse)->create(['title' => ['en' => 'CSE About']]);
    $eee = Page::factory()->ownedBy($this->eee)->create(['title' => ['en' => 'EEE About']]);
    $main = Page::factory()->create(['title' => ['en' => 'Main About']]);

    $this->actingAs(makeUser(PAGE_EDITOR, [$this->cse]));
    UserScopeFilter::enforce();

    try {
        Livewire::test(ListPages::class)
            ->assertCanSeeTableRecords([$cse])
            ->assertCanNotSeeTableRecords([$eee, $main]);
    } finally {
        UserScopeFilter::enforce(false);
    }

    $this->actingAs(superAdmin());
    Livewire::test(ListPages::class)
        ->filterTable('owner', $this->cseKey)
        ->assertCanSeeTableRecords([$cse])
        ->assertCanNotSeeTableRecords([$eee, $main]);
});

it('counts pictures in blocks, main picture and downloads as Media usage', function () {
    $this->actingAs(superAdmin());
    $library = app(MediaLibrary::class);
    $photo = $library->store(UploadedFile::fake()->image('lab.jpg'));
    $gallery = $library->store(UploadedFile::fake()->image('room.jpg'));
    $pdf = $library->store(UploadedFile::fake()->createWithContent('rules.pdf', "%PDF-1.4\n%%EOF"));

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => ['en' => 'Labs'],
            'content' => ['en' => blocks(
                ['type' => 'image', 'data' => ['image' => $photo->id, 'caption' => 'Main lab']],
                ['type' => 'gallery', 'data' => ['images' => [$gallery->id], 'columns' => 3]],
            )],
            'featured_image' => $photo->id,
            'attachments' => [$pdf->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::first();
    expect($page->mediaIdsIn('content'))->toEqualCanonicalizing([$photo->id, $gallery->id])
        ->and($page->firstMedia('featured_image')?->id)->toBe($photo->id)
        ->and($page->mediaIdsIn('attachments'))->toBe([$pdf->id])
        ->and($photo->refresh()->usage_count)->toBe(2);                                     // in a block and as main picture

    $page->update(['content' => ['en' => [['type' => 'text', 'data' => ['body' => '<p>Gone</p>']]]]]);
    expect($gallery->refresh()->usage_count)->toBe(0);
});

it('changes the web address, keeping the old one for redirects (FR-GEN-30/32)', function () {
    $this->actingAs(superAdmin());
    $page = Page::factory()->create(['title' => ['en' => 'Admission Info']]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertFormSet(['web_address' => 'admission-info'])
        ->fillForm(['web_address' => 'Admissions'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->slug('en'))->toBe('admissions')
        ->and(Page::findBySlug('admission-info', 'en')[1])->toBeFalse();                    // found, not current: redirect
});

it('duplicates a page as an inactive copy with the same files (FR-PAGE-08)', function () {
    $this->actingAs(superAdmin());
    $pdf = app(MediaLibrary::class)->store(UploadedFile::fake()->createWithContent('form.pdf', "%PDF-1.4\n%%EOF"));
    $page = Page::factory()->create(['title' => ['en' => 'Forms', 'bn' => 'ফর্ম']]);
    $page->syncMedia('attachments', [$pdf->id]);

    Livewire::test(ListPages::class)->callTableAction('replicate', $page);

    $copy = Page::whereKeyNot($page->id)->first();
    expect($copy->getTranslation('title', 'en'))->toBe('Forms (copy)')
        ->and($copy->getTranslation('title', 'bn'))->toBe('ফর্ম (copy)')
        ->and($copy->is_active)->toBeFalse()
        ->and($copy->mediaIdsIn('attachments'))->toBe([$pdf->id])
        ->and($copy->slug('en'))->toBe('forms-copy');
});
