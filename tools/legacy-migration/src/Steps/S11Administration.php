<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use Platform\Assignments\Assignment;
use Platform\Assignments\ManualPerson;

/**
 * S11 Administration (DB-04 v1.2 §4, §11.1): DIU's two staff lists become one — `admins`
 * (officers and management, their categories in DIU's order) and `staff` (support staff, not in
 * the directory). The legacy `office_id` is the employee ID, `unique_id` the old address.
 * Then the manual persons S08 / S10 made for admins are replaced by their member in every
 * assignment (one phone number for all places, AC-ADMN-01). Run after S08 and S10.
 */
class S11Administration extends Step
{
    /** @var list<string> legacy admin categories counted as management (the rest are officers) */
    public const MANAGEMENT = ['management', 'director', 'additional registrar', 'joint director'];

    public static function key(): string
    {
        return 'S11';
    }

    public static function title(): string
    {
        return 'Administration: categories, members, admins as members';
    }

    public static function requires(): array
    {
        return ['administration'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('admins')) {
            $this->report->review('No admins table in the legacy database: nothing to migrate.');

            return;
        }

        $order = 0;
        $adminCategories = [];
        foreach ($this->rows('admin_categories', fn ($q) => $q->whereNull('deleted_at'))->sortBy('serial') as $row) {
            $name = Normalizer::text($row->category_name);
            $adminCategories[$row->id] = $this->category('admin_categories', $row, $name,
                in_array(Str::lower($name), self::MANAGEMENT, true) ? 'management' : 'officer', true, ++$order, (bool) $row->publication_status)->id;
        }
        // DIU's /admin/{section} is not redirected: /admin is this system's admin panel
        $this->report->review('DIU\'s /admin/{section} addresses are not redirected: /admin is the admin panel here. Its sections are /administration?category={id}.');
        $staffCategories = [];
        foreach ($this->rows('staff_categories', fn ($q) => $q->whereNull('deleted_at'))->sortBy('serial') as $row) {
            $staffCategories[$row->id] = $this->category('staff_categories', $row, Normalizer::text($row->category_name), 'support', false, ++$order, (bool) $row->publication_status)->id;
        }

        // Categories DIU does not have (the module's starter ladder) go after DIU's, and are named for review
        foreach (AdministrationCategory::query()->whereNotIn('id', [...$adminCategories, ...$staffCategories])->ordered()->get() as $extra) {
            $extra->update(['sort_order' => ++$order]);
            $this->report->review("Category \"{$extra->name}\" is not DIU's (".$extra->members()->count().' members): delete it in People › Staff categories if it is not needed.');
        }

        $employeeIds = [];
        $photos = 0;
        $this->members('admins', $adminCategories, 'admin_category_id', $employeeIds, $photos);
        $this->members('staff', $staffCategories, 'staff_category_id', $employeeIds, $photos);

        $this->replaceManualPersons();

        if ($photos > 0) {
            $this->report->review("{$photos} photos wait for the files (S03).");
        }
        $this->report->review('Legacy "campus" text is not migrated: it comes back with the Campus module (M-08).');
    }

    protected function category(string $table, object $row, string $name, string $group, bool $public, int $order, bool $active): AdministrationCategory
    {
        return $this->upsert($table, $row, AdministrationCategory::class, [
            'name' => Normalizer::en($name),
            'group' => $group,
            'is_public' => $public,
            'sort_order' => $order,
            'is_active' => $active,
        ], existing: $this->same(AdministrationCategory::class, ['name->en' => $name]));
    }

    /**
     * @param  array<int, int>  $categories  legacy category id => category id
     * @param  array<string, true>  $employeeIds  seen so far (an ID is one person's: later duplicates get none)
     */
    protected function members(string $table, array $categories, string $categoryColumn, array &$employeeIds, int &$photos): void
    {
        foreach ($this->rows($table, fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $category = $categories[$row->{$categoryColumn}] ?? null;
            $name = Normalizer::text($row->name);
            if ($category === null || blank($name)) {
                $this->report->skipped($table, $category === null ? 'no category' : 'no name');

                continue;
            }

            $employeeId = Normalizer::text((string) $row->office_id);
            $employeeId = $employeeId && $employeeId !== '0' ? mb_substr($employeeId, 0, 30) : null;
            $mine = $this->ids->record($table, $row->id, Member::class);
            if ($employeeId !== null && (isset($employeeIds[$employeeId])
                || Member::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('employee_id', $employeeId)->whereKeyNot($mine?->getKey())->exists())) {
                $this->report->review("{$table} #{$row->id} {$name}: employee ID {$employeeId} is someone else's too — left empty.");
                $employeeId = null;
            }
            if ($employeeId !== null) {
                $employeeIds[$employeeId] = true;
            }
            $photos += filled($row->photo) ? 1 : 0;

            $this->upsert($table, $row, Member::class, [
                'category_id' => $category,
                'employee_id' => $employeeId,
                'name' => Normalizer::en($name),
                'designation' => Normalizer::en(Normalizer::text($row->designation)),
                'email' => filter_var(Normalizer::text($row->email), FILTER_VALIDATE_EMAIL) ?: null,
                'phone' => mb_substr((string) Normalizer::text($row->phone), 0, 50) ?: null,
                'extension' => mb_substr((string) Normalizer::text($row->extension), 0, 20) ?: null,
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Member $member, bool $created) => $table === 'admins' && filled($row->unique_id)
                ? $this->keepSlug($member, Str::slug((string) $row->unique_id), $created) : null);
        }
    }

    /** Assignments of the per-admin manual persons (S08, S10) now point at the member; the manual persons go. */
    protected function replaceManualPersons(): void
    {
        $moved = 0;
        foreach ($this->ids->ownedIds('admins@manual', (new ManualPerson)->getMorphClass()) as $manualId) {
            $legacyId = DB::table('migration_id_map')->where('legacy_table', 'admins@manual')->where('new_id', $manualId)->value('legacy_id');
            $member = $legacyId ? $this->ids->record('admins', $legacyId, Member::class) : null;
            $manual = ManualPerson::query()->withoutGlobalScopes()->find($manualId);
            if ($member === null || $manual === null) {
                continue;
            }

            $moved += Assignment::withTrashed()->where('person_type', $manual->getMorphClass())->where('person_id', $manual->getKey())
                ->update(['person_type' => $member->getMorphClass(), 'person_id' => $member->getKey()]);
            $manual->forceDelete();
            $this->ids->forget('admins@manual', $legacyId);
        }

        $this->report->review("{$moved} assignments moved from manual persons to Administration members.");
    }
}
