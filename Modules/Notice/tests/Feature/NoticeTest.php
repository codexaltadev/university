<?php

/*
| Phase 3.15 — M-20 Notice (DB-05 §5 v1.4, SRS Content FR-NOT-01..07): files with their own
| titles (AC-NOT-01, kernel AttachmentList / media use meta), pinned on top (AC-NOT-02), off the
| board after expiry but in the archive (FR-NOT-03), filters and search by notice number, places
| and the main site, "New" badge, board and ticker sections, PDF preview.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Notice\Database\Seeders\NoticeDatabaseSeeder;
use Modules\Notice\Filament\Resources\Notices\Pages\CreateNotice;
use Modules\Notice\Filament\Resources\Notices\Pages\EditNotice;
use Modules\Notice\Models\Notice;
use Modules\Notice\Sections\NoticeBoardSection;
use Modules\Notice\Sections\NoticeTickerSection;
use Platform\Homepage\SectionContext;
use Platform\Media\MediaLibrary;
use Platform\Modules\ModulePermissions;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Carbon::setTestNow('2026-09-10 10:00:00');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Notice'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    app(ModulePermissions::class)->syncAttachable('notice', [Notice::class]);
    (new NoticeDatabaseSeeder)->run();
    $this->exam = (int) array_search('Examination', app(Vocabularies::class)->options(Notice::CATEGORY), true);
    $this->engine = app(RelationEngine::class);
    $this->cse = Department::factory()->create(['faculty_id' => Faculty::factory()->create()->id, 'short_name' => 'CSE']);

    $main = fn (array $a) => tap(Notice::factory()->create($a), fn (Notice $n) => $this->engine->sync($n, [], mainSite: true, user: null));
    $this->holiday = $main(['title' => ['en' => 'Holiday on 1 May'], 'active_at' => '2026-04-20 09:00', 'is_pinned' => true]);
    $this->routine = $main(['title' => ['en' => 'Final exam routine Fall 2026'], 'reference_no' => 'DIU/Exam/2026/115', 'active_at' => '2026-09-08 09:00', 'is_urgent' => true]);
    $this->routine->syncTerms(Notice::CATEGORY, [$this->exam]);
    $this->old = $main(['title' => ['en' => 'Admission test result'], 'active_at' => '2026-07-01 09:00', 'expires_at' => '2026-08-01 00:00']);
    $this->cseOnly = Notice::factory()->create(['title' => ['en' => 'CSE lab closed on Friday'], 'active_at' => '2026-09-09 09:00']);
    $this->engine->sync($this->cseOnly, ['department.department:'.$this->cse->id], mainSite: false, user: null);
});

afterEach(fn () => Carbon::setTestNow());

it('keeps a pinned notice on top, and an expired one in the archive only (AC-NOT-02, FR-NOT-03)', function () {
    $this->get('/notices')->assertOk()
        ->assertSeeInOrder(['Holiday on 1 May', 'Final exam routine Fall 2026'])                      // pinned first, though older
        ->assertDontSee('Admission test result')->assertDontSee('CSE lab closed on Friday');
    $this->get('/notices?archive=1')->assertOk()->assertSee('Admission test result')->assertDontSee('Holiday on 1 May');
    $this->get($this->old->url())->assertOk()->assertSee('Archived');                                // its page stays

    $board = (new NoticeBoardSection)->items(new SectionContext('site:0', ['count' => 5], 'en'));
    expect($board->pluck('title')->all())->toBe(['Holiday on 1 May', 'Final exam routine Fall 2026']);
    $ticker = (new NoticeTickerSection)->items(new SectionContext('site:0', ['count' => 5, 'important_only' => true], 'en'));
    expect($ticker->pluck('title')->all())->toBe(['Final exam routine Fall 2026', 'Holiday on 1 May']);   // urgent first
});

it('searches by title and notice number and filters by category, entity and dates (FR-NOT-04)', function () {
    $this->get('/notices?q=2026/115')->assertOk()->assertSee('Final exam routine Fall 2026')->assertDontSee('Holiday on 1 May');
    $this->get('/notices?q=holiday')->assertOk()->assertSee('Holiday on 1 May');
    $this->get('/notices?category=examination')->assertOk()->assertSee('Final exam routine Fall 2026')->assertDontSee('Holiday on 1 May');
    $this->get('/notices?from=2026-09-01&to=2026-09-30')->assertOk()->assertSee('Final exam routine Fall 2026')->assertDontSee('Holiday on 1 May');

    $this->engine->sync($this->routine, ['department.department:'.$this->cse->id], mainSite: true, user: null);
    $this->get('/notices?entity=department.department:'.$this->cse->id)->assertOk()->assertSee('Final exam routine Fall 2026')->assertDontSee('Holiday on 1 May');
});

it('shows an entity\'s notice only on its microsite, with the New badge for a week (FR-NOT-07)', function () {
    $base = rtrim($this->cse->url(), '/');
    $this->get($base.'/notices')->assertOk()->assertSee('CSE lab closed on Friday')->assertSee('New')->assertDontSee('Holiday on 1 May');
    $this->get('/notices/'.$this->cseOnly->slug())->assertNotFound();

    Carbon::setTestNow('2026-09-20 10:00:00');
    expect($this->cseOnly->fresh()->isNew())->toBeFalse();
    settings()->save('notice', ['new_days' => 15]);
    expect($this->cseOnly->fresh()->isNew())->toBeTrue();
});

it('saves three files with their own titles and lists them on the notice (FR-NOT-01/05, AC-NOT-01)', function () {
    $library = app(MediaLibrary::class);
    $files = collect(['routine.pdf', 'seat-plan.pdf', 'rules.pdf'])->map(fn (string $name) => $library->store(UploadedFile::fake()->createWithContent($name, '%PDF-1.4 test')));

    $this->actingAs(superAdmin());
    Livewire::test(CreateNotice::class)
        ->fillForm([
            'title' => ['en' => 'Mid-term notice'], 'show_in' => [], 'main_site' => true,
            'attachments' => [
                ['media' => $files[0]->id, 'title' => ['en' => 'Exam routine']],
                ['media' => $files[1]->id, 'title' => ['en' => 'Seat plan']],
                ['media' => $files[2]->id, 'title' => ['en' => 'Exam rules']],
            ],
        ])
        ->call('create')->assertHasNoFormErrors();

    $notice = Notice::query()->get()->first(fn (Notice $n): bool => $n->title === 'Mid-term notice');
    expect($notice->mediaUsagesIn('attachments')->map(fn ($u) => $u->meta['title']['en'])->all())->toBe(['Exam routine', 'Seat plan', 'Exam rules']);

    $this->get($notice->url())->assertOk()
        ->assertSeeInOrder(['Exam routine', 'Download', 'Seat plan', 'Download', 'Exam rules', 'Download'])
        ->assertSee('Preview');                                                                       // PDFs can be previewed

    $state = Livewire::test(EditNotice::class, ['record' => $notice->id])->get('data.attachments');   // repeater rows are keyed by uuid
    expect(collect($state)->map(fn (array $row): array => [(int) $row['media'], $row['title']['en'] ?? null])->values()->all())
        ->toBe([[$files[0]->id, 'Exam routine'], [$files[1]->id, 'Seat plan'], [$files[2]->id, 'Exam rules']]);
});

it('marks urgent notices and keeps expiry after the start', function () {
    $this->get('/notices')->assertOk()->assertSee('Urgent');
    expect(fn () => Notice::factory()->create(['active_at' => '2026-09-10', 'expires_at' => '2026-09-09']))->toThrow(ValidationException::class);
});
