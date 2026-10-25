<?php

/*
| Phase 3.31 — M-07 Faculty Member (DB-03 §8 v1.7, SRS Academic v0.5 FR-FM-02..12): a read-only
| copy of the faculty site — sync from a JSON file or the API (FR-FM-08, AC-FM-02/03,
| BRL-FM-01..05), admin with local switches only (FR-FM-11), directory, Department / Faculty
| microsites, profile, homepage section, metric, scope, person source, search and sitemap.
*/

use App\Services\FmsApiClient;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\FacultyMember\Filament\Resources\FacultyMembers\Pages\EditFacultyMember;
use Modules\FacultyMember\Filament\Resources\FacultyMembers\Pages\ListFacultyMembers;
use Modules\FacultyMember\Metrics\FacultyMemberCount;
use Modules\FacultyMember\Models\FacultyMember;
use Modules\FacultyMember\Sections\FacultyMembersSection;
use Modules\FacultyMember\Settings\FacultyMemberSettings;
use Modules\FacultyMember\Sync\ApiSource;
use Modules\FacultyMember\Sync\FacultyMemberSource;
use Modules\FacultyMember\Sync\FacultyMemberSync;
use Modules\FacultyMember\Sync\JsonFileSource;
use Modules\FacultyMember\Sync\RecordList;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\RoleCatalog;
use Platform\Filament\Components\PersonPicker;
use Platform\Homepage\SectionContext;
use Platform\Modules\ModulePermissions;
use Platform\Scope\UserScopeFilter;
use Platform\Seo\Sitemap;
use Platform\Statistics\StatContext;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/** A source that returns what the test gives it. */
function facultySource(array $records): FacultyMemberSource
{
    return new class($records) implements FacultyMemberSource
    {
        public function __construct(public array $records) {}

        public function records(): array
        {
            return RecordList::from(['data' => $this->records]);
        }

        public function describe(): string
        {
            return 'test';
        }
    };
}

function teacher(string $id, array $overrides = []): array
{
    return array_merge([
        'id' => $id,
        'name' => 'Teacher '.$id,
        'designation' => 'Lecturer',
        'department' => 'CSE',
        'email' => strtolower($id).'@example.edu',
        'phone' => '+880 1700 '.substr(md5($id), 0, 6),
    ], $overrides);
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['Faculty', 'Department', 'FacultyMember'] as $module) {
        app(ModulePermissions::class)->sync($module);
    }
    Vocabularies::ensureTerms(FacultyMember::DESIGNATION, [
        ['name' => ['en' => 'Professor']], ['name' => ['en' => 'Associate Professor']], ['name' => ['en' => 'Lecturer']],
    ]);

    $this->fsit = Faculty::factory()->create(['short_name' => 'FSIT']);
    $this->cse = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'CSE', 'name' => ['en' => 'Computer Science and Engineering']]);
    $this->swe = Department::factory()->create(['faculty_id' => $this->fsit->id, 'short_name' => 'SWE', 'name' => ['en' => 'Software Engineering']]);
    $fbe = Faculty::factory()->create(['short_name' => 'FBE']);
    $this->bba = Department::factory()->create(['faculty_id' => $fbe->id, 'short_name' => 'BBA', 'name' => ['en' => 'Business Administration']]);

    $this->records = [
        teacher('T-1', ['name' => ['en' => 'Dr. Rina Khan', 'bn' => 'ড. রিনা খান'], 'designation' => 'Professor', 'designation_note' => 'and Head',
            'research_interests' => 'Machine learning', 'links' => ['google_scholar' => 'https://scholar.google.com/citations?user=X', 'orcid' => 'not a url'],
            'profile' => ['education' => [['title' => 'PhD', 'institution' => 'University of Tokyo', 'end_year' => 2010]], 'publication' => ['Deep nets (2019)']]]),
        teacher('T-2', ['name' => 'Kamal Uddin', 'designation' => 'Lecturer', 'other_departments' => ['swe']]),
        teacher('T-3', ['name' => 'Mitu Akter', 'designation' => 'Associate Professor', 'employment_type' => 'on leave']),
        teacher('T-4', ['name' => 'Sabbir Alam', 'department' => 'BBA', 'designation' => 'Senior Lecturer']),
        teacher('T-5', ['name' => 'Gone Soon', 'status' => 'inactive']),
    ];
    $this->sync = fn (?array $records = null) => app(FacultyMemberSync::class)->run(facultySource($records ?? $this->records));
});

it('copies the teachers: departments by short name, new designations as terms, inactive ones hidden (FR-FM-06/08, BRL-FM-02)', function () {
    $result = ($this->sync)();

    expect($result->ok())->toBeTrue()
        ->and([$result->total, $result->created, $result->updated, $result->removed])->toBe([5, 5, 0, 0])
        ->and($result->unmatchedDepartments)->toBe([]);

    $rina = FacultyMember::query()->where('external_ref', 'T-1')->sole();
    expect($rina->department_id)->toBe($this->cse->id)
        ->and($rina->getTranslation('name', 'bn'))->toBe('ড. রিনা খান')
        ->and($rina->designationLabel())->toBe('Professor and Head')
        ->and($rina->links)->toBe(['google_scholar' => 'https://scholar.google.com/citations?user=X'])
        ->and($rina->profileSection('education')[0])->toMatchArray(['title' => 'PhD', 'institution' => 'University of Tokyo', 'end_year' => 2010])
        ->and($rina->profileSection('publication')[0]['title'])->toBe('Deep nets (2019)');

    expect(FacultyMember::query()->where('external_ref', 'T-2')->sole()->departments->pluck('id')->all())->toBe([$this->swe->id])
        ->and(FacultyMember::query()->where('external_ref', 'T-3')->sole()->isOnLeave())->toBeTrue()
        ->and(FacultyMember::query()->where('external_ref', 'T-5')->sole()->is_active)->toBeFalse()
        ->and(Term::query()->whereJsonContains('name->en', 'Senior Lecturer')->exists())->toBeTrue();   // made by the sync

    // Nothing changed: nothing written
    $again = ($this->sync)();
    expect([$again->created, $again->updated, $again->unchanged])->toBe([0, 0, 5]);
});

it('follows the faculty site everywhere, and brings a returning teacher back with their roles (AC-FM-02)', function () {
    ($this->sync)();
    $rina = FacultyMember::query()->where('external_ref', 'T-1')->sole();
    app(RoleCatalog::class)->sync();
    app(AssignmentEngine::class)->assign($rina, $this->cse, 'head');

    $records = $this->records;
    $records[0]['designation'] = 'Associate Professor';
    $result = ($this->sync)($records);
    expect($result->updated)->toBe(1)
        ->and(app(AssignmentEngine::class)->people($this->cse)->first()->designation())->toBe('Associate Professor and Head');

    $result = ($this->sync)(array_slice($records, 1));   // gone from the faculty site
    expect($result->removed)->toBe(1)->and(FacultyMember::query()->where('external_ref', 'T-1')->exists())->toBeFalse();
    $this->get('/faculty-members')->assertDontSee('Dr. Rina Khan');

    $result = ($this->sync)($records);   // back again
    expect($result->restored)->toBe(1)
        ->and(app(AssignmentEngine::class)->people($this->cse)->first()?->name())->toBe('Dr. Rina Khan');
});

it('stops without removing anyone when the source is empty, broken or unreachable (BRL-FM-05, AC-FM-03)', function () {
    ($this->sync)();

    $sync = app(FacultyMemberSync::class);
    $empty = $sync->run(new JsonFileSource(tempnam(sys_get_temp_dir(), 'fm')));   // empty file
    expect($empty->ok())->toBeFalse();

    $path = tempnam(sys_get_temp_dir(), 'fm');
    file_put_contents($path, json_encode(['data' => []]));
    expect($sync->run(new JsonFileSource($path))->failure)->toBe(__('facultymember::facultymember.sync.empty'));

    file_put_contents($path, json_encode(['data' => [['name' => 'No id']]]));
    expect($sync->run(new JsonFileSource($path))->ok())->toBeFalse();

    Http::fake(['faculty.example.edu/*' => Http::response('down', 503)]);
    expect($sync->run(new ApiSource('https://faculty.example.edu/api/teachers'))->ok())->toBeFalse();

    expect(FacultyMember::query()->withoutGlobalScopes()->whereNull('deleted_at')->count())->toBe(5)
        ->and(FacultyMemberSync::lastResult()['failure'])->not->toBeNull();
});

it('reports unknown departments and broken records, keeping the teacher without a department (BRL-FM-01, BRL-FM-04)', function () {
    $records = [...$this->records, teacher('T-6', ['department' => 'MARS']), ['name' => 'No id'], teacher('T-7', ['employee_id' => '710-1']), teacher('T-8', ['employee_id' => '710-1'])];
    $result = ($this->sync)($records);

    expect($result->unmatchedDepartments)->toBe(['MARS'])
        ->and($result->errors)->toHaveCount(2)   // no id; employee ID taken
        ->and(FacultyMember::query()->where('external_ref', 'T-6')->sole()->department_id)->toBeNull()
        ->and(FacultyMember::query()->where('external_ref', 'T-8')->sole()->employee_id)->toBeNull();

    $this->get('/faculty-members')->assertSee('Teacher T-6');
    $this->get(rtrim($this->cse->url(), '/').'/faculty-members')->assertDontSee('Teacher T-6');
});

it('reads the API, following its pages, with the token', function () {
    Http::fake([
        'faculty.example.edu/api/teachers?page=2' => Http::response(['data' => [teacher('T-2')], 'next_page_url' => null]),
        'faculty.example.edu/api/teachers' => Http::response(['data' => [teacher('T-1')], 'next_page_url' => 'https://faculty.example.edu/api/teachers?page=2']),
    ]);
    settings()->save('faculty-member', ['source' => 'api', 'api_url' => 'https://faculty.example.edu/api/teachers', 'api_token' => 'secret'], encrypted: ['api_token']);

    $result = app(FacultyMemberSync::class)->run();

    expect($result->created)->toBe(2)->and($result->source)->toBe('API faculty.example.edu');
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer secret'));
});

/** A teacher as DIU's FMS API gives it (GET /teachers, /{faculty}/{department}/{teacher}) */
function fmsTeacher(array $overrides = []): array
{
    return array_merge([
        'id' => 2125, 'slug' => 'muntachir', 'full_name' => 'Muntachir Razzak', 'designation' => 'Assistant Professor', 'designation_title' => 'Assistant Professor',
        'department' => ['id' => 5, 'name' => 'Computer Science and Engineering', 'code' => 'CSE', 'faculty' => ['id' => 1, 'short_name' => 'FSIT']],
        'photo_url' => 'https://fms.test/photos/muntachir.jpg', 'office_room' => '605', 'phone' => '01713493093', 'email' => 'web3@example.edu',
        'employment_status' => null, 'bio' => 'Works on enterprise systems.', 'publications_count' => 1,
    ], $overrides);
}

it('syncs the teachers from DIU\'s FMS API, page by page, keeping each teacher\'s FMS address', function () {
    config(['services.fms' => ['url' => 'https://fms.test/api/v1', 'token' => 'test-token', 'cache_ttl' => 3600]]);
    app()->forgetInstance(FmsApiClient::class);
    Http::preventStrayRequests();
    Http::fake([
        'fms.test/api/v1/teachers?page=1&per_page=100' => Http::response(['data' => [fmsTeacher()], 'meta' => ['current_page' => 1, 'last_page' => 2, 'total' => 2]]),
        'fms.test/api/v1/teachers?page=2&per_page=100' => Http::response(['data' => [fmsTeacher(['id' => 2200, 'slug' => 'gone', 'full_name' => 'Left Already', 'employment_status' => 'Resigned',
            'department' => ['code' => 'BADM', 'name' => 'Business Administration', 'faculty' => ['short_name' => 'FBE']]])], 'meta' => ['current_page' => 2, 'last_page' => 2, 'total' => 2]]),
    ]);
    expect((new FacultyMemberSettings)->defaults()['source'])->toBe('json');  // the sample until the owner turns FMS on
    ($this->sync)();                                                          // another source's teachers ("T-1" …) first
    settings()->save('faculty-member', ['source' => 'fms']);

    $result = app(FacultyMemberSync::class)->run();

    expect($result->ok())->toBeTrue()->and($result->created)->toBe(2)->and($result->removed)->toBe(5)->and($result->source)->toBe('FMS API fms.test');
    $member = FacultyMember::query()->where('external_ref', '2125')->sole();
    expect($member->source_path)->toBe('FSIT/CSE/muntachir')
        ->and($member->department_id)->toBe($this->cse->id)
        ->and($member->name)->toBe('Muntachir Razzak')
        ->and($member->photo_url)->toBe('https://fms.test/photos/muntachir.jpg')
        ->and(FacultyMember::query()->where('external_ref', '2200')->sole()->is_active)->toBeFalse()     // resigned
        ->and(FacultyMember::query()->where('external_ref', '2200')->sole()->department_id)->toBe($this->bba->id)   // FMS code BADM ≠ BBA: by name
        ->and($result->unmatchedDepartments)->toBe([]);
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token'));
});

it('links each teacher to their profile on the faculty website, with no profile page here (owner 2026-10-25)', function () {
    ($this->sync)([...$this->records, ['id' => '2125', 'source_path' => 'FSIT/CSE/muntachir', 'name' => 'Muntachir Razzak', 'department' => 'CSE', 'designation' => 'Lecturer']]);
    settings()->save('faculty-member', ['profile_site' => 'https://faculty.example.edu/']);
    $muntachir = FacultyMember::query()->where('external_ref', '2125')->sole();
    $rina = FacultyMember::query()->where('external_ref', 'T-1')->sole();                         // no FMS address

    expect($muntachir->profileUrl())->toBe('https://faculty.example.edu/FSIT/CSE/muntachir')
        ->and($muntachir->personPresenter()->profileUrl())->toBe('https://faculty.example.edu/FSIT/CSE/muntachir')
        ->and($rina->profileUrl())->toBeNull()
        ->and($muntachir->url())->toBe('/faculty-members?q=Muntachir%20Razzak');                    // here: the directory
    expect(moduleView('FacultyMember', 'partials.card', ['member' => $muntachir->load('department'), 'layout' => 'grid']))
        ->toContain('href="https://faculty.example.edu/FSIT/CSE/muntachir" target="_blank"');

    // The default theme (Phase 4.6b): DIU's sections with their teachers, DeanCard-style cards, "View Profile" out
    $this->get('/faculty-members')->assertOk()
        ->assertSeeInOrder(['All (5)', 'FSIT (4)', 'FBE (1)'])
        ->assertSee('faculty-member-card', false)->assertSee('MR')                                   // initials without a photo
        ->assertSeeInOrder(['Muntachir Razzak', 'View Profile'])
        ->assertSee('href="https://faculty.example.edu/FSIT/CSE/muntachir" target="_blank"', false);
    $this->get('/faculty-members?faculty=FBE')->assertOk()->assertSee('Sabbir Alam')->assertDontSee('Muntachir Razzak');

    $this->get('/faculty-members/'.$muntachir->slug())->assertRedirect('https://faculty.example.edu/FSIT/CSE/muntachir')->assertStatus(301);   // an old address
    $this->get('/faculty-members/'.$rina->slug())->assertNotFound();
    expect(view()->exists('facultymember::profile'))->toBeFalse();
});

it('reads the sample JSON file shipped with the module', function () {
    $records = (new JsonFileSource(JsonFileSource::samplePath()))->records();
    expect(count($records))->toBeGreaterThan(50)
        ->and($records[0])->toMatchArray(['id' => '1001', 'source_path' => 'FSIT/CSE/arif-alam', 'name' => 'Arif Alam', 'department' => 'CSE', 'designation_note' => 'and Head']);   // the FMS format, read as FMS

    $result = app(FacultyMemberSync::class)->run();   // default source: the sample
    expect($result->ok())->toBeTrue()->and($result->source)->toBe('JSON faculty-members.json');
});

it('keeps the faculty site\'s fields read only and lets the admin switch contacts off (FR-FM-11, BRL-FM-03)', function () {
    ($this->sync)();
    $rina = FacultyMember::query()->where('external_ref', 'T-1')->sole();
    $this->actingAs(superAdmin());

    Livewire::test(ListFacultyMembers::class)->assertCanSeeTableRecords([$rina])->assertActionVisible('sync')->assertActionDoesNotExist('create');

    Livewire::test(EditFacultyMember::class, ['record' => $rina->getRouteKey()])
        ->fillForm(['show_email' => false])->call('save')->assertHasNoFormErrors();
    expect($rina->fresh()->show_email)->toBeFalse()
        ->and($rina->fresh()->personPresenter()->email())->toBeNull();

    // The sync never touches it
    $records = $this->records;
    $records[0]['phone'] = '+880 1999 000000';
    ($this->sync)($records);
    expect($rina->fresh())->show_email->toBeFalse()->phone->toBe('+880 1999 000000');

    expect(auth()->user()->can('create', FacultyMember::class))->toBeFalse()
        ->and(auth()->user()->can('delete', $rina))->toBeFalse();
});

it('runs Sync now from the list for holders of the permission only', function () {
    $user = makeUser(['ViewAny:FacultyMember', 'View:FacultyMember']);
    $this->actingAs($user);
    Livewire::test(ListFacultyMembers::class)->assertActionHidden('sync');

    $this->actingAs(superAdmin());
    settings()->save('faculty-member', ['json_path' => JsonFileSource::samplePath()]);
    Livewire::test(ListFacultyMembers::class)->callAction('sync')->assertNotified();
    expect(FacultyMember::query()->count())->toBeGreaterThan(50);
});

it('lists teachers in designation order with filters, search and layouts (FR-FM-03, FR-FM-09)', function () {
    ($this->sync)();

    $this->get('/faculty-members')->assertOk()
        ->assertSeeInOrder(['Dr. Rina Khan', 'Mitu Akter', 'Kamal Uddin'])
        ->assertSee('On leave')->assertSee('Sabbir Alam')->assertDontSee('Gone Soon')
        ->assertDontSee('t-1@example.edu');   // written in character references
    $this->get('/faculty-members?faculty=FBE')->assertOk()->assertSee('Sabbir Alam')->assertDontSee('Kamal Uddin');
    $this->get('/faculty-members?department=SWE')->assertOk()->assertSee('Kamal Uddin')->assertDontSee('Dr. Rina Khan');
    $this->get('/faculty-members?q=machine&view=list')->assertOk()->assertSee('Dr. Rina Khan')->assertDontSee('Kamal Uddin');
});

it('shows a department\'s and a faculty\'s own teachers on their microsites (FR-FM-05, AC-FM-01)', function () {
    ($this->sync)();

    $this->get(rtrim($this->cse->url(), '/').'/faculty-members')->assertOk()
        ->assertSeeInOrder(['Dr. Rina Khan', 'Mitu Akter', 'Kamal Uddin'])->assertDontSee('Sabbir Alam')->assertDontSee('Gone Soon');
    $this->get(rtrim($this->swe->url(), '/').'/faculty-members')->assertOk()->assertSee('Kamal Uddin')->assertDontSee('Dr. Rina Khan');   // also in SWE
    $this->get(rtrim($this->fsit->url(), '/').'/faculty-members')->assertOk()->assertSee('Kamal Uddin')->assertDontSee('Sabbir Alam');

    $items = (new FacultyMembersSection)->items(new SectionContext($this->cse->getMorphClass().':'.$this->cse->id, ['count' => 2], 'en'));
    expect($items['members']->pluck('external_ref')->all())->toBe(['T-1', 'T-3'])->and($items['url'])->toEndWith('/faculty-members');
    $html = view(FacultyMembersSection::view(), ['items' => $items, 'section' => null, 'settings' => []])->render();
    expect($html)->toContain('Dr. Rina Khan')->toContain('All faculty members');
});

it('counts teachers per place (FR-FM-12)', function () {
    ($this->sync)();
    $metric = new FacultyMemberCount;

    expect($metric->count(StatContext::site(), []))->toBe(4)
        ->and($metric->count(StatContext::of($this->cse->getMorphClass().':'.$this->cse->id), []))->toBe(3)
        ->and($metric->count(StatContext::of($this->swe->getMorphClass().':'.$this->swe->id), []))->toBe(1)
        ->and($metric->count(StatContext::of($this->fsit->getMorphClass().':'.$this->fsit->id), []))->toBe(3);
});

it('lets a department user see only their teachers, and offers teachers in the person picker and search, not the sitemap', function () {
    ($this->sync)();
    $rina = FacultyMember::query()->where('external_ref', 'T-1')->sole();
    $sabbir = FacultyMember::query()->where('external_ref', 'T-4')->sole();

    $this->actingAs(makeUser(['ViewAny:FacultyMember', 'View:FacultyMember'], [$this->bba]));
    UserScopeFilter::enforce();
    try {
        Livewire::test(ListFacultyMembers::class)->assertCanSeeTableRecords([$sabbir])->assertCanNotSeeTableRecords([$rina]);
    } finally {
        UserScopeFilter::enforce(false);
    }

    expect(collect(PersonPicker::search('Rina'))->keys()->all())->toContain(PersonPicker::keyOf($rina))
        ->and($rina->toSearchDocument('en'))->toMatchArray(['title' => 'Dr. Rina Khan']);
    $sitemap = collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' ');
    expect($sitemap)->not->toContain('/faculty-members/');                                          // no profile pages here
});
