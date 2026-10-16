<?php

/*
| Phase 3.12 — M-17 Alumni (DB-04 §8 v1.5, SRS People FR-ALUM-01..09): directory with filters
| (AC-ALUM-01), hidden contacts, profile with video, Pride section, Department / Program
| microsites, the visitor's submission waiting for approval (AC-ALUM-02), import / export,
| scope, person source, search and sitemap.
*/

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Alumni\Filament\Resources\Alumni\Pages\ListAlumni;
use Modules\Alumni\Livewire\SubmitForm;
use Modules\Alumni\Models\Alumnus;
use Modules\Alumni\Sections\PrideAlumniSection;
use Modules\Alumni\Support\AlumniSheet;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Models\Program;
use Platform\Filament\Components\PersonPicker;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Scope\UserScopeFilter;
use Platform\Seo\Sitemap;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Program', 'Alumni'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }

    $fsit = Faculty::factory()->create(['short_name' => 'FSIT']);
    $this->cse = Department::factory()->create(['faculty_id' => $fsit->id, 'short_name' => 'CSE', 'name' => ['en' => 'Computer Science and Engineering']]);
    $this->eee = Department::factory()->create(['faculty_id' => $fsit->id, 'short_name' => 'EEE', 'name' => ['en' => 'Electrical and Electronic Engineering']]);
    $this->bsc = Program::factory()->create(['department_id' => $this->cse->id, 'name' => ['en' => 'B.Sc. in CSE']]);

    $this->rahim = Alumnus::factory()->pride()->create(['name' => ['en' => 'Rahim Uddin'], 'program_id' => $this->bsc->id, 'passing_year' => 2015, 'batch' => '20',
        'current_designation' => ['en' => 'Senior Engineer'], 'organization' => ['en' => 'Google'], 'phone' => '+880 1700 111111', 'email' => 'rahim@example.com',
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'story' => ['en' => '<p>From Dhaka to Mountain View.</p>']]);
    $this->karim = Alumnus::factory()->create(['name' => ['en' => 'Karim Mia'], 'department_id' => $this->cse->id, 'passing_year' => 2016, 'job_type' => 'business',
        'organization' => ['en' => 'Karim Foods']]);
    $this->nila = Alumnus::factory()->create(['name' => ['en' => 'Nila Akter'], 'department_id' => $this->eee->id, 'passing_year' => 2015]);
});

it('fills the department from the program and keeps a student ID one person\'s (FR-ALUM-01, BRL-ALUM-01)', function () {
    expect($this->rahim->department_id)->toBe($this->cse->id);

    $this->rahim->update(['student_id' => '111-15-001']);
    expect(fn () => $this->karim->update(['student_id' => '111-15-001']))->toThrow(ValidationException::class)
        ->and(fn () => $this->karim->update(['job_type' => 'astronaut']))->toThrow(ValidationException::class);
});

it('filters the directory and never shows a phone unless switched on (FR-ALUM-02/06, AC-ALUM-01)', function () {
    $this->get('/alumni')->assertOk()->assertSeeInOrder(['Rahim Uddin', 'Pride'])->assertSee('Karim Mia')->assertSee('Nila Akter');

    $this->get('/alumni?department='.$this->cse->id.'&year=2015')->assertOk()
        ->assertSee('Rahim Uddin')->assertDontSee('Karim Mia')->assertDontSee('Nila Akter')
        ->assertDontSee('+880 1700 111111')->assertDontSee('rahim@example.com');
    $this->get('/alumni?job=business')->assertOk()->assertSee('Karim Mia')->assertDontSee('Rahim Uddin');
    $this->get('/alumni?q=google')->assertOk()->assertSee('Rahim Uddin')->assertDontSee('Karim Mia');

    $this->get($this->rahim->url())->assertOk()
        ->assertSee('Senior Engineer, Google')->assertSee('From Dhaka to Mountain View.')
        ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
        ->assertDontSee('+880 1700 111111')->assertSee('"alumniOf"', false);

    $this->rahim->update(['show_phone' => true]);
    $this->get($this->rahim->url())->assertOk()->assertSee('+880 1700 111111');
});

it('shows a department\'s and a program\'s own alumni on their microsites (FR-ALUM-05)', function () {
    $this->get(rtrim($this->cse->url(), '/').'/alumni')->assertOk()->assertSee('Rahim Uddin')->assertSee('Karim Mia')->assertDontSee('Nila Akter');
    $this->get(rtrim($this->bsc->url(), '/').'/alumni')->assertOk()->assertSee('Rahim Uddin')->assertDontSee('Karim Mia');
});

it('lists Pride alumni in the homepage section (FR-ALUM-04)', function () {
    $items = (new PrideAlumniSection)->items(new SectionContext('site:0', ['count' => 4], 'en'));
    expect($items->pluck('id')->all())->toBe([$this->rahim->id]);

    $html = view(PrideAlumniSection::view(), ['items' => $items, 'section' => null, 'settings' => ['display' => 'grid']])->render();
    expect($html)->toContain('Rahim Uddin')->toContain('All alumni');
});

it('keeps a visitor\'s submission off until the Alumni Office approves it (FR-ALUM-07, AC-ALUM-02)', function () {
    Livewire::test(SubmitForm::class)
        ->set('name', 'Sadia Islam')->set('email', 'sadia@example.com')->set('passing_year', '2019')->set('organization', 'Brac Bank')
        ->call('submit')->assertHasNoErrors()->assertSet('done', true);

    $sadia = Alumnus::query()->where('email', 'sadia@example.com')->sole();
    expect($sadia->is_active)->toBeFalse()->and($sadia->submission_source)->toBe('public_form')->and($sadia->show_email)->toBeFalse();
    $this->get('/alumni')->assertDontSee('Sadia Islam');
    $this->get('/alumni/'.$sadia->slug())->assertNotFound();

    Livewire::test(SubmitForm::class)->set('name', 'Bot')->set('email', 'bot@example.com')->set('website', 'spam.example')->call('submit');
    expect(Alumnus::query()->where('email', 'bot@example.com')->exists())->toBeFalse();   // honeypot

    $this->actingAs(superAdmin());
    Livewire::test(ListAlumni::class)->filterTable('new')->assertCanSeeTableRecords([$sadia])->assertCanNotSeeTableRecords([$this->rahim])
        ->callTableAction('approve', $sadia);
    expect($sadia->fresh()->is_active)->toBeTrue();
    $this->get('/alumni')->assertSee('Sadia Islam');
});

it('imports and exports with the same columns, checking first (FR-ALUM-09)', function () {
    $this->karim->update(['student_id' => '111-16-002']);
    $file = UploadedFile::fake()->createWithContent('alumni.csv', implode("\n", [
        'Student ID,Name,Department,Program,Batch,Passing year,Designation,Organization,Job type,Email,Phone,Pride,Active',
        '111-16-002,Karim Mia,CSE,,21,2016,CEO,Karim Foods,business,,,yes,yes',
        ',Tania Rahman,,B.Sc. in CSE,25,2020,Analyst,BRAC,job,tania@example.com,,,',
        ',Nobody,Mars Dept,,,,,,,,,,',
        ',Bad Year,,,,20x5,,,,,,,',
    ]));
    $sheet = app(AlumniSheet::class);
    $checked = $sheet->check($file->getRealPath(), 'csv');
    expect($checked['total'])->toBe(4)->and($checked['rows'])->toHaveCount(2)->and($checked['errors'])->toHaveCount(2);

    expect($sheet->save($checked['rows']))->toBe(['created' => 1, 'updated' => 1])
        ->and($this->karim->fresh()->is_pride)->toBeTrue()
        ->and(Alumnus::query()->where('email', 'tania@example.com')->sole()->department_id)->toBe($this->cse->id);

    $out = fopen('php://memory', 'w+');
    $sheet->export($out);
    rewind($out);
    expect(stream_get_contents($out))->toStartWith('"Student ID",Name,Department,Program')->toContain('111-16-002,"Karim Mia"');
});

it('lets a department user manage only their alumni, and offers alumni in the person picker', function () {
    $user = makeUser(['ViewAny:Alumnus', 'View:Alumnus', 'Update:Alumnus'], [$this->eee]);
    $this->actingAs($user);
    UserScopeFilter::enforce();
    try {
        Livewire::test(ListAlumni::class)->assertCanSeeTableRecords([$this->nila])->assertCanNotSeeTableRecords([$this->rahim, $this->karim]);
    } finally {
        UserScopeFilter::enforce(false);
    }

    expect(collect(PersonPicker::search('Rahim'))->keys()->all())->toContain(PersonPicker::keyOf($this->rahim))
        ->and($this->rahim->personPresenter()->designation())->toBe('Senior Engineer, Google')
        ->and($this->rahim->personPresenter()->phone())->toBeNull();
});

it('puts visible alumni in search and the sitemap', function () {
    $this->nila->update(['is_active' => false]);
    $sitemap = collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' ');
    expect($sitemap)->toContain($this->rahim->url())->not->toContain($this->nila->url())
        ->and($this->rahim->toSearchDocument('en'))->toMatchArray(['title' => 'Rahim Uddin']);
});
