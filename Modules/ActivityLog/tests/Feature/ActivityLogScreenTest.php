<?php

/*
| Phase 2.3 — Activity Log (C-04): screen, record Activity tab, export, read-only,
| retention cleanup (FR-LOG-04..09, FR-GEN-83, BR-LOG-05/06; Test Plan TC-DATA-01/02/02a).
*/

use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\ActivityLog\Filament\Resources\Activities\ActivityResource;
use Modules\ActivityLog\Filament\Resources\Activities\Pages\ListActivities;
use Modules\ActivityLog\Filament\Resources\Activities\Pages\ViewActivity;
use Modules\Faq\Filament\Resources\Faqs\Pages\EditFaq;
use Modules\Faq\Models\Faq;
use Platform\Activity\Activity;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Modules\ModulePermissions;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    app(ModulePermissions::class)->sync('ActivityLog');
    app(ModulePermissions::class)->sync('Faq');
});

it('offers only "view any" and "view" in the role editor', function () {
    expect(Permission::where('name', 'like', '%:Activity')->pluck('name')->sort()->values()->all())
        ->toBe(['View:Activity', 'ViewAny:Activity']);
});

it('shows the log to users with permission only', function () {
    $this->actingAs(makeUser())->get(ActivityResource::getUrl('index'))->assertForbidden();
    $this->actingAs(makeUser(['ViewAny:Activity']))->get(ActivityResource::getUrl('index'))->assertOk();
});

it('lists, filters and shows changes', function () {
    $editor = makeUser(['ViewAny:Activity', 'View:Activity']);
    $this->actingAs($editor);

    $faq = Faq::factory()->create(['question' => 'Old?']);
    $faq->update(['question' => 'New?']);
    $update = Activity::where('event', 'updated')->where('subject_id', $faq->id)->firstOrFail();
    $created = Activity::where('event', 'created')->where('subject_id', $faq->id)->firstOrFail();

    Livewire::test(ListActivities::class)
        ->assertCanSeeTableRecords([$update, $created])
        ->filterTable('event', ['updated'])
        ->assertCanSeeTableRecords([$update])
        ->assertCanNotSeeTableRecords([$created]);

    Livewire::test(ListActivities::class)
        ->filterTable('module_slug', 'faq')
        ->assertCanSeeTableRecords([$update]);

    Livewire::test(ViewActivity::class, ['record' => $update->getRouteKey()])
        ->assertSee('Old?')
        ->assertSee('New?');
});

it('exports the filtered log as CSV (FR-LOG-05)', function () {
    $this->actingAs(makeUser(['ViewAny:Activity']));
    Faq::factory()->create();

    Livewire::test(ListActivities::class)
        ->callAction('exportCsv')
        ->assertFileDownloaded();
});

it('can never be changed or deleted, not even by a Super Admin (FR-LOG-09)', function () {
    Faq::factory()->create();
    $entry = Activity::firstOrFail();
    $boss = superAdmin();

    expect($boss->can('update', $entry))->toBeFalse()
        ->and($boss->can('delete', $entry))->toBeFalse()
        ->and(ActivityResource::canCreate())->toBeFalse();
});

it('shows a record\'s history on its edit page (FR-GEN-83)', function () {
    $faq = Faq::factory()->create(['question' => 'First?']);
    $faq->update(['question' => 'Second?']);

    expect(ActivityRelationManager::canViewForRecord($faq, EditFaq::class))->toBeFalse();   // guest

    $this->actingAs(makeUser(['ViewAny:Activity', 'Update:Faq']));
    expect(ActivityRelationManager::canViewForRecord($faq, EditFaq::class))->toBeTrue();

    Livewire::test(ActivityRelationManager::class, ['ownerRecord' => $faq, 'pageClass' => EditFaq::class])
        ->assertCanSeeTableRecords($faq->activitiesAsSubject)
        ->assertSee('Second?');
});

it('keeps at least 60 days and logs each cleanup (BR-LOG-05/06)', function () {
    config(['activitylog.clean_after_days' => 30]);   // too short: 60 applies

    foreach ([70, 61, 59, 1] as $days) {
        $this->travelTo(now()->subDays($days));
        activity('model')->event('updated')->log("old {$days}");
        $this->travelBack();
    }

    $this->artisan('uwms:activity:clean')->assertSuccessful();

    expect(Activity::where('description', 'like', 'old %')->pluck('description')->sort()->values()->all())->toBe(['old 1', 'old 59'])
        ->and(Activity::where('event', 'log_cleanup')->first()->properties['deleted'])->toBe(2)
        ->and(Activity::where('event', 'log_cleanup')->first()->properties['retention_days'])->toBe(60);
});
