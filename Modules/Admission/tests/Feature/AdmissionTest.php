<?php

/*
| Phase 3 — M-09 Admission (SRS FR-ADM-01..16, DB-03 v1.5 §10): circulars and their dates,
| the result import (check, errors, bulk save) and lookup (published batches, messages, rate
| limit), subscribers, page blocks on the /admission page, module pages, the Admission Office.
*/

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Admission\Database\Seeders\AdmissionDatabaseSeeder;
use Modules\Admission\Filament\Resources\Circulars\CircularResource;
use Modules\Admission\Filament\Resources\Circulars\Pages\CreateCircular;
use Modules\Admission\Filament\Resources\ContactGroups\AdmissionContactGroupResource;
use Modules\Admission\Filament\Resources\Highlights\AdmissionHighlightResource;
use Modules\Admission\Filament\Resources\ResultBatches\Pages\EditResultBatch;
use Modules\Admission\Filament\Resources\ResultBatches\RelationManagers\ResultsRelationManager;
use Modules\Admission\Filament\Resources\ResultBatches\ResultBatchResource;
use Modules\Admission\Filament\Resources\Steps\AdmissionStepResource;
use Modules\Admission\Filament\Resources\Subscribers\AdmissionSubscriberResource;
use Modules\Admission\Livewire\ResultLookup;
use Modules\Admission\Livewire\SubscribeForm;
use Modules\Admission\Models\AdmissionContactGroup;
use Modules\Admission\Models\AdmissionHighlight;
use Modules\Admission\Models\AdmissionResult;
use Modules\Admission\Models\AdmissionStep;
use Modules\Admission\Models\AdmissionSubscriber;
use Modules\Admission\Models\Circular;
use Modules\Admission\Models\ResultBatch;
use Modules\Admission\Notifications\ConfirmSubscription;
use Modules\Admission\Support\ResultImporter;
use Modules\Menu\Models\Menu;
use Modules\Menu\Support\MenuEditor;
use Modules\Menu\Support\MenuLinks;
use Modules\Page\Models\Page;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Context\UrlRules;
use Platform\Modules\ModulePermissions;
use Platform\Seo\Sitemap;
use Platform\Taxonomy\Vocabularies;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('Admission');
    RateLimiter::clear('admission-result:127.0.0.1');

    $this->fall = Circular::factory()->create(['title' => ['en' => 'Fall 2026 (Tri Semester)'], 'session_label' => 'Fall 2026', 'sort_order' => 1]);
    $this->summer = Circular::factory()->closed()->create(['title' => ['en' => 'Summer 2026'], 'sort_order' => 2]);

    $this->batch = ResultBatch::create([
        'name' => ['en' => 'Summer 2026'], 'session_label' => 'Summer 2026', 'is_active' => true,
        'messages' => [
            'selected' => ['en' => 'Dear {name}, congratulations! You are selected.'],
            'not_selected' => ['en' => 'Dear {name}, we regret to inform you.'],
            'waiting' => ['en' => 'Dear {name}, you are on the waiting list.'],
            'absent' => ['en' => 'Dear {name}, you were absent.'],
            'not_found' => ['en' => 'Sorry, no result with this serial.'],
        ],
    ]);
});

function resultFile(array $lines): string
{
    $path = tempnam(sys_get_temp_dir(), 'adm').'.csv';
    $out = fopen($path, 'w');
    foreach ($lines as $line) {
        fputcsv($out, $line);
    }
    fclose($out);

    return $path;
}

it('keeps a circular\'s dates, closes it after the last day to apply and counts down', function () {
    expect($this->fall->isClosed())->toBeFalse()->and($this->summer->isClosed())->toBeTrue()
        ->and($this->fall->nextDate()['key'])->toBe('apply_ends_on')
        ->and(Circular::query()->running()->pluck('id')->all())->toBe([$this->fall->id]);

    $this->actingAs(superAdmin());
    Livewire::test(CreateCircular::class)
        ->fillForm(['title' => ['en' => 'Spring 2027'], 'session_label' => 'Spring 2027', 'apply_ends_on' => today()->addMonths(4)->toDateString(), 'web_address' => 'spring-2027'])
        ->call('create')->assertHasNoFormErrors();
    expect(Circular::query()->latest('id')->first()->url())->toBe('/admission/circulars/spring-2027');
});

it('shows circulars on their pages, open first, with old addresses redirected', function () {
    $this->get('/admission/circulars')->assertOk()->assertSeeInOrder(['Fall 2026 (Tri Semester)', 'Open', 'Summer 2026', 'Closed']);
    $this->get($this->fall->url())->assertOk()->assertSee('Last day to apply')->assertSee('Fall 2026');

    $old = $this->fall->url();
    $this->fall->changeSlug('en', 'fall-2026');
    $this->get($old)->assertRedirect('/admission/circulars/fall-2026');

    $this->fall->update(['is_active' => false]);
    $this->get('/admission/circulars/fall-2026')->assertNotFound();
});

it('checks a result file, reports its problems by line and imports the good rows in bulk (FR-ADM-05)', function () {
    $file = resultFile([
        ['Program', 'Application Serial', 'Student Name', 'Result'],
        ['Bachelor of Business Administration', '26211N00518', 'Md. Sani Ahmmed', 'Selected 1st Merit List'],
        ['LL.B. (Hons.)', '26211N00519', 'Ayesha Rahman', 'Not Selected'],
        ['LL.B. (Hons.)', '26211N00520', 'Karim', 'Waiting 2nd Merit List'],
        ['LL.B. (Hons.)', '26211N00521', 'Rina', 'Absent'],
        ['LL.B. (Hons.)', '', 'No Serial', 'Selected'],
        ['LL.B. (Hons.)', '26211N00522', 'Odd', 'Passed?'],
        ['LL.B. (Hons.)', '26211N00518', 'Twice', 'Selected'],
    ]);

    $checked = app(ResultImporter::class)->check($file);
    expect($checked['total'])->toBe(7)->and($checked['rows'])->toHaveCount(4)
        ->and($checked['errors'])->toHaveCount(3)
        ->and($checked['errors'][0])->toContain('Line 6')
        ->and($checked['errors'][2])->toContain('26211N00518');

    expect(app(ResultImporter::class)->save($this->batch, $checked['rows']))->toBe(4);
    $first = AdmissionResult::query()->where('application_serial', '26211N00518')->sole();
    expect($first->result)->toBe('selected')->and($first->result_label)->toBe('Selected 1st Merit List')
        ->and(AdmissionResult::query()->where('application_serial', '26211N00520')->value('result'))->toBe('waiting')
        ->and($this->batch->fresh()->imported_rows)->toBe(4);

    app(ResultImporter::class)->save($this->batch, array_slice($checked['rows'], 0, 1));   // a new import replaces the old
    expect($this->batch->results()->count())->toBe(1);

    expect(app(ResultImporter::class)->check(resultFile([['Foo', 'Bar'], ['1', '2']]))['errors'][0])->toContain('first row');
});

it('looks a result up by serial with the batch\'s message, and nothing more (FR-ADM-06, BRL-ADM-02, AC-ADM-02)', function () {
    AdmissionResult::create(['batch_id' => $this->batch->id, 'application_serial' => 'A100', 'student_name' => 'Ayesha Rahman', 'result' => 'selected', 'result_label' => 'Selected 1st Merit List']);

    Livewire::test(ResultLookup::class)->set('serial', 'A100')->call('lookup')
        ->assertSet('answer.status', 'found')
        ->assertSee('Ayesha Rahman')->assertSee('Selected 1st Merit List')
        ->assertSee('Dear Ayesha Rahman, congratulations! You are selected.');

    Livewire::test(ResultLookup::class)->set('serial', 'NOPE')->call('lookup')
        ->assertSet('answer.status', 'not_found')->assertSee('Sorry, no result with this serial.');

    $this->batch->update(['is_active' => false]);
    Livewire::test(ResultLookup::class)->set('serial', 'A100')->call('lookup')
        ->assertSet('answer.status', 'unpublished')->assertSee('Result not yet published.');
});

it('limits lookups per visitor and ignores the trap field (FR-ADM-07)', function () {
    $lookup = Livewire::test(ResultLookup::class)->set('serial', 'X1');
    foreach (range(1, ResultLookup::PER_MINUTE) as $try) {
        $lookup->call('lookup')->assertSet('answer.status', 'not_found');
    }
    $lookup->call('lookup')->assertSet('answer.status', 'too_many');

    RateLimiter::clear('admission-result:127.0.0.1');
    Livewire::test(ResultLookup::class)->set('serial', 'X1')->set('website', 'spam')->call('lookup')->assertSet('answer.message', null);
});

it('subscribes once per contact and topic, confirms an email, and unsubscribes by link (FR-ADM-09)', function () {
    Notification::fake();

    Livewire::test(SubscribeForm::class)->set('contact', 'Student@Example.com')->call('subscribe')->assertSee('Please confirm');
    Livewire::test(SubscribeForm::class)->set('contact', 'student@example.com')->call('subscribe');
    Livewire::test(SubscribeForm::class)->set('channel', 'whatsapp')->set('contact', '+880 1674-797580')->call('subscribe')->assertHasNoErrors();

    $email = AdmissionSubscriber::query()->where('channel', 'email')->sole();
    expect($email->contact)->toBe('student@example.com')
        ->and(AdmissionSubscriber::query()->where('channel', 'whatsapp')->value('contact'))->toBe('+8801674797580');
    Notification::assertSentOnDemand(ConfirmSubscription::class);

    $this->get(URL::signedRoute('admission.subscriber.verify', $email))->assertOk();
    expect($email->fresh()->verified_at)->not->toBeNull();
    $this->get(route('admission.subscriber.unsubscribe', $email))->assertForbidden();   // unsigned
    $this->get(URL::signedRoute('admission.subscriber.unsubscribe', $email))->assertOk();
    expect($email->fresh()->unsubscribed_at)->not->toBeNull();
});

it('builds the /admission page with the module blocks and a sidebar menu on install (FR-ADM-13/15)', function () {
    app(RoleCatalog::class)->sync();
    Vocabularies::ensureTerms(AdmissionHighlight::CATEGORY, [['name' => ['en' => 'Facility'], 'slug' => 'facility']]);
    $facility = (int) array_key_first(app(Vocabularies::class)->options(AdmissionHighlight::CATEGORY));
    AdmissionHighlight::create(['title' => ['en' => 'Tuition Fees for Local Students'], 'url' => '/tuition-fees', 'is_active' => true])->syncTerms(AdmissionHighlight::CATEGORY, [$facility]);
    AdmissionStep::create(['title' => ['en' => 'Apply online'], 'description' => ['en' => 'Fill in the form.'], 'is_active' => true]);
    $group = AdmissionContactGroup::create(['name' => ['en' => 'Local Admission'], 'is_active' => true]);
    app(AssignmentEngine::class)->assign(ManualPerson::create(['name' => ['en' => 'Mr. Siddiqul Alam'], 'designation' => ['en' => 'Deputy Director'], 'phone' => '+8801713493050']), $group, AdmissionContactGroup::ROLE);
    settings()->save('admission', ['apply_url' => 'https://admission.example.edu/']);

    (new AdmissionDatabaseSeeder)->run();
    (new AdmissionDatabaseSeeder)->run();   // twice: still one page
    $page = Page::query()->sole();
    expect($page->url())->toBe('/admission')->and($page->sidebar_menu_id)->not->toBeNull();

    $this->get('/admission')->assertOk()
        ->assertSeeInOrder(['Fall 2026 (Tri Semester)', 'Apply Now', 'Facility', 'Tuition Fees for Local Students', 'Apply online', 'Local Admission', 'Mr. Siddiqul Alam', 'Get admission updates'])
        ->assertSee('https://admission.example.edu/', false)
        ->assertDontSee('Summer 2026');   // closed: not in "running only"
    $this->get('/admission/result')->assertOk()->assertSee('Admission Test Result');

    // The default theme: DIU's /admission — "Browse by Section" from the sidebar menu, the page's Quick Links, the blocks in
    // the theme's look (not inside the page-text wrapper), the circulars as DIU's notice card
    app(MenuEditor::class)->save(Menu::query()->findOrFail($page->sidebar_menu_id), [
        ['type' => 'url', 'url' => '/admission', 'title' => ['en' => 'Admission Information'], 'is_active' => true, 'children' => []],
        ['type' => 'url', 'url' => '/tuition-fees', 'title' => ['en' => 'Tuition Fees'], 'open_in_new_tab' => true, 'is_active' => true, 'children' => []],
    ]);
    $page->update(['excerpt' => ['en' => 'Browse 51 programs.'], 'quick_links' => [['label' => ['en' => 'Waiver Calculator'], 'url' => '/waiver-calculator']]]);
    $this->get('/admission')->assertOk()
        ->assertSeeInOrder(['Browse 51 programs.', 'Browse by Section (02)', 'Admission Information', 'Tuition Fees', 'Quick Links', 'Waiver Calculator', 'List of Admission Announce Notices', 'Fall 2026 (Tri Semester)', 'Last day to apply'])
        ->assertSee('aria-current="page"', false)
        ->assertDontSee('<diu-rich-html', false);
});

it('offers its pages to menus, keeps them from child pages and lists circulars in search and the sitemap (FR-ADM-16)', function () {
    expect(app(MenuLinks::class)->modulePages())->toHaveKeys(['page:admission.result', 'page:admission.circulars'])
        ->and(UrlRules::modulePageClash('/admission/result'))->not->toBeNull()
        ->and(UrlRules::modulePageClash('/admission/schedule'))->toBeNull()
        ->and(collect(app(Sitemap::class)->entries())->pluck('loc')->implode(' '))->toContain($this->fall->url())
        ->and($this->fall->toSearchDocument('en'))->toMatchArray(['title' => 'Fall 2026 (Tri Semester)']);
});

it('opens every admin screen of Academic › Admission', function () {
    $this->actingAs(superAdmin());
    AdmissionContactGroup::create(['name' => ['en' => 'Local Admission']]);
    AdmissionResult::create(['batch_id' => $this->batch->id, 'application_serial' => 'A100', 'student_name' => 'Ayesha', 'result' => 'waiting']);

    foreach ([CircularResource::class, AdmissionHighlightResource::class, AdmissionStepResource::class, AdmissionContactGroupResource::class, ResultBatchResource::class, AdmissionSubscriberResource::class] as $resource) {
        $this->get($resource::getUrl('index'))->assertOk();
    }
    $this->get(CircularResource::getUrl('edit', ['record' => $this->fall]))->assertOk();
    $this->get(AdmissionContactGroupResource::getUrl('edit', ['record' => AdmissionContactGroup::query()->first()]))->assertOk();
    $this->get(ResultBatchResource::getUrl('edit', ['record' => $this->batch]))->assertOk()->assertSee('Import results');

    Livewire::test(ResultsRelationManager::class, ['ownerRecord' => $this->batch, 'pageClass' => EditResultBatch::class])
        ->assertSee('A100')->assertSee('Waiting');
});

it('gives the Admission Office the admission permissions once, and nobody else', function () {
    (new AdmissionDatabaseSeeder)->run();
    $office = Role::findByName('Admission Office', 'web');
    expect($office->hasPermissionTo('Update:ResultBatch'))->toBeTrue()->and($office->hasPermissionTo('ViewAny:AdmissionSubscriber'))->toBeTrue();

    $office->revokePermissionTo('Delete:Circular');
    (new AdmissionDatabaseSeeder)->run();
    expect($office->fresh()->hasPermissionTo('Delete:Circular'))->toBeFalse();

    $this->actingAs(makeUser([]));
    $this->get(CircularResource::getUrl('index'))->assertForbidden();

    $user = makeUser([]);
    $user->assignRole('Admission Office');
    $this->actingAs($user);
    $this->get(CircularResource::getUrl('index'))->assertOk()->assertSee('Fall 2026 (Tri Semester)');
});

it('shows DIU\'s result, contact and circular pages, with the office from Settings and HTML messages made safe (Phase 4.7b-3)', function () {
    app(RoleCatalog::class)->sync();
    settings()->save('admission', ['helpline' => '+8809617901212', 'email' => 'admission@example.edu', 'apply_url' => 'https://admission.example.edu/',
        'map_url' => 'https://www.google.com/maps/embed?pb=diu'], translatable: ['office_name', 'office_address']);
    $this->batch->update(['messages' => ['selected' => ['en' => '<p>Dear {name}, you are selected for {program} ({serial}).</p><script>alert(1)</script>']]]);
    AdmissionResult::create(['batch_id' => $this->batch->id, 'program_label' => 'BSc in CSE', 'application_serial' => 'A100', 'student_name' => 'Ayesha Rahman', 'result' => 'selected']);

    // the result: name, serial, program, the message as safe HTML with its placeholders filled
    Livewire::test(ResultLookup::class)->set('serial', 'A100')->call('lookup')
        ->assertSet('answer.tone', 'success')->assertSet('answer.program', 'BSc in CSE')
        ->assertSeeHtml('<p>Dear Ayesha Rahman, you are selected for BSc in CSE (A100).</p>')
        ->assertDontSeeHtml('<script>alert(1)</script>');

    $this->get('/admission/result')->assertOk()
        ->assertSeeInOrder(['Admission Test Result', 'Application Serial Number', 'Admission Information', 'Admission Contact', 'Need Assistance?', 'admission@example.edu', '+8809617901212']);

    $group = AdmissionContactGroup::create(['name' => ['en' => 'International Admission'], 'is_active' => true]);
    app(AssignmentEngine::class)->assign(ManualPerson::create(['name' => ['en' => 'Ms. Nadia'], 'designation' => ['en' => 'Counselor'], 'phone' => '+8801713000000']), $group, AdmissionContactGroup::ROLE);
    $this->get('/admission/contact')->assertOk()
        ->assertSeeInOrder(['Admission Contact', 'Daffodil International University', 'Admission Helpline', '+8809617901212', 'Apply Online', 'International Admission', 'Ms. Nadia', 'Counselor'])
        ->assertSee('https://www.google.com/maps/embed?pb=diu', false);

    settings()->save('admission', ['map_url' => 'https://evil.example/maps/embed?x']);
    $this->get('/admission/contact')->assertOk()->assertDontSee('evil.example', false);   // only Google Maps embeds

    $this->get('/admission/circulars')->assertOk()->assertSeeInOrder(['Fall 2026 (Tri Semester)', 'Open', 'Last day to apply', 'Summer 2026', 'Closed']);
    $this->get($this->fall->url())->assertOk()->assertSeeInOrder(['Fall 2026 (Tri Semester)', 'Last day to apply', 'Apply Now'])
        ->assertSee('https://admission.example.edu/', false);
});
