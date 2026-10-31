<?php

/*
| M-12 Office completed (SRS FR-OFF-01..14, DB-04 §3): profile per language, type, own page
| or contact card, sections / services / support contacts with their people, /offices,
| /services, /support-contacts, the microsite home, counters, office users managing their own.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Modules\Faq\Models\Faq;
use Modules\Homepage\Support\Homepages;
use Modules\Menu\Support\MenuEditor;
use Modules\Menu\Support\MenuTree;
use Modules\Notice\Models\Notice;
use Modules\Office\Database\Seeders\OfficeDatabaseSeeder;
use Modules\Office\Filament\Resources\Offices\OfficeResource;
use Modules\Office\Filament\Resources\Offices\Pages\EditOffice;
use Modules\Office\Filament\Resources\Offices\RelationManagers\ServicesRelationManager;
use Modules\Office\Filament\Resources\Offices\RelationManagers\SupportContactsRelationManager;
use Modules\Office\Models\Office;
use Modules\Office\Models\OfficeService;
use Modules\Office\Models\OfficeSupportContact;
use Modules\Office\Settings\OfficeSettings;
use Modules\Office\Support\OfficePage;
use Modules\Office\Support\SupportDirectory;
use Modules\Page\Blocks\PageBlocks;
use Modules\Page\Models\Page;
use Modules\Settings\Groups\GeneralSettings;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Context\SiteContext;
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

    // Under the name: the university's full name (Settings › General), else the site name
    settings()->saveFor(new GeneralSettings, ['site_name' => ['en' => 'DIU Website'], 'university_name' => ['en' => 'Daffodil International University']]);
    $this->get($this->registrar->url())->assertSee('Registrar Office - Daffodil International University');
    settings()->saveFor(new GeneralSettings, ['university_name' => null]);
    $this->get($this->registrar->url())->assertSee('Registrar Office - DIU Website');
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

it('shows highlights cards and Quick Service tiles entered with the office, and its own side menu (DIU registrar / proctor office)', function () {
    $this->actingAs(superAdmin());
    Livewire::test(EditOffice::class, ['record' => $this->registrar->getRouteKey()])
        ->fillForm([
            'highlights' => [['icon' => 'shield', 'title' => ['en' => 'Our Mission'], 'text' => ['en' => 'Fair and transparent procedures.']]],
            'quick_services' => [['icon' => 'folder', 'label' => ['en' => 'File Processing'], 'url' => '/office/registrar-office/file-processing'], ['icon' => '', 'label' => ['en' => 'Employee Application'], 'url' => 'https://empapp.example.edu']],
        ])
        ->call('save')->assertHasNoFormErrors();

    // The module's own page
    expect(moduleView('Office', 'microsite.home', ['entity' => $this->registrar->refresh()]))
        ->toContain('Our Mission')->toContain('Quick Service')->toContain('Employee Application');

    // DIU's look: cards, "About the Office" (no cover picture), then the tiles; outside links open in a new tab
    $html = $this->get($this->registrar->url())->assertOk()
        ->assertSeeInOrder(['Our Mission', 'Fair and transparent procedures.', 'About the Office', 'Records and certificates.', 'Quick Service', 'File Processing', 'Employee Application'])
        ->getContent();
    expect($html)->toMatch('#href="https://empapp\.example\.edu"\s+target="_blank"#');

    // A sidebar menu of its own is the side list; the header menu stays the primary one
    $editor = app(MenuEditor::class);
    $key = $this->registrar->getMorphClass().':'.$this->registrar->id;
    $editor->save($editor->create($key, 'sidebar'), [['title' => ['en' => 'Honour Board'], 'type' => 'url', 'url' => '/honour-board', 'is_active' => true]]);
    MenuTree::flush();
    $this->get($this->registrar->url())->assertSeeInOrder(['Browse by Section (02)', 'Overview', 'Honour Board']);
});

it('opens the office\'s pages, team and notice board in the office\'s frame, the one shown marked (DIU /registrar-office/{slug})', function () {
    $editor = app(MenuEditor::class);
    $key = $this->registrar->getMorphClass().':'.$this->registrar->id;
    $calendar = Page::factory()->ownedBy($this->registrar)->create(['title' => ['en' => 'Academic Calendar'], 'content' => ['en' => [['type' => 'html', 'data' => ['html' => '<p>Dates of the session.</p>']]]]]);
    $employee = Page::factory()->ownedBy($this->registrar)->create(['title' => ['en' => 'Employee Service'], 'content' => ['en' => []]]);
    Page::factory()->ownedBy($this->registrar)->create(['title' => ['en' => 'Recruitment'], 'parent_id' => $employee->id, 'excerpt' => ['en' => 'Jobs at the university.']]);
    $editor->save($editor->create($key, 'sidebar'), [
        ['title' => ['en' => 'Staff Directory'], 'type' => 'url', 'url' => $this->registrar->url().'/team', 'is_active' => true],
        ['title' => ['en' => 'Academic Calendar'], 'type' => 'url', 'url' => $calendar->url(), 'is_active' => true],
        ['title' => ['en' => 'Notice'], 'type' => 'url', 'url' => $this->registrar->url().'/notices', 'is_active' => true],
        ['title' => ['en' => 'Employee Application'], 'type' => 'url', 'url' => 'https://empapp.example.edu', 'is_active' => true],
    ]);
    MenuTree::flush();
    $current = fn (string $html): string => preg_match('#<a href="([^"]+)"[^>]*aria-current="page"#', $html, $m) ? $m[1] : '';

    // A page: the office hero and side list, its text in the card, the page marked; links leaving the site in a new tab only
    $html = $this->get($calendar->url())->assertOk()
        ->assertSeeInOrder(['Registrar Office', 'Browse by Section (05)', 'Overview', 'Staff Directory', 'Academic Calendar', 'Quick Links', 'Dates of the session.'])
        ->getContent();
    expect($current($html))->toBe(url($calendar->url()))
        ->and($html)->toMatch('#href="https://empapp\.example\.edu"\s+target="_blank"#')
        ->and($html)->not->toMatch('#href="'.preg_quote(url($calendar->url()), '#').'"\s+target="_blank"#');

    // A page with pages under it lists them as cards
    $this->get($employee->url())->assertOk()->assertSeeInOrder(['Employee Service', 'Recruitment', 'Jobs at the university.']);

    // The team and the notice board, marked in the same side list
    expect($current($this->get($this->registrar->url().'/team')->assertOk()->assertSeeInOrder(['Browse by Section (05)', 'Office Team Members', 'Ms. Akter'])->getContent()))
        ->toBe(url($this->registrar->url().'/team'));
    app(ModulePermissions::class)->sync('Notice');
    $notice = Notice::factory()->create(['title' => ['en' => 'Late registration for Fall 2026'], 'active_at' => now()->subDay()]);
    app(RelationEngine::class)->sync($notice, [$key], mainSite: false, user: null);
    expect($current($this->get($this->registrar->url().'/notices')->assertOk()->assertSeeInOrder(['Browse by Section (05)', 'Notices &amp; Announcements', 'Late registration for Fall 2026'], false)->getContent()))
        ->toBe(url($this->registrar->url().'/notices'));

    // The office page itself: Overview marked
    expect($current($this->get($this->registrar->url())->getContent()))->toBe(url($this->registrar->url()));
});

it('shows the Honour Board — the head now, then former heads with their terms — and the Contact from the office (DIU registrar-office)', function () {
    $head = ManualPerson::create(['name' => ['en' => 'Dr. Mostafa Kamal'], 'designation' => ['en' => 'Registrar'], 'is_active' => true]);
    $engine = app(AssignmentEngine::class);
    $engine->assign($head, $this->registrar, 'head', ['starts_on' => '2002-03-19', 'ends_on' => '2007-03-31', 'role_label' => ['en' => 'Registrar']]);
    $akter = ManualPerson::query()->get()->first(fn ($p) => $p->name === 'Ms. Akter');
    $engine->assign($akter, $this->registrar, 'head', ['starts_on' => '2022-10-15', 'role_label' => ['en' => 'Registrar (In charge)']]);

    $board = Page::factory()->ownedBy($this->registrar)->create(['title' => ['en' => 'Honour Board'], 'content' => ['en' => [['type' => 'office-heads', 'data' => ['intro' => 'Registrars since 2002.']]]]]);
    $this->get($board->url())->assertOk()
        ->assertSeeInOrder(['Honour Board', 'Registrars since 2002.', 'Serving now', 'Ms. Akter', 'Registrar (In charge)', 'Since 15 Oct 2022', 'Former heads', 'MK', 'Dr. Mostafa Kamal', '19 Mar 2002 – 31 Mar 2007', '5 years']);
    expect((new OfficePage($this->registrar))->team()->filter(fn ($p) => $p->name() === 'Ms. Akter'))->toHaveCount(1);   // head and team: once

    $contact = Page::factory()->ownedBy($this->registrar)->create(['title' => ['en' => 'Contact'], 'content' => ['en' => [['type' => 'office-contact', 'data' => []]]]]);
    $this->get($contact->url())->assertOk()
        ->assertSeeInOrder(['Get in Touch', 'Building 1, Level 3', 'registrar@example.edu', 'Who to contact', 'ID card problem after admission', 'Room 301', 'Ms. Akter'])
        ->assertSee('mailto:registrar@example.edu', false)->assertSee('tel:+8801847140000', false);

    // Outside an office's site the blocks show nothing
    View::share('context', SiteContext::site());
    expect(PageBlocks::render([['type' => 'office-heads', 'data' => []], ['type' => 'office-contact', 'data' => []]], 'en')->toHtml())->not->toContain('Dr. Mostafa Kamal')->not->toContain('Get in Touch');
});

it('shows every office\'s services and who to contact (FR-OFF-06/07)', function () {
    $this->get('/services')->assertOk()->assertSeeInOrder(['Registrar Office', 'Transcript issue', '500.00', 'Ms. Akter']);
    $this->get('/support-contacts')->assertOk()->assertSeeInOrder(['Ms. Akter', 'Officer', 'Registrar Office', 'Room 301', 'ID card problem after admission']);
});

it('shows the support contacts as DIU\'s directory: a card per person, filters by purpose and office (FR-OFF-07)', function () {
    // A purpose without people is a card of its office, with the office's phone and emails
    $this->transport->update(['email' => 'transport@example.edu, bus@example.edu']);
    OfficeSupportContact::create(['office_id' => $this->transport->id, 'purpose' => ['en' => 'Bus pass'], 'sort_order' => 1]);

    $this->get('/support-contacts')->assertOk()
        ->assertSeeInOrder(['Support Contact Directory', 'Connect with support staff', 'All Purposes', 'ID card problem after admission', 'Bus pass', 'All Offices', 'Registrar Office', 'Transport'])
        ->assertSeeInOrder(['Ms. Akter', 'tel:+8801700000001', 'ID card problem after admission', 'Transport', 'tel:+8801700000000', 'mailto:transport@example.edu', 'mailto:bus@example.edu', 'Bus pass'], false);

    // ?purpose / ?office open it filtered; unknown values are dropped
    $page = fn (array $query) => (new SupportDirectory(OfficeSupportContact::query()->with('office')->get(), $query))->filters();
    expect($page(['purpose' => 'Bus pass', 'office' => (string) $this->transport->id]))->toBe(['q' => '', 'purpose' => 'Bus pass', 'office' => (string) $this->transport->id])
        ->and($page(['purpose' => 'Nope', 'office' => '999', 'q' => ' id ']))->toBe(['q' => 'id', 'purpose' => '', 'office' => '']);

    // The module's own page (any theme without its version)
    $contacts = OfficeSupportContact::query()->with('office')->get();
    expect(moduleView('Office', 'support-contacts', ['contacts' => $contacts, 'page' => new SupportDirectory($contacts)]))
        ->toContain('Ms. Akter')->toContain('mailto:bus@example.edu')->toContain('Bus pass');

    // Title and text from Settings › Offices
    settings()->saveFor(new OfficeSettings, ['support_title' => ['en' => 'Who can help'], 'support_subtitle' => ['en' => 'Find the right person.']]);
    $this->get('/support-contacts')->assertOk()->assertSeeInOrder(['Who can help', 'Find the right person.'])->assertSee('<title>Who can help', false);
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
