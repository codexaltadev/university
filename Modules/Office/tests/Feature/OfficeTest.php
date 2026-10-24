<?php

/*
| M-12 Office completed (SRS FR-OFF-01..14, DB-04 §3): profile per language, type, own page
| or contact card, sections / services / support contacts with their people, /offices,
| /services, /support-contacts, the microsite home, counters, office users managing their own.
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Faq\Models\Faq;
use Modules\Homepage\Support\Homepages;
use Modules\Office\Database\Seeders\OfficeDatabaseSeeder;
use Modules\Office\Filament\Resources\Offices\OfficeResource;
use Modules\Office\Filament\Resources\Offices\Pages\EditOffice;
use Modules\Office\Filament\Resources\Offices\RelationManagers\ServicesRelationManager;
use Modules\Office\Filament\Resources\Offices\RelationManagers\SupportContactsRelationManager;
use Modules\Office\Models\Office;
use Modules\Office\Models\OfficeService;
use Modules\Office\Models\OfficeSupportContact;
use Modules\Office\Settings\OfficeSettings;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Modules\ModulePermissions;
use Platform\Permissions\EntityPermissions;
use Platform\Relations\RelationEngine;
use Platform\Statistics\Statistics;
use Platform\Taxonomy\Vocabularies;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Office');
    app(RoleCatalog::class)->sync();
    (new OfficeDatabaseSeeder)->run();
    $type = fn (string $name): int => (int) array_search($name, app(Vocabularies::class)->options(Office::TYPE), true);

    $this->registrar = Office::create([
        'name' => ['en' => 'Registrar Office'], 'type_term_id' => $type('Administration'), 'overview' => ['en' => '<p>Records and certificates.</p>'],
        'email' => 'registrar@example.edu', 'phone' => '+880 1847-140000', 'location' => ['en' => 'Building 1, Level 3'], 'sort_order' => 1,
    ]);
    $this->transport = Office::create(['name' => ['en' => 'Transport'], 'type_term_id' => $type('Student Services'), 'has_microsite' => false, 'phone' => '+880 1700-000000', 'sort_order' => 2]);

    $this->transcript = OfficeService::create(['office_id' => $this->registrar->id, 'title' => ['en' => 'Transcript issue'], 'required_documents' => ['en' => '<p>Student ID card</p>'], 'fee_amount' => 500, 'processing_time' => ['en' => '7 working days']]);
    $this->idCard = OfficeSupportContact::create(['office_id' => $this->registrar->id, 'purpose' => ['en' => 'ID card problem after admission'], 'location' => ['en' => 'Room 301']]);
    $person = ManualPerson::create(['name' => ['en' => 'Ms. Akter'], 'designation' => ['en' => 'Officer'], 'phone' => '+8801700000001']);
    app(AssignmentEngine::class)->assign($person, $this->idCard, OfficeSupportContact::ROLE);
    app(AssignmentEngine::class)->assign($person, $this->transcript, OfficeService::ROLE);
    app(AssignmentEngine::class)->assign($person, $this->registrar, 'team');
});

it('lists offices by type, a contact card for one without its own page (FR-OFF-09/11)', function () {
    $this->get('/offices')->assertOk()
        ->assertSeeInOrder(['Administration', 'Registrar Office', 'Student Services', 'Transport'])
        ->assertSee($this->registrar->url(), false)
        ->assertSee('+880 1700-000000');

    expect($this->transport->url())->toBeNull();
    $this->get('/office/transport')->assertNotFound();
});

it('opens an office microsite with overview, services, team and contact (FR-OFF-10)', function () {
    // The module's own page (any theme without its version)
    $html = moduleView('Office', 'microsite.home', ['entity' => $this->registrar]);
    expect($html)->toContain('Records and certificates.')->toContain('Building 1, Level 3')
        ->and(strpos($html, 'Transcript issue'))->toBeLessThan(strpos($html, 'Ms. Akter'));

    // Through the default theme (DIU's office page, plus the Head's message, services and sections)
    $this->registrar->update(['head_message' => ['en' => '<p>Welcome to the Registrar Office.</p>'], 'office_hours' => ['en' => 'Sun–Thu 9am–5pm']]);
    $this->get($this->registrar->url())->assertOk()
        ->assertSeeInOrder(['Registrar Office', 'Browse by Section (01)', 'Overview', 'Quick Links', 'Records and certificates.', 'Building 1, Level 3',
            'registrar@example.edu', 'Sun–Thu 9am–5pm', 'Welcome to the Registrar Office.', 'Services', 'Transcript issue', 'Student ID card', '7 working days', 'Office Team', 'Ms. Akter'])
        ->assertSee('Registrar Office - ', false);
});

it('shows the whole team on /office/{slug}/team, the first few on the office page; none, no page (FR-OFF-05)', function () {
    foreach (range(1, 5) as $i) {
        app(AssignmentEngine::class)->assign(ManualPerson::create(['name' => ['en' => "Officer {$i}"], 'designation' => ['en' => 'Officer']]), $this->registrar, 'team');
    }

    $this->get($this->registrar->url())->assertOk()->assertSee('View Full Team')->assertSee($this->registrar->url().'/team', false)
        ->assertSee('Officer 3')->assertDontSee('Officer 4');           // 4 on the page: Ms. Akter + 3
    $this->get($this->registrar->url().'/team')->assertOk()
        ->assertSeeInOrder(['Office Team Members', 'Ms. Akter', 'Officer 1', 'Officer 5']);

    $empty = Office::create(['name' => ['en' => 'Library Office']]);
    $this->get($empty->url().'/team')->assertNotFound();
    $this->get($empty->url())->assertOk()->assertDontSee('View Full Team');
});

it('shows the office\'s FAQs, the first five, with a link to all (FR-OFF-10)', function () {
    foreach (range(1, 6) as $i) {
        $faq = Faq::factory()->create(['question' => ['en' => "Question {$i}?"], 'answer' => ['en' => "<p>Answer {$i}<script>x()</script></p>"]]);
        app(RelationEngine::class)->sync($faq, [$this->registrar->getMorphClass().':'.$this->registrar->id], mainSite: false, user: null);
    }

    $this->get($this->registrar->url())->assertOk()
        ->assertSeeInOrder(['Frequently Asked Questions', 'All FAQs', 'Question 1?', 'Answer 1', 'Question 5?'])
        ->assertDontSee('Question 6?')->assertDontSee('<script>x()', false)
        ->assertSee($this->registrar->url().'/faq', false);
});

it('takes Quick Links from the office, else Settings › Offices; an empty homepage still shows the office page', function () {
    $this->get($this->registrar->url())->assertSeeInOrder(['Quick Links', 'Notice', 'Tuition Fees', 'Waiver Calculator'], false);   // DIU's defaults

    settings()->saveFor(new OfficeSettings, ['page_quick_links' => [['label' => ['en' => 'Exam Schedule'], 'url' => '/exams']]]);
    $this->get($this->registrar->url())->assertSee('Exam Schedule')->assertDontSee('Waiver Calculator');

    $this->actingAs(superAdmin());
    Livewire::test(EditOffice::class, ['record' => $this->registrar->getRouteKey()])
        ->fillForm(['quick_links' => [['label' => ['en' => 'Certificate Verification'], 'url' => 'https://verify.example.edu']]])
        ->call('save')->assertHasNoFormErrors();
    $this->get($this->registrar->url())->assertSee('Certificate Verification')->assertSee('https://verify.example.edu', false)->assertDontSee('Exam Schedule');

    // A homepage made in the builder but with no sections yet: the office page, not a blank one
    app(Homepages::class)->create($this->registrar->getMorphClass().':'.$this->registrar->id);
    $this->get($this->registrar->url())->assertOk()->assertSee('Records and certificates.');
});

it('shows every office\'s services and who to contact (FR-OFF-06/07)', function () {
    $this->get('/services')->assertOk()->assertSeeInOrder(['Registrar Office', 'Transcript issue', '500.00', 'Ms. Akter']);
    $this->get('/support-contacts')->assertOk()->assertSeeInOrder(['ID card problem after admission', 'Registrar Office', 'Room 301', 'Ms. Akter']);
});

it('gives a new office its own counters: services and team (FR-OFF-14)', function () {
    $stats = collect(app(Statistics::class)->forOwner($this->registrar->getMorphClass().':'.$this->registrar->id))->pluck('display', 'label');
    expect($stats->get('Services'))->toBe('1')->and($stats->get('Team members'))->toBe('1');
});

it('lets an office user manage their own office\'s services, not another\'s (BRL-OFF-03)', function () {
    $user = makeUser(['ViewAny:Office', 'View:Office', 'Update:Office', EntityPermissions::key('Manage', 'Office', $this->registrar->id)]);
    $this->actingAs($user);

    $this->get(OfficeResource::getUrl('edit', ['record' => $this->registrar]))->assertOk();
    Livewire::test(ServicesRelationManager::class, ['ownerRecord' => $this->registrar, 'pageClass' => EditOffice::class])
        ->assertSee('Transcript issue')
        ->callTableAction('create', data: ['title' => ['en' => 'Certificate verification'], 'is_active' => true])
        ->assertHasNoTableActionErrors();
    expect($this->registrar->services()->count())->toBe(2);

    expect(SupportContactsRelationManager::canViewForRecord($this->transport, EditOffice::class))->toBeFalse();
});

it('opens the office editor for an Admin with its lists', function () {
    $this->actingAs(superAdmin());
    $this->get(OfficeResource::getUrl('index'))->assertOk()->assertSee('Registrar Office');
    $this->get(OfficeResource::getUrl('edit', ['record' => $this->registrar]))->assertOk()
        ->assertSee('Services')->assertSee('Who to contact')->assertSee('Sections');
});
