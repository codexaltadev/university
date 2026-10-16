<?php

/*
| Phase 3.20 — M-27 Documents (DB-05 §12 v1.9, SRS Content FR-DOC-01..08): files from the Media
| Library or an outside address; type with its own fields (AC-DOC-01); /downloads by type,
| category, year, search; versions (FR-DOC-05); counted downloads (FR-DOC-06); private ones
| (FR-DOC-07); the own flipbook (FR-DOC-03) with PDF.js shipped by the module (kernel module
| assets); homepage sections and the page block (FR-DOC-08).
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Documents\Blocks\DocumentBlock;
use Modules\Documents\Filament\Resources\Documents\Pages\CreateDocument;
use Modules\Documents\Filament\Resources\Documents\Pages\EditDocument;
use Modules\Documents\Models\Document;
use Modules\Documents\Sections\DownloadsSection;
use Modules\Documents\Sections\ProspectusSection;
use Modules\Faculty\Models\Faculty;
use Platform\Homepage\SectionContext;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
use Platform\Modules\Manifest;
use Platform\Modules\ModuleAssets;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Carbon::setTestNow('2026-10-16 10:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Documents');
    app(ModulePermissions::class)->syncAttachable('documents', [Document::class]);
    $this->engine = app(RelationEngine::class);
    $this->pdf = fn (string $name): MediaItem => app(MediaLibrary::class)->store(UploadedFile::fake()->createWithContent($name, '%PDF-1.4 test'));

    $this->make = function (string $title, string $type = 'other', array $attributes = [], array $places = [], bool $main = true, ?MediaItem $file = null): Document {
        $d = Document::factory()->create(['title' => ['en' => $title], 'document_type' => $type, ...$attributes]);
        $file && $d->syncMedia('file', [$file->id]);
        $this->engine->sync($d, $places, mainSite: $main, user: null);

        return $d;
    };
});

afterEach(fn () => Carbon::setTestNow());

it('lists the main site\'s current documents by type, category, year and title (FR-DOC-04)', function () {
    Vocabularies::ensureTerms(Document::CATEGORY, [['name' => ['en' => 'Admission'], 'slug' => 'admission']]);
    $term = (int) array_search('Admission', app(Vocabularies::class)->options(Document::CATEGORY), true);
    $form = ($this->make)('Admission Form', 'form', ['active_at' => '2026-01-10'], file: ($this->pdf)('form.pdf'));
    $form->syncTerms(Document::CATEGORY, [$term]);
    ($this->make)('Code of Conduct', 'policy', ['active_at' => '2025-06-01', 'version_label' => 'v3'], file: ($this->pdf)('conduct.pdf'));
    ($this->make)('Annual Report 2024', 'report', ['active_at' => '2025-02-01', 'external_url' => 'https://drive.google.com/file/d/abc/view']);
    ($this->make)('Hidden', 'form', ['is_active' => false]);
    ($this->make)('Board secrets', 'report', ['is_private' => true]);

    $this->get('/downloads')->assertOk()->assertSeeInOrder(['Admission Form', 'Code of Conduct', 'Annual Report 2024'])
        ->assertSee('PDF · 1 KB')->assertSee('drive.google.com')
        ->assertDontSee('Hidden')->assertDontSee('Board secrets');
    $this->get('/downloads?type=policy')->assertOk()->assertSee('Code of Conduct')->assertDontSee('Admission Form');
    $this->get('/downloads?category=admission')->assertOk()->assertSee('Admission Form')->assertDontSee('Code of Conduct');
    $this->get('/downloads?year=2025')->assertOk()->assertSee('Annual Report 2024')->assertDontSee('Admission Form');
    $this->get('/downloads?q=conduct')->assertOk()->assertSee('Code of Conduct')->assertDontSee('Annual Report');
    foreach (['cards', 'list'] as $view) {
        $this->get('/downloads?view='.$view)->assertOk()->assertSee('document-item--'.$view, false);
    }

    $cse = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id, 'short_name' => 'CSE']);
    ($this->make)('CSE Lab Manual', 'guidebook', places: ['department.department:'.$cse->id], main: false);
    $this->get(rtrim($cse->url(), '/').'/downloads')->assertOk()->assertSee('CSE Lab Manual')->assertDontSee('Admission Form');
});

it('counts a download and sends the file or the outside address; read-only has no download (FR-DOC-06)', function () {
    $form = ($this->make)('Admission Form', 'form', file: $file = ($this->pdf)('form.pdf'));
    $this->get($form->url().'/file')->assertRedirect($file->url());
    $this->get($form->url().'/file')->assertRedirect();
    expect($form->fresh()->download_count)->toBe(2)
        ->and($form->fresh()->updated_at->equalTo($form->updated_at))->toBeTrue();                       // a download is not an edit

    $report = ($this->make)('Annual Report', 'report', ['external_url' => 'https://example.org/report.pdf']);
    $this->get($report->url().'/file')->assertRedirect('https://example.org/report.pdf');

    $readOnly = ($this->make)('Prospectus 2026', 'prospectus', ['allow_download' => false, 'show_as_flipbook' => true], file: ($this->pdf)('p.pdf'));
    $this->get($readOnly->url())->assertOk()->assertDontSee($readOnly->url().'/file');
    $this->get($readOnly->url().'/file')->assertNotFound();
});

it('opens a PDF as the own flipbook, with PDF.js shipped by the module (FR-DOC-03)', function () {
    app(ModuleAssets::class)->publish(Manifest::fromDirectory(base_path('Modules/Documents')));
    expect(public_path('modules/documents/flipbook.js'))->toBeFile()
        ->and(public_path('modules/documents/pdfjs/pdf.min.mjs'))->toBeFile()
        ->and(public_path('modules/documents/pdfjs/pdf.worker.min.mjs'))->toBeFile();

    $book = ($this->make)('Prospectus Fall 2026', 'prospectus', ['show_as_flipbook' => true, 'session_label' => 'Fall 2026'], file: $file = ($this->pdf)('prospectus.pdf'));
    $this->get($book->url())->assertOk()
        ->assertSee('x-data="flipbook(', false)
        ->assertSee('/modules/documents/flipbook.js?v=', false)
        ->assertSee('modules\/documents\/pdfjs\/pdf.min.mjs', false)
        ->assertSee(str_replace('/', '\/', $file->url()), false)
        ->assertSee('Fall 2026');

    $plain = ($this->make)('Plain PDF', 'form', file: ($this->pdf)('plain.pdf'));
    $this->get($plain->url())->assertOk()->assertDontSee('x-data="flipbook(', false)->assertSee('<iframe', false);
});

it('keeps older versions reachable, pointing to the new one, and out of the lists (FR-DOC-05)', function () {
    $v1 = ($this->make)('Code of Conduct', 'policy', ['version_label' => 'v1', 'approved_on' => '2024-01-01'], file: ($this->pdf)('v1.pdf'));
    $this->actingAs(superAdmin());
    Livewire::test(EditDocument::class, ['record' => $v1->id])
        ->callAction('newVersion', data: ['file' => ($this->pdf)('v2.pdf')->id, 'version_label' => 'v2', 'approved_on' => '2026-09-01']);
    auth()->logout();

    $v2 = Document::query()->whereNull('superseded_by_id')->sole();
    expect($v2->version_label)->toBe('v2')->and($v1->fresh()->superseded_by_id)->toBe($v2->id)
        ->and($v2->isOnMainSite())->toBeTrue();

    $this->get('/downloads')->assertOk()->assertSee('v2')->assertDontSee('(v1)');
    $this->get($v1->url())->assertOk()->assertSee('There is a newer version')->assertSee($v2->url());
    $this->get($v2->url())->assertOk()->assertSee('Older versions')->assertSee('v1');

    expect(fn () => $v2->update(['superseded_by_id' => $v2->id]))->toThrow(ValidationException::class);
});

it('shows private documents only to signed-in users, the file through a signed link (FR-DOC-07)', function () {
    $secret = ($this->make)('Board minutes', 'report', ['is_private' => true], file: ($this->pdf)('minutes.pdf'));
    $this->get($secret->url())->assertNotFound();
    $this->get($secret->url().'/file')->assertNotFound();

    $this->actingAs(superAdmin());
    $this->get($secret->url())->assertOk()->assertSee('Board minutes')->assertSee('signature=', false)->assertSee('noindex', false);
    expect($this->get($secret->url().'/file')->headers->get('Location'))->toContain('signature=');
});

it('shows the type\'s own fields in the form and saves a document (AC-DOC-01)', function () {
    $this->actingAs(superAdmin());
    Livewire::test(CreateDocument::class)
        ->fillForm(['document_type' => 'policy'])
        ->assertFormFieldIsVisible('version_label')->assertFormFieldIsVisible('approved_on')->assertFormFieldIsHidden('session_label')
        ->fillForm(['document_type' => 'prospectus'])
        ->assertFormFieldIsVisible('session_label')->assertFormFieldIsHidden('version_label')
        ->fillForm(['title' => ['en' => 'Prospectus 2027'], 'session_label' => 'Spring 2027', 'file' => ($this->pdf)('x.pdf')->id, 'show_in' => [], 'main_site' => true])
        ->call('create')->assertHasNoFormErrors();

    $doc = Document::query()->sole();
    expect($doc->session_label)->toBe('Spring 2027')->and($doc->isPdf())->toBeTrue()->and(MediaItem::query()->count())->toBe(1);
    expect(fn () => Document::factory()->create(['document_type' => 'novel']))->toThrow(ValidationException::class);
});

it('fills the Downloads and Prospectus sections and the page block (FR-DOC-08)', function () {
    $form = ($this->make)('Admission Form', 'form', ['active_at' => '2026-01-01']);
    $book = ($this->make)('Prospectus 2026', 'prospectus', ['active_at' => '2026-02-01', 'show_as_flipbook' => true], file: ($this->pdf)('p.pdf'));
    ($this->make)('Private one', 'form', ['is_private' => true]);

    expect((new DownloadsSection)->items(new SectionContext('site:0', ['count' => 5], 'en'))->pluck('title')->all())->toBe(['Prospectus 2026', 'Admission Form'])
        ->and((new DownloadsSection)->items(new SectionContext('site:0', ['types' => ['form'], 'count' => 5], 'en'))->pluck('title')->all())->toBe(['Admission Form'])
        ->and((new ProspectusSection)->items(new SectionContext('site:0', [], 'en'))->pluck('title')->all())->toBe(['Prospectus 2026']);

    $html = view('documents::blocks.documents', ['data' => ['documents' => [$book->id], 'display' => 'flipbook'], 'locale' => 'en'])->render();
    expect($html)->toContain('x-data="flipbook(');
    expect(DocumentBlock::documents(['documents' => [$form->id, $book->id]])->pluck('id')->all())->toBe([$form->id, $book->id]);
});
