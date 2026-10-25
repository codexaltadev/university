<?php

/*
| Phase 3.3a — Program (M-03) core: admin, Department required, level and tags,
| scope (AC-PROG-03), moving to another Department, website listing with filters
| and layouts (AC-PROG-01), microsite page with facts, eligibility and other
| modules' tabs (FR-PROG-18, AC-PROG-02), counters, homepage sections, search,
| sitemap. FR-PROG-01..03a/10..13/16..18, BRL-PROG-01/02.
*/

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Page\Blocks\PageBlocks;
use Modules\Program\Database\Factories\ProgramFactory;
use Modules\Program\Database\Seeders\ProgramDatabaseSeeder;
use Modules\Program\Filament\Resources\Programs\Pages\CreateProgram;
use Modules\Program\Filament\Resources\Programs\Pages\EditProgram;
use Modules\Program\Filament\Resources\Programs\ProgramResource;
use Modules\Program\Models\Program;
use Modules\Program\Sections\FeaturedProgramsSection;
use Modules\Program\Sections\ProgramsByLevelSection;
use Modules\Program\Settings\ProgramSettings;
use Modules\Program\Support\EligibilityPage;
use Platform\Events\EntityHierarchyChanged;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Registry\Registry;
use Platform\Search\SearchIndex;
use Platform\Statistics\Statistics;
use Platform\Statistics\StatSet;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Platform\Website\EntityTab;

class ProgramTestTuitionTab extends EntityTab
{
    public static function key(): string
    {
        return 'tuition.fee';
    }

    public static function label(): string
    {
        return 'Tuition Fees';
    }

    public static function view(): string
    {
        return 'program::partials.card';
    }

    public function visible(Model $entity): bool
    {
        return $entity->short_name !== 'NOFEE';
    }

    public function data(Model $entity): array
    {
        return ['program' => $entity, 'style' => 'list'];
    }
}

class ProgramTestBrokenTab extends ProgramTestTuitionTab
{
    public static function key(): string
    {
        return 'broken.tab';
    }

    public static function view(): string
    {
        return 'missing::view';
    }
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'Program'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    (new ProgramDatabaseSeeder)->run();

    $this->ug = ProgramFactory::level('Undergraduate');
    $this->grad = ProgramFactory::level('Graduate');

    $this->fsit = Faculty::factory()->create(['name' => ['en' => 'Faculty of Science and Information Technology'], 'short_name' => 'FSIT']);
    $this->fbe = Faculty::factory()->create(['name' => ['en' => 'Faculty of Business and Entrepreneurship'], 'short_name' => 'FBE']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'Department of Computer Science and Engineering'], 'short_name' => 'CSE']);
    $this->swe = Department::factory()->create(['faculty_id' => $this->fsit->id, 'name' => ['en' => 'Department of Software Engineering'], 'short_name' => 'SWE']);
    $this->bba = Department::factory()->create(['faculty_id' => $this->fbe->id, 'name' => ['en' => 'Department of Business Administration'], 'short_name' => 'BBA']);

    $this->bsc = Program::factory()->create([
        'department_id' => $this->cse->id,
        'level_term_id' => $this->ug,
        'name' => ['en' => 'BSc in Computer Science and Engineering', 'bn' => 'কম্পিউটার বিজ্ঞান ও প্রকৌশলে বিএসসি'],
        'short_name' => 'BSc CSE',
        'degree_name' => ['en' => 'Bachelor of Science'],
        'duration_label' => ['en' => '4 Years (12 Semesters)'],
        'total_credits' => 148,
        'summary' => ['en' => '<p>Build software and systems.</p>'],
        'objective' => ['en' => '<p>Graduates who solve real problems.</p>'],
        'eligibility_general' => ['en' => '<p>SSC and HSC GPA 2.5 each.</p>'],
        'eligibility_english_medium' => ['en' => '<p>Five O Level and two A Level subjects.</p>'],
        'application_url' => 'https://admission.example.edu/apply',
        'next_intake_on' => '2027-01-15',
    ]);
});

it('creates a program under a Department with level, tags and its own counters (FR-PROG-01/03/03a/11)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateProgram::class)
        ->fillForm(['name' => ['en' => 'MSc in CSE'], 'short_name' => 'MSc CSE'])
        ->call('create')
        ->assertHasFormErrors(['department_id' => 'required', 'level_term_id' => 'required']);

    $dual = Term::query()->create(['vocabulary_id' => app(Vocabularies::class)->find('program.tag')->id, 'name' => ['en' => 'Dual Degree (DIU-BCU)']]);

    Livewire::test(CreateProgram::class)
        ->fillForm([
            'department_id' => $this->cse->id, 'level_term_id' => $this->grad, 'tags' => [$dual->id],
            'name' => ['en' => 'MSc in Computer Science and Engineering'], 'short_name' => 'MSc CSE', 'web_address' => 'msc-cse',
            'shift' => 'evening', 'class_mode' => 'hybrid', 'total_credits' => 36, 'duration_years' => 1.5,
            'eligibility_rules' => ['min_cgpa' => '2.5'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $msc = Program::query()->where('short_name', 'MSc CSE')->sole();
    expect($msc->url())->toBe($msc->department->url().'/msc-cse')   // under its Department (FR-GEN-34)
        ->and($msc->faculty()->is($this->fsit))->toBeTrue()                         // from the Department
        ->and($msc->level->getTranslation('name', 'en'))->toBe('Graduate')
        ->and($msc->termIdsIn('program.tag'))->toBe([$dual->id])
        ->and($msc->eligibility_rules)->toBe(['min_cgpa' => '2.5']);

    $set = StatSet::query()->ownedBy($msc->getMorphClass().':'.$msc->id)->sole();
    expect($set->items()->pluck('metric_key')->filter()->values()->all())->toBe(['program.majors', 'program.industry-experts'])
        ->and($set->items()->where('source', 'manual')->count())->toBe(2);

    // The four levels are seeded once, however often the seeder runs
    (new ProgramDatabaseSeeder)->run();
    expect(array_values(app(Vocabularies::class)->options('program.level')))->toBe(['Undergraduate', 'Graduate', 'Diploma', 'Post Graduate Diploma']);
});

it('keeps a program address unique across the site (BRL-PROG-01)', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateProgram::class)
        ->fillForm(['department_id' => $this->swe->id, 'level_term_id' => $this->ug, 'name' => ['en' => 'BSc in CSE again'], 'short_name' => 'X', 'web_address' => $this->bsc->slug()])
        ->call('create')
        ->assertHasNoFormErrors();

    // The address that is taken gets a suffix; the first program keeps its own
    $again = Program::query()->where('short_name', 'X')->sole();
    expect($again->slug())->not->toBe($this->bsc->slug())
        ->and($again->slug())->toStartWith($this->bsc->slug())
        ->and(Program::findBySlug($this->bsc->slug(), 'en')[0]->is($this->bsc))->toBeTrue();
});

it('lets a Program user edit only their program, and a Department user its programs (AC-PROG-03)', function () {
    $mba = Program::factory()->create(['department_id' => $this->bba->id, 'short_name' => 'MBA']);
    $sweProgram = Program::factory()->create(['department_id' => $this->swe->id, 'short_name' => 'BSc SWE']);

    $programUser = makeUser(['ViewAny:Program', 'View:Program', 'Update:Program'], [$this->bsc]);
    $this->actingAs($programUser);
    $this->get(ProgramResource::getUrl())->assertOk()->assertSee('BSc CSE')->assertDontSee('MBA')->assertDontSee('BSc SWE');
    $this->get(ProgramResource::getUrl('edit', ['record' => $mba]))->assertNotFound();

    Livewire::test(EditProgram::class, ['record' => $this->bsc->getRouteKey()])
        ->assertFormFieldIsDisabled('department_id')
        ->assertFormFieldIsDisabled('short_name')
        ->assertFormFieldIsDisabled('web_address')
        ->fillForm(['summary' => ['en' => '<p>Updated by the program office.</p>'], 'total_credits' => 150])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->bsc->fresh()->total_credits)->toBe('150.0')
        ->and($this->bsc->fresh()->department_id)->toBe($this->cse->id);

    $deptUser = makeUser(['ViewAny:Program', 'View:Program'], [$this->cse]);
    $this->actingAs($deptUser);
    $this->get(ProgramResource::getUrl())->assertOk()->assertSee('BSc CSE')->assertDontSee('BSc SWE');

    $facultyUser = makeUser(['ViewAny:Program', 'View:Program'], [$this->fsit]);
    $this->actingAs($facultyUser);
    $this->get(ProgramResource::getUrl())->assertOk()->assertSee('BSc CSE')->assertSee('BSc SWE')->assertDontSee('MBA');
});

it('moves to another Department only by an Admin; the hierarchy follows (BRL-PROG-02)', function () {
    $moves = [];
    Event::listen(function (EntityHierarchyChanged $e) use (&$moves): void {
        $moves[] = [$e->fromParent, $e->toParent];
    });

    $this->actingAs(superAdmin());
    Livewire::test(EditProgram::class, ['record' => $this->bsc->getRouteKey()])
        ->fillForm(['department_id' => $this->swe->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $k = fn ($m) => $m->getMorphClass().':'.$m->id;
    expect($moves)->toBe([[$k($this->cse), $k($this->swe)]])
        ->and($this->bsc->fresh()->ancestorKeys())->toBe([$k($this->swe), $k($this->fsit)]);
});

it('blocks deleting a Department that has active programs (BRL-DEPT-02)', function () {
    expect(fn () => $this->cse->delete())->toThrow(ParentHasActiveChildren::class);

    $this->bsc->update(['is_active' => false]);
    $this->cse->delete();
    expect($this->cse->trashed())->toBeTrue();
});

it('filters /programs by faculty, department, level, shift, mode and search (FR-PROG-12, AC-PROG-01)', function () {
    Program::factory()->create(['department_id' => $this->cse->id, 'level_term_id' => $this->grad, 'name' => ['en' => 'MSc in CSE Evening'], 'shift' => 'evening', 'class_mode' => 'hybrid']);
    Program::factory()->create(['department_id' => $this->bba->id, 'level_term_id' => $this->grad, 'name' => ['en' => 'Executive MBA'], 'shift' => 'evening', 'class_mode' => 'on_campus']);
    Program::factory()->create(['department_id' => $this->bba->id, 'level_term_id' => $this->grad, 'name' => ['en' => 'Regular MBA'], 'shift' => 'day']);
    Program::factory()->create(['department_id' => $this->cse->id, 'name' => ['en' => 'BSc Hidden'], 'show_in_listing' => false]);
    Program::factory()->create(['department_id' => $this->cse->id, 'name' => ['en' => 'BSc Next Year'], 'published_at' => now()->addMonth()]);

    $names = fn (string $url): array => $this->get($url)->assertOk()->viewData('programs')->pluck('name')->all();

    expect($names('/programs'))->toContain('BSc in Computer Science and Engineering', 'Regular MBA')->not->toContain('BSc Hidden', 'BSc Next Year');

    // AC-PROG-01: Graduate + Evening
    expect($names('/programs?level=graduate&shift=evening'))->toContain('MSc in CSE Evening', 'Executive MBA')->not->toContain('Regular MBA', 'BSc in Computer Science and Engineering')
        ->and($names('/programs?faculty=fbe'))->toContain('Executive MBA')->not->toContain('MSc in CSE Evening')
        ->and($names('/programs?department=cse&mode=hybrid'))->toContain('MSc in CSE Evening')->not->toContain('Executive MBA')
        ->and($names('/programs?q=executive'))->toContain('Executive MBA')->not->toContain('Regular MBA')
        ->and($names('/programs?level=nonsense&shift=<script>'))->toContain('Regular MBA')   // unknown values are ignored
        ->and($names('/programs?q=zzz-nothing'))->toBe([]);

    // The module's own layouts, for themes without their own
    $data = $this->get('/programs?q=zzz-nothing')->viewData('choices');
    expect(moduleView('Program', 'listing.card', ['programs' => collect(), 'choices' => $data, 'selected' => ['faculty' => null, 'department' => null, 'level' => null, 'shift' => null, 'mode' => null, 'q' => 'zzz-nothing'], 'options' => []]))->toContain('No program matches these filters.');
    $all = $this->get('/programs')->viewData('programs');
    foreach (['card' => 'program-card--card', 'list' => 'program-card--list', 'table' => 'program-row', 'detailed' => 'program-card--detailed'] as $layout => $marker) {
        expect(moduleView('Program', "listing.{$layout}", ['programs' => $all, 'choices' => $data, 'selected' => ['faculty' => null, 'department' => null, 'level' => null, 'shift' => null, 'mode' => null, 'q' => null], 'options' => []]))->toContain($marker);
    }

    // A program of an inactive Department leaves the list
    $this->bba->update(['is_active' => false]);
    expect($names('/programs'))->not->toContain('Regular MBA');
});

it('takes Quick Links from the program, else Settings › Programs, and the /programs text and links from Settings', function () {
    // Defaults: DIU's
    $this->get($this->bsc->url())->assertOk()->assertSeeInOrder(['Quick Links', 'Notice', 'Waiver &amp; Scholarship', 'Tuition Fees', 'Tuition Fee Calculator'], false);
    $this->get('/programs')->assertOk()->assertSee('Institutional Programs')->assertSee('Rules and Regulation');

    $this->actingAs(superAdmin());
    Livewire::test(EditProgram::class, ['record' => $this->bsc->getRouteKey()])
        ->fillForm(['quick_links' => [['label' => ['en' => 'CSE Lab Booking'], 'url' => 'https://labs.example.edu']]])
        ->call('save')->assertHasNoFormErrors();
    $this->get($this->bsc->url())->assertSee('CSE Lab Booking')->assertSee('https://labs.example.edu', false)->assertDontSee('Tuition Fee Calculator');

    settings()->saveFor(new ProgramSettings, [
        'listing_title' => ['en' => 'All Our Programs'],
        'side_links' => [['label' => ['en' => 'Short Courses'], 'url' => '/short-courses']],
        'quick_links' => [['label' => ['en' => 'Credit Transfer'], 'url' => '/credit-transfer']],
        'page_quick_links' => [['label' => ['en' => 'Program Calendar'], 'url' => '/calendar']],
    ]);
    $this->get('/programs')->assertOk()->assertSee('All Our Programs')->assertSee('Short Courses')->assertSee('Credit Transfer')
        ->assertDontSee('Institutional Programs')->assertDontSee('Rules and Regulation');
    $other = Program::factory()->create(['department_id' => $this->cse->id]);
    $this->get($other->url())->assertSee('Program Calendar')->assertDontSee('Tuition Fee Calculator');   // no own links: Settings'
});

it('shows DIU\'s /programs in the default theme: faculties as tabs, UG / PG with counts, every program (Phase 4.4d)', function () {
    Program::factory()->create(['department_id' => $this->bba->id, 'level_term_id' => $this->grad, 'name' => ['en' => 'Regular MBA'], 'short_name' => 'MBA']);

    $this->get('/programs')->assertOk()
        ->assertSee('Explore Our Offerings')
        ->assertSeeInOrder(['Browse by Faculty (03)', 'Faculty of Science and Information Technology', 'Faculty of Business and Entrepreneurship', 'Institutional Programs'])
        ->assertSee('Full Program Name')
        ->assertSeeInOrder(['BSc in Computer Science and Engineering', 'BSc CSE'])
        ->assertSee('Regular MBA')
        ->assertSee("faculty === 'FBE' && level === 'PG'", false);   // a graduate program is "Post Graduate"

    // ?faculty= only picks the first faculty: the others stay on the page
    $this->get('/programs?faculty=fbe&isPostgraduate=1')->assertOk()->assertSee('Regular MBA')->assertSee('BSc in Computer Science and Engineering');
});

it('shows the program page with facts, apply button, eligibility and counters (FR-PROG-10/13)', function () {
    // Through the default theme (DIU's program page)
    $this->get($this->bsc->url())->assertOk()
        ->assertSee('BSc in Computer Science and Engineering')
        ->assertSeeInOrder(['Credit Requirement', '148 credits', 'Total Semester', '12 Semester'])
        ->assertSee('Graduates who solve real problems.')
        ->assertSee('SSC and HSC GPA 2.5 each.')
        ->assertSee('Five O Level and two A Level subjects.')
        ->assertSee('https://admission.example.edu/apply', false)   // the header's Apply button
        ->assertSee('2027-01-15')
        ->assertSee($this->cse->url(), false)
        ->assertSee('microsite-name', false);

    // The module's own page, for themes without their own
    expect(moduleView('Program', 'microsite.home', ['entity' => $this->bsc]))->toContain('Bachelor of Science', '4 Years (12 Semesters)', 'Build software and systems.',
        'Graduates who solve real problems.', 'https://admission.example.edu/apply', '15 January 2027', $this->cse->url());

    $this->get('/bn'.$this->bsc->url('bn'))->assertOk()->assertSee('কম্পিউটার বিজ্ঞান ও প্রকৌশলে বিএসসি');

    // Scheduled or inactive: no page
    $later = Program::factory()->create(['department_id' => $this->cse->id, 'published_at' => now()->addWeek()]);
    $this->get($later->url())->assertNotFound();
});

it('shows DIU\'s program page in the default theme: hero, sections menu, AI summary, overview (Phase 4.4c)', function () {
    $this->bsc->update([
        'subtitle' => ['en' => 'Build the future.'], 'video_url' => 'https://www.youtube.com/watch?v=wSNsd4LuhAI',
        'credit_duration' => ['en' => '<p>148 credits in four years.</p>'], 'ai_summary' => ['en' => 'An AI written summary.'],
    ]);

    $this->get($this->bsc->url())->assertOk()
        ->assertSee('page-hero', false)->assertSee('Build the future.')
        ->assertSee('youtube.com/embed/wSNsd4LuhAI', false)
        ->assertSeeInOrder(['Browse by Section (02)', 'href="#overview"', 'href="#eligibilities"'], false)
        ->assertSeeInOrder(['AI Summary', 'An AI written summary.', 'Program Overview', 'Program Objective', 'Graduates who solve real problems.', 'Credit &amp; Duration', '148 credits in four years.'], false)
        ->assertDontSee('<p>Build software and systems.</p>', false);   // the summary stands in only when neither text is there

    $this->bsc->update(['objective' => null, 'credit_duration' => null]);
    $this->get($this->bsc->url())->assertOk()->assertSee('<p>Build software and systems.</p>', false);
});

it('adds other modules\' tabs to the program page; a missing or broken one never breaks it (FR-PROG-18, AC-PROG-02)', function () {
    $this->get($this->bsc->url())->assertOk()->assertDontSee('href="#tuition-fee"', false);   // the module is not active: no tab

    app(Registry::class)->add('entityTab', 'fee', ['target' => Program::class, 'class' => ProgramTestTuitionTab::class]);
    app(Registry::class)->add('entityTab', 'broken', ['target' => Program::class, 'class' => ProgramTestBrokenTab::class]);

    $this->get($this->bsc->url())->assertOk()
        ->assertSee('Tuition Fees')
        ->assertSee('href="#tuition-fee"', false)
        ->assertSee('program-card--list', false);

    $nofee = Program::factory()->create(['department_id' => $this->cse->id, 'short_name' => 'NOFEE']);
    $this->get($nofee->url())->assertOk()->assertDontSee('href="#tuition-fee"', false);   // the tab chose to hide
});

it('counts programs for the main site, a Faculty and a Department (FR-FAC-04, FR-DEPT-05)', function () {
    Program::factory()->create(['department_id' => $this->swe->id]);
    Program::factory()->create(['department_id' => $this->bba->id]);
    Program::factory()->create(['department_id' => $this->cse->id, 'is_active' => false]);

    $label = fn (Model $owner) => collect(app(Statistics::class)->forOwner($owner->getMorphClass().':'.$owner->id))->firstWhere('label', 'Programs')['display'] ?? null;

    expect($label($this->fsit))->toBe('2')
        ->and($label($this->cse))->toBe('1')
        ->and($label($this->fbe))->toBe('1');
});

it('offers "Featured Programs" and "Programs by Level", search and sitemap (FR-PROG-16)', function () {
    $mba = Program::factory()->create(['department_id' => $this->bba->id, 'level_term_id' => $this->grad, 'short_name' => 'MBA']);
    $featured = new FeaturedProgramsSection;

    expect($featured->items(new SectionContext('site:0', ['program_ids' => [$mba->id, $this->bsc->id]], 'en'))->pluck('short_name')->all())->toBe(['MBA', 'BSc CSE'])
        ->and($featured->items(new SectionContext($this->fsit->getMorphClass().':'.$this->fsit->id, [], 'en'))->pluck('short_name')->all())->toBe(['BSc CSE']);

    $groups = (new ProgramsByLevelSection)->items(new SectionContext('site:0', [], 'en'));
    expect($groups->map(fn (array $g): string => $g['level']->name)->all())->toBe(['Undergraduate', 'Graduate'])
        ->and($groups[1]['programs']->pluck('short_name')->all())->toBe(['MBA']);

    expect(app(SearchIndex::class)->search('Computer Science')['groups'])->toHaveKey('program');
    $this->get('/sitemap.xml')->assertSee(url($this->bsc->url()), false);
});

it('lists programs whose rich text is very large (DIU pages up to 1.4 MB: MySQL sort buffer)', function () {
    Program::factory()->create(['department_id' => $this->cse->id, 'name' => ['en' => 'BSc with a huge page'], 'summary' => ['en' => '<table>'.str_repeat('<tr><td style="mso-line-height-rule:exactly">Physics I</td></tr>', 25000).'</table>']]);

    $this->get('/programs')->assertOk()->assertSee('BSc with a huge page');

    $this->actingAs(superAdmin());
    $this->get(ProgramResource::getUrl())->assertOk()->assertSee('BSc with a huge page');
});

it('lets only permitted users into the program screens', function () {
    $this->actingAs(makeUser());
    $this->get(ProgramResource::getUrl())->assertForbidden();

    $this->actingAs(superAdmin());
    $this->get(ProgramResource::getUrl())->assertOk()->assertSee('BSc CSE');
    $this->get(ProgramResource::getUrl('create'))->assertOk();
});

it('shows a faculty\'s admission eligibility as a page block: programs by level with their eligibility (DIU /admission/faculty)', function () {
    Program::factory()->create(['department_id' => $this->cse->id, 'level_term_id' => $this->grad, 'name' => ['en' => 'MSc in CSE'], 'short_name' => 'MSc CSE']);
    Program::factory()->create(['department_id' => $this->bba->id, 'level_term_id' => $this->ug, 'name' => ['en' => 'Bachelor of Business Administration']]);
    $block = fn (int $faculty): string => (string) PageBlocks::render([['type' => 'program-eligibility', 'data' => [
        'faculty' => $faculty, 'links' => [['label' => ['en' => 'Tuition Fees'], 'url' => '/tuition-fees']],
    ]]], 'en');

    expect(PageBlocks::all())->toHaveKey('program-eligibility');

    // The default theme: DIU's level buttons with counts, full name and short tag, the eligibility, the program page and the links
    $html = $block($this->fsit->id);
    expect($html)->toContain('Faculty of Science and Information Technology')
        ->toContain('Undergraduate Program (1)')->toContain('Graduate Program (1)')
        ->toContain('BSc in Computer Science and Engineering')->toContain('BSc CSE')->toContain('MSc in CSE')
        ->toContain('General Student')->toContain('SSC and HSC GPA 2.5 each.')
        ->toContain('English Medium Student')->toContain('Five O Level and two A Level subjects.')
        ->toContain(e(url($this->bsc->url())))->toContain(url('/tuition-fees'))
        ->not->toContain('Bachelor of Business Administration')       // another faculty's
        ->not->toContain('<diu-rich-html');                            // a module block in the theme's look

    // The module's own view, for themes without their own
    $data = ['data' => ['faculty' => $this->fbe->id], 'locale' => 'en'];
    expect(moduleView('program', 'blocks.eligibility', [...$data, 'eligibility' => new EligibilityPage($data)]))
        ->toContain('Bachelor of Business Administration')->not->toContain('BSc in Computer Science and Engineering');
});
