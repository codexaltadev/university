<?php

/*
| Phase 2.7a — Pages (C-11): owner, nesting, address, blocks, files, duplicate,
| scope (FR-PAGE-01..05/08, AC-PAGE-01).
*/

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Page\Blocks\HtmlBlock;
use Modules\Page\Filament\Resources\Pages\Pages\CreatePage;
use Modules\Page\Filament\Resources\Pages\Pages\EditPage;
use Modules\Page\Filament\Resources\Pages\Pages\ListPages;
use Modules\Page\Filament\Resources\Pages\Schemas\PageForm;
use Modules\Page\Models\Page;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Filament\Components\HtmlEditor;
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

it('edits an HTML block visually or as source, with Media Library files inserted and counted (FR-PAGE-02)', function () {
    $this->actingAs(superAdmin());
    $library = app(MediaLibrary::class);
    $photo = $library->store(UploadedFile::fake()->image('campus.jpg'), ['alt_text' => ['en' => 'Campus']]);
    $pdf = $library->store(UploadedFile::fake()->createWithContent('rules.pdf', "%PDF-1.4\n%%EOF"));
    $secret = $library->store(UploadedFile::fake()->image('secret.jpg'), ['is_private' => true]);

    $html = HtmlEditor::mediaHtml([$pdf->id, $photo->id, $secret->id]);
    expect($html)->toStartWith('<p><a href="/media/'.$pdf->id.'/')                          // a file as a link, by its permalink
        ->toContain('<img src="/storage/')->toContain('alt="Campus" data-media-id="'.$photo->id.'"')
        ->not->toContain('secret');                                                           // private files never go into page text

    $form = Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => ['en' => 'Campus'],
            'content' => ['en' => blocks(['type' => 'html', 'data' => ['html' => '<div class="row"><strong>Hi</strong></div>']])],
        ])
        ->assertSeeHtml('htmlEditorFormComponent');

    $key = collect($form->instance()->form->getFlatComponents())->first(fn ($c) => $c instanceof HtmlEditor)->getKey();
    $form->callAction(TestAction::make('insertMedia')->schemaComponent(Str::after($key, 'form.')), ['items' => [$photo->id]])
        ->assertDispatched('html-editor-insert', key: $key, html: HtmlEditor::mediaHtml([$photo->id]));

    $form->fillForm(['content' => ['en' => blocks(['type' => 'html', 'data' => ['html' => '<div class="row">'.$html.'</div>']])]])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::first()->mediaIdsIn('content'))->toEqualCanonicalizing([$pdf->id, $photo->id])
        ->and((string) HtmlBlock::clean('<img src="/storage/1/a.jpg" data-media-id="5" onerror="x()">'))->toContain('data-media-id="5"')->not->toContain('onerror');
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

it('edits a key facts block in Admin: chapters of facts, each with its shape, saved as given (UI/UX Part B §5.8)', function () {
    $this->actingAs(superAdmin());
    $data = ['highlights' => [['value' => '2002', 'label' => 'Year of establishment']], 'show_nav' => true, 'note' => '<p>Note</p>', 'chapters' => [
        ['title' => 'Academics', 'rows' => [['label' => 'Faculties :', 'value' => '<ol><li>Faculty of Engineering</li></ol>', 'display' => 'cards', 'featured' => true]]],
    ]];
    $page = Page::factory()->create(['title' => ['en' => 'At a Glance'], 'content' => ['en' => [['type' => 'facts', 'data' => $data]]]]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertOk()->call('save')->assertHasNoFormErrors();

    $saved = $page->fresh()->getTranslation('content', 'en')[0];
    expect($saved['type'])->toBe('facts')
        ->and(array_values($saved['data']['chapters'])[0]['title'])->toBe('Academics')
        ->and(array_values(array_values($saved['data']['chapters'])[0]['rows'])[0])->toMatchArray(['label' => 'Faculties :', 'display' => 'cards', 'featured' => true]);
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

it('links to the page on the website while it is shown there', function () {
    $this->actingAs(superAdmin());
    $page = Page::factory()->ownedBy($this->cse)->create(['title' => ['en' => 'Lab Rules']]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertActionVisible('view_on_site')
        ->assertActionHasUrl('view_on_site', url('/engineering/cse/lab-rules'))
        ->assertActionShouldOpenUrlInNewTab('view_on_site');

    $page->update(['is_active' => false]);
    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])->assertActionHidden('view_on_site');
});
