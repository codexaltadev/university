<?php

/*
| Phase 3.3b — Program sub-items: majors (with category), career paths and
| industries, facilitators, learning materials, memberships, variants; edited
| as lists inside the Program editor by whoever may update the Program
| (BRL-PROG-04, AC-PROG-03, UI/UX Part A §5.2 v1.2); shown on the program page with
| section intros; counted by the program.majors and program.industry-experts
| metrics (FR-PROG-04..09, 11, 13).
*/

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Database\Seeders\ProgramDatabaseSeeder;
use Modules\Program\Filament\Resources\Programs\Pages\EditProgram;
use Modules\Program\Filament\Resources\Programs\Pages\ViewProgram;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramFacilitator;
use Modules\Program\Models\ProgramListItem;
use Modules\Program\Models\ProgramMajor;
use Modules\Program\Models\ProgramMaterial;
use Modules\Program\Models\ProgramMembership;
use Modules\Program\Models\ProgramVariant;
use Platform\Modules\ModulePermissions;
use Platform\Statistics\StatContext;
use Platform\Statistics\Statistics;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Program'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    (new ProgramDatabaseSeeder)->run();

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'CSE']);
    $this->bsc = Program::factory()->create(['department_id' => $this->cse->id, 'short_name' => 'BSc CSE', 'name' => ['en' => 'BSc in CSE']]);
    $this->msc = Program::factory()->create(['department_id' => $this->cse->id, 'short_name' => 'MSc CSE', 'name' => ['en' => 'MSc in CSE']]);

    $this->editor = fn () => Livewire::test(EditProgram::class, ['record' => $this->bsc->getRouteKey()]);
});

it('adds majors with a category, and career paths and industries to their own lists (FR-PROG-04/05)', function () {
    $this->actingAs(superAdmin());
    Vocabularies::ensureTerms('program.major-category', [['name' => ['en' => 'Concentration']]]);
    $concentration = array_key_first(app(Vocabularies::class)->options('program.major-category'));

    ($this->editor)()
        ->fillForm([
            'majors' => [['title' => ['en' => 'Cyber Security', 'bn' => 'সাইবার নিরাপত্তা'], 'category_term_id' => $concentration, 'is_active' => true]],
            'career_paths' => [['title' => ['en' => 'Software Engineer'], 'is_active' => true]],
            'industries' => [['title' => ['en' => 'Banking'], 'is_active' => true]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $major = ProgramMajor::query()->sole();
    expect($major->program_id)->toBe($this->bsc->id)
        ->and($major->category->getTranslation('name', 'en'))->toBe('Concentration')
        ->and($major->getTranslation('title', 'bn'))->toBe('সাইবার নিরাপত্তা');

    expect(ProgramListItem::query()->get()->mapWithKeys(fn (ProgramListItem $i): array => [$i->getTranslation('title', 'en') => $i->list_type])->all())
        ->toBe(['Software Engineer' => 'career_path', 'Banking' => 'industry']);

    // Each list shows only its own items; saving again keeps both
    $form = ($this->editor)();
    expect(collect($form->get('data.career_paths'))->pluck('title.en')->all())->toBe(['Software Engineer'])
        ->and(collect($form->get('data.industries'))->pluck('title.en')->all())->toBe(['Banking']);
    $form->call('save')->assertHasNoFormErrors();
    expect(ProgramListItem::query()->count())->toBe(2);
});

it('links a variant to a related program; the button falls back to that program (FR-PROG-09)', function () {
    $this->actingAs(superAdmin());

    ($this->editor)()
        ->fillForm(['variants' => [['title' => ['en' => 'Evening option'], 'short_description' => ['en' => 'Classes after office hours.'], 'related_program_id' => $this->msc->id, 'is_active' => true]]])
        ->call('save')
        ->assertHasNoFormErrors();

    $variant = ProgramVariant::query()->sole();
    expect($variant->buttonHref())->toBe($this->msc->url());

    $variant->update(['button_url' => 'https://example.edu/evening']);
    expect($variant->fresh()->buttonHref())->toBe('https://example.edu/evening');
});

it('lets whoever may update the program edit its sub-items; a viewer reads them (BRL-PROG-04, AC-PROG-03)', function () {
    $programUser = makeUser(['ViewAny:Program', 'View:Program', 'Update:Program'], [$this->bsc]);
    $this->actingAs($programUser);
    $this->get(ProgramResource::getUrl('edit', ['record' => $this->bsc]))->assertOk()->assertSee('Majors');

    ($this->editor)()
        ->fillForm(['majors' => [['title' => ['en' => 'Data Science'], 'is_active' => true]]])
        ->call('save')
        ->assertHasNoFormErrors();
    expect(ProgramMajor::query()->where('program_id', $this->bsc->id)->count())->toBe(1);

    // Another program of the same department is out of this user's scope
    $this->get(ProgramResource::getUrl('edit', ['record' => $this->msc]))->assertNotFound();

    // View only: the same parts, read only
    $viewer = makeUser(['ViewAny:Program', 'View:Program'], [$this->bsc]);
    $this->actingAs($viewer);
    $this->get(ProgramResource::getUrl('edit', ['record' => $this->bsc]))->assertForbidden();
    $this->get(ProgramResource::getUrl('view', ['record' => $this->bsc]))->assertOk()->assertSee('Data Science');
    Livewire::test(ViewProgram::class, ['record' => $this->bsc->getRouteKey()])->assertFormFieldIsDisabled('majors');
});

it('shows the sub-items on the program page with their intros, active ones only (FR-PROG-13)', function () {
    $this->bsc->update(['section_intros' => ['en' => ['majors' => 'Pick your focus in year three.', 'facilitators' => 'Learn from people who build things.']]]);

    Vocabularies::ensureTerms('program.major-category', [['name' => ['en' => 'Track']]]);
    $track = array_key_first(app(Vocabularies::class)->options('program.major-category'));
    ProgramMajor::query()->create(['program_id' => $this->bsc->id, 'title' => ['en' => 'Robotics'], 'short_description' => ['en' => 'Embedded systems.'], 'category_term_id' => $track]);
    ProgramMajor::query()->create(['program_id' => $this->bsc->id, 'title' => ['en' => 'Retired Major'], 'is_active' => false]);
    ProgramListItem::query()->create(['program_id' => $this->bsc->id, 'list_type' => 'career_path', 'title' => ['en' => 'Data Analyst']]);
    ProgramListItem::query()->create(['program_id' => $this->bsc->id, 'list_type' => 'industry', 'title' => ['en' => 'Telecom']]);
    ProgramFacilitator::query()->create(['program_id' => $this->bsc->id, 'name' => 'Ayesha Rahman', 'designation' => ['en' => 'CTO'], 'organization' => ['en' => 'Example Ltd'], 'linkedin_url' => 'https://linkedin.com/in/ayesha']);
    ProgramMaterial::query()->create(['program_id' => $this->bsc->id, 'title' => ['en' => 'Course handbook'], 'url' => 'https://example.edu/handbook.pdf']);
    ProgramMaterial::query()->create(['program_id' => $this->bsc->id, 'title' => ['en' => 'Nothing to open']]);
    ProgramMembership::query()->create(['program_id' => $this->bsc->id, 'title' => ['en' => 'IEB Accredited']]);
    ProgramVariant::query()->create(['program_id' => $this->bsc->id, 'title' => ['en' => 'Dual Degree (DIU-BCU)'], 'short_description' => ['en' => 'Two years here, two in the UK.'], 'related_program_id' => $this->msc->id, 'button_text' => ['en' => 'See the dual degree']]);

    // Through the default theme (DIU's program page: a block per major category, majors' text on hover)
    $this->get($this->bsc->url())->assertOk()
        ->assertSeeInOrder(['href="#majors-1"', 'Explore Your Track', 'Pick your focus in year three.', 'Robotics', 'Embedded systems.'], false)
        ->assertDontSee('Retired Major')
        ->assertSee('Dual Degree (DIU-BCU)')->assertSee($this->msc->url(), false)
        ->assertSee('Data Analyst')->assertSee('Telecom')
        ->assertSee('Learn from people who build things.')
        ->assertSee('Ayesha Rahman')->assertSee('CTO')
        ->assertSee('https://example.edu/handbook.pdf', false)->assertDontSee('Nothing to open')
        ->assertSee('IEB Accredited');

    // The module's own page, for themes without their own
    $html = moduleView('Program', 'microsite.home', ['entity' => $this->bsc->fresh()]);
    expect($html)->toContain('Majors &amp; Specializations', 'Track', 'Robotics', 'See the dual degree', 'Example Ltd', 'https://linkedin.com/in/ayesha', 'Learning Materials')
        ->not->toContain('Retired Major', 'Nothing to open');

    // A program without sub-items shows none of these parts
    $this->get($this->msc->url())->assertOk()->assertDontSee('Explore Your')->assertDontSee('Downloadable Materials');
    expect(moduleView('Program', 'microsite.home', ['entity' => $this->msc]))->not->toContain('Majors &amp; Specializations', 'Learning Materials');
});

it('saves section intros from the program form', function () {
    $this->actingAs(superAdmin());

    Livewire::test(EditProgram::class, ['record' => $this->bsc->getRouteKey()])
        ->fillForm(['section_intros' => ['en' => ['majors' => 'Choose a major.', 'industry' => 'Where our graduates work.']]])
        ->call('save')
        ->assertHasNoFormErrors();

    $program = $this->bsc->fresh();
    expect($program->intro('majors', 'en'))->toBe('Choose a major.')
        ->and($program->intro('industry', 'en'))->toBe('Where our graduates work.')
        ->and($program->intro('variants', 'en'))->toBeNull();

    Livewire::test(EditProgram::class, ['record' => $this->bsc->getRouteKey()])
        ->assertFormSet(['section_intros.en.majors' => 'Choose a major.']);
});

it('counts majors and industry experts for the program, its Department and Faculty (FR-PROG-11)', function () {
    foreach (['AI', 'Cyber', 'Data'] as $title) {
        ProgramMajor::query()->create(['program_id' => $this->bsc->id, 'title' => ['en' => $title]]);
    }
    ProgramMajor::query()->create(['program_id' => $this->msc->id, 'title' => ['en' => 'Cloud']]);
    ProgramMajor::query()->create(['program_id' => $this->bsc->id, 'title' => ['en' => 'Off'], 'is_active' => false]);
    ProgramFacilitator::query()->create(['program_id' => $this->bsc->id, 'name' => 'Expert One']);
    ProgramFacilitator::query()->create(['program_id' => $this->bsc->id, 'name' => 'Expert Two']);

    $display = fn (Model $owner, string $label) => collect(app(Statistics::class)->forOwner($owner->getMorphClass().':'.$owner->id))->firstWhere('label', $label)['display'] ?? null;

    expect($display($this->bsc, 'Majors'))->toBe('3')
        ->and($display($this->bsc, 'Industry Experts'))->toBe('2')
        ->and($display($this->msc, 'Majors'))->toBe('1')
        ->and(app(Statistics::class)->metric('program.majors', StatContext::of($this->cse->getMorphClass().':'.$this->cse->id)))->toBe(4);

    // A hidden program's majors are not counted
    $this->msc->update(['is_active' => false]);
    expect(app(Statistics::class)->metric('program.majors', StatContext::site()))->toBe(3);
});

it('removes the sub-items with a force-deleted program (DB-03 §4.2 cascade)', function () {
    ProgramMajor::query()->create(['program_id' => $this->msc->id, 'title' => ['en' => 'Cloud']]);
    ProgramVariant::query()->create(['program_id' => $this->bsc->id, 'title' => ['en' => 'Link'], 'short_description' => ['en' => 'x'], 'related_program_id' => $this->msc->id]);

    $this->msc->forceDelete();

    expect(ProgramMajor::withTrashed()->count())->toBe(0)
        ->and(ProgramVariant::query()->sole()->related_program_id)->toBeNull();   // set null
});
