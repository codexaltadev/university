<?php

namespace Legacy\Migration\Steps;

use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;

/**
 * S04 — Faculty and Department (DB-03 §15). Only academic faculties: legacy "faculties"
 * marked is_optional ("Office", "Institutional Programs") hold offices and institutes,
 * which the Office module receives in S10. Old web addresses are kept: /faculty/fsit,
 * /department/cse. Pictures come with S03 (media import).
 */
class S04FacultiesDepartments extends Step
{
    public static function key(): string
    {
        return 'S04';
    }

    public static function title(): string
    {
        return 'Faculties and departments';
    }

    public static function requires(): array
    {
        return ['faculty', 'department'];
    }

    protected function handle(): void
    {
        $skippedFaculties = [];
        $pictures = 0;

        foreach ($this->rows('faculties') as $row) {
            if ($row->deleted_at !== null) {
                $this->report->skipped('faculties', 'deleted');

                continue;
            }

            if ($row->is_optional) {
                $skippedFaculties[$row->id] = $row->faculty_name;
                $this->report->skipped('faculties', 'not academic (offices / institutes) → S10');

                continue;
            }

            $this->upsert('faculties', $row, Faculty::class, [
                'name' => Normalizer::en(Normalizer::text($row->faculty_name)),
                'short_name' => Normalizer::shortName($row->faculty_short_name),
                'overview' => Normalizer::en(Normalizer::html($row->faculty_content)),
                'show_in_listing' => (bool) ($row->show_list ?? true),
                'sort_order' => (int) $row->faculty_serial,
                'is_active' => (bool) $row->publication_status,
            ], fn (Faculty $faculty, bool $created) => $this->keepSlug($faculty, $row->faculty_short_name, $created),
                existing: fn () => Faculty::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('short_name', Normalizer::shortName($row->faculty_short_name))->first());

            $pictures += (int) filled($row->faculty_photo);
        }

        foreach ($this->rows('departments') as $row) {
            if ($row->deleted_at !== null) {
                $this->report->skipped('departments', 'deleted');

                continue;
            }

            if (isset($skippedFaculties[$row->faculty_id])) {
                $this->report->skipped('departments', "under \"{$skippedFaculties[$row->faculty_id]}\" → S10");

                continue;
            }

            $facultyId = $this->ids->find('faculties', $row->faculty_id);
            if ($facultyId === null) {
                $this->report->skipped('departments', 'faculty not migrated');
                $this->report->review("Department {$row->id} \"{$row->department_name}\": faculty {$row->faculty_id} not migrated.");

                continue;
            }

            $this->upsert('departments', $row, Department::class, [
                'faculty_id' => $facultyId,
                'name' => Normalizer::en(Normalizer::text($row->department_name)),
                'short_name' => Normalizer::shortName($row->short_name),
                'about_title' => Normalizer::en(Normalizer::text($row->about_title)),
                'about' => Normalizer::en(Normalizer::html($row->department_content)),
                'external_url' => Normalizer::url($row->department_link),
                'sort_order' => (int) $row->department_serial,
                'is_active' => (bool) $row->publication_status,
            ], fn (Department $department, bool $created) => $this->keepSlug($department, $row->short_name, $created),
                existing: fn () => Department::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('faculty_id', $facultyId)->where('short_name', Normalizer::shortName($row->short_name))->first());

            $pictures += (int) filled($row->department_photo) + (int) filled($row->department_cover);
        }

        if ($pictures > 0) {
            $this->report->review("{$pictures} faculty / department pictures wait for S03 (media import).");
        }
    }
}
