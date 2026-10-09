<?php

/*
| Phase 1A — data foundation (SAD Part A §6, DB-01 §1, Test Plan TC-PUB / TC-DATA).
*/

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Faq\Models\Faq;
use Modules\Office\Models\Office;
use Nwidart\Modules\Facades\Module;
use Platform\Activity\Activity;
use Platform\Exceptions\ParentHasActiveChildren;
use Platform\Modules\ModulePermissions;
use Spatie\Permission\Models\Permission;
use Tests\Fixtures\Models\FixtureChild;
use Tests\Fixtures\Models\FixtureParent;

afterEach(fn () => Module::find('Faq')->enable());

function columnInfo(string $table, string $column): ?object
{
    return DB::selectOne(
        'SELECT DATA_TYPE AS type, IS_NULLABLE AS nullable, COLUMN_DEFAULT AS dflt, GENERATION_EXPRESSION AS expr
         FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column],
    );
}

// ─── platformColumns() ──────────────────────────────────────────────────────

it('platformColumns() adds the standard columns, active_key and IX-5', function () {
    expect(Schema::hasColumns('faq_faqs', [
        'is_active', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at', 'active_key',
    ]))->toBeTrue();

    expect(columnInfo('faq_faqs', 'active_key')->expr)->toContain('deleted_at');

    $indexes = collect(Schema::getIndexes('faq_faqs'))->pluck('columns')->all();
    expect($indexes)->toContain(['is_active', 'deleted_at']);
});

it('users is a business table with the DB-01 columns', function () {
    expect(Schema::hasColumns('users', [
        'username', 'phone', 'locale', 'timezone', 'invited_at', 'last_login_at', 'last_login_ip',
        'is_active', 'created_by', 'deleted_at', 'active_key',
    ]))->toBeTrue();
});

it('IX-8: unique keys ignore soft-deleted rows through active_key', function () {
    $parent = FixtureParent::create(['name' => 'P']);
    $old = FixtureChild::create(['parent_id' => $parent->id, 'name' => 'Old', 'code' => 'CSE']);

    expect(fn () => FixtureChild::create(['parent_id' => $parent->id, 'name' => 'Dup', 'code' => 'CSE']))
        ->toThrow(QueryException::class);

    $old->delete();

    $new = FixtureChild::create(['parent_id' => $parent->id, 'name' => 'New', 'code' => 'CSE']);
    expect($new->exists)->toBeTrue();
});

// ─── HasActiveFlag, Blameable, SoftDeletes ──────────────────────────────────

it('new records are active by default and can be deactivated', function () {
    $faq = Faq::factory()->create();

    expect($faq->fresh()->is_active)->toBeTrue();

    $faq->deactivate();

    expect(Faq::active()->count())->toBe(0)
        ->and(Faq::inactive()->count())->toBe(1);
});

it('Blameable fills created_by, updated_by, deleted_by and clears deleted_by on restore', function () {
    $author = User::factory()->create();
    $editor = User::factory()->create();

    $this->actingAs($author);
    $faq = Faq::factory()->create();
    expect($faq->created_by)->toBe($author->id)->and($faq->updated_by)->toBe($author->id);

    $this->actingAs($editor);
    $faq->update(['question' => 'Changed?']);
    expect($faq->fresh()->updated_by)->toBe($editor->id)
        ->and($faq->fresh()->created_by)->toBe($author->id);

    $faq->delete();
    expect(Faq::withTrashed()->find($faq->id)->deleted_by)->toBe($editor->id);

    $faq->restore();
    expect($faq->fresh()->deleted_by)->toBeNull();
});

// ─── frontendVisible() ─────────────────────────────────────────────────────

it('frontendVisible() hides inactive, scheduled and deleted records (FR-GEN-12)', function () {
    $visible = Faq::factory()->create(['published_at' => now()->subDay()]);
    $noDate = Faq::factory()->create(['published_at' => null]);
    Faq::factory()->create(['is_active' => false]);
    Faq::factory()->create(['published_at' => now()->addDay()]);
    Faq::factory()->create()->delete();

    expect(Faq::frontendVisible()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$visible->id, $noDate->id])->sort()->values()->all());

    expect(Faq::scheduled()->count())->toBe(1);
});

it('frontendVisible() hides expired records (HasExpiry)', function () {
    $parent = FixtureParent::create(['name' => 'P']);
    $live = FixtureChild::create(['parent_id' => $parent->id, 'name' => 'Live', 'code' => 'A', 'expires_at' => now()->addDay()]);
    FixtureChild::create(['parent_id' => $parent->id, 'name' => 'Expired', 'code' => 'B', 'expires_at' => now()->subMinute()]);

    expect(FixtureChild::frontendVisible()->pluck('id')->all())->toBe([$live->id]);
});

it('frontendVisible() returns nothing when the owning module is inactive', function () {
    Faq::factory()->count(2)->create();

    expect(Faq::frontendVisible()->count())->toBe(2);

    Module::find('Faq')->disable();

    expect(Faq::frontendVisible()->count())->toBe(0)
        ->and(Faq::count())->toBe(2);   // data is kept (FR-MODM-09)
});

// ─── HasSortOrder ──────────────────────────────────────────────────────────

it('new records go to the end of the sort order', function () {
    $first = Faq::factory()->create();
    $second = Faq::factory()->create();

    expect($second->sort_order)->toBe($first->sort_order + 1)
        ->and(Faq::ordered()->pluck('id')->all())->toBe([$first->id, $second->id]);
});

// ─── GuardsParentDelete ────────────────────────────────────────────────────

it('a parent with active children cannot be deleted (FR-GEN-08)', function () {
    $parent = FixtureParent::create(['name' => 'Faculty']);
    $child = FixtureChild::create(['parent_id' => $parent->id, 'name' => 'CSE', 'code' => 'CSE']);

    expect(fn () => $parent->delete())->toThrow(ParentHasActiveChildren::class);
    expect($parent->activeChildren())->toBe(['children' => ['CSE']]);

    $child->deactivate();

    expect($parent->delete())->toBeTrue();
});

// ─── Activity log ──────────────────────────────────────────────────────────

it('logs create, update (changed fields only), delete, restore with module, ip and causer', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $faq = Faq::factory()->create(['question' => 'Q1?']);
    $faq->update(['question' => 'Q2?']);
    $faq->delete();
    $faq->restore();

    $log = Activity::where('subject_type', $faq->getMorphClass())->where('subject_id', $faq->id)->orderBy('id')->get();

    expect($log->pluck('event')->all())->toBe(['created', 'updated', 'deleted', 'restored']);

    $update = $log[1];
    expect($update->attribute_changes['attributes'])->toBe(['question' => 'Q2?'])
        ->and($update->attribute_changes['old'])->toBe(['question' => 'Q1?'])
        ->and($update->module_slug)->toBe('faq')
        ->and($update->ip_address)->toBe('127.0.0.1')
        ->and($update->causer_id)->toBe($user->id)
        ->and($update->log_name)->toBe('model');
});

it('logs activated / deactivated and force_deleted under their own names', function () {
    $faq = Faq::factory()->create();

    $faq->deactivate();
    $faq->activate();
    $faq->forceDelete();

    expect(Activity::where('subject_id', $faq->id)->where('subject_type', $faq->getMorphClass())->orderBy('id')->pluck('event')->all())
        ->toBe(['created', 'deactivated', 'activated', 'force_deleted']);
});

it('never logs passwords or bookkeeping-only changes', function () {
    $user = User::factory()->create(['password' => 'secret-123']);
    $created = Activity::where('subject_type', $user->getMorphClass())->where('subject_id', $user->id)->first();

    expect($created->attribute_changes['attributes'])->not->toHaveKeys(['password', 'remember_token']);

    $user->update(['password' => 'another-456']);   // only a hidden field changed → no entry
    expect(Activity::where('subject_type', $user->getMorphClass())->where('subject_id', $user->id)->count())->toBe(1);
});

it('activity log entries cannot be changed (FR-LOG-09)', function () {
    Faq::factory()->create();
    $entry = Activity::first();

    expect(fn () => $entry->update(['description' => 'tampered']))->toThrow(LogicException::class);
});

// ─── Users & permissions ───────────────────────────────────────────────────

it('an inactive user cannot use the admin panel (FR-USER-05)', function () {
    $user = User::factory()->create();
    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();

    $user->deactivate();
    expect($user->fresh()->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('module permissions record their module and kind; entity permissions are mapped (P-10)', function () {
    app(ModulePermissions::class)->sync('Faq');
    expect(Permission::where('name', 'Update:Faq')->first())
        ->module_slug->toBe('faq')
        ->kind->toBe('resource');

    $office = Office::create(['name' => 'Registrar Office']);
    $permission = Permission::where('name', "Manage:Office#{$office->id}")->first();

    expect($permission->kind)->toBe('entity')
        ->and(DB::table('core_entity_permissions')->where('permission_id', $permission->id)->first())
        ->entity_id->toBe($office->id)
        ->action->toBe('manage');

    $office->forceDelete();
    expect(Permission::where('name', "Manage:Office#{$office->id}")->exists())->toBeFalse()
        ->and(DB::table('core_entity_permissions')->count())->toBe(0);
});
