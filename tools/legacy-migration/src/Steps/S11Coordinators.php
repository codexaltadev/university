<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Department\Support\CoordinatorsPage;
use Modules\Program\Models\Program;
use Platform\Assignments\Assignment;

/**
 * S11C Coordination officers (DB-04 v1.1 §11, DB-03 §18): `coordination_officers` → the role "coordinator" on the program
 * its text names ("BBA", "MBA" — matched among the department's programs), else on the department with that text as
 * the label. The person is the Administration member of `admin_id` (S11), else a manual person from the row; a phone,
 * email or extension typed on the row stays on the assignment. DIU's /coordination-officer → /coordination-officers.
 * After S04, S06 and S11. Run again: updates, no copies.
 */
class S11Coordinators extends Step
{
    public static function key(): string
    {
        return 'S11C';
    }

    public static function title(): string
    {
        return 'Coordination officers of departments and programs';
    }

    public static function requires(): array
    {
        return ['department'];
    }

    protected function handle(): void
    {
        $this->oldAddress('/coordination-officer', '/'.CoordinatorsPage::PATH);
        if (! Schema::connection('legacy')->hasTable('coordination_officers')) {
            $this->report->review('No coordination_officers table in the legacy database: nothing to migrate.');

            return;
        }

        $programs = class_exists(Program::class) ? Program::query()->withoutGlobalScopes()->get()->groupBy('department_id') : collect();
        $unmatched = [];
        foreach ($this->rows('coordination_officers') as $row) {
            $department = $this->ids->record('departments', $row->department_id, Department::class);
            $person = $this->adminPerson($row->admin_id) ?? $this->rowPerson('coordination_officers', $row);
            if ($department === null || $person === null) {
                $this->report->skipped('coordination_officers', 'no department or person');

                continue;
            }

            $label = Normalizer::text($row->department_program_name);
            $program = $label !== null ? $this->program($programs->get($department->id, collect()), $label) : null;
            if ($label !== null && $program === null) {
                $unmatched[$label] = true;
            }
            $place = $program ?? $department;

            $this->upsert('coordination_officers', $row, Assignment::class, [
                'person_type' => $person->getMorphClass(),
                'person_id' => $person->getKey(),
                'assignable_type' => $place->getMorphClass(),
                'assignable_id' => $place->getKey(),
                'role_key' => CoordinatorsPage::ROLE,
                'role_label' => $program === null && $label !== null ? ['en' => $label] : null,
                'sort_order' => max(0, (int) $row->serial),
                'is_public' => (bool) $row->publication_status,
                'overrides' => array_filter(['phone' => Normalizer::text($row->phone), 'email' => Normalizer::text($row->email), 'extension' => Normalizer::text($row->extension)]) ?: null,
                'is_active' => true,
            ]);
        }

        if ($unmatched !== []) {
            $this->report->review('Coordinators kept on the department with the program as a label (no program by that name there): '.implode(', ', array_keys($unmatched)).'.');
        }
        $this->report->review('Photos of coordinators without an Administration member (manual persons) are not migrated: add them in People if wanted.');
    }

    /** The department's program the text names: its short name, else its name, compared without punctuation */
    protected function program($programs, string $label): ?Model
    {
        $key = fn (?string $s): string => (string) preg_replace('/[^a-z0-9]+/', '', mb_strtolower((string) $s));
        $wanted = $key($label);

        return $programs->first(fn (Model $p): bool => $key($p->short_name) === $wanted)
            ?? $programs->first(fn (Model $p): bool => collect($p->getTranslations('name'))->contains(fn ($n): bool => $key($n) === $wanted));
    }
}
