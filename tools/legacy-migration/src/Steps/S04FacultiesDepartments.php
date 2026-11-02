<?php

namespace Legacy\Migration\Steps;

use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Platform\Statistics\StatItem;
use Platform\Statistics\StatSet;

/**
 * S04 — Faculty and Department (DB-03 §15). Only academic faculties: legacy "faculties"
 * marked is_optional ("Office", "Institutional Programs") hold offices and institutes,
 * which the Office module receives in S10. Old web addresses are kept: /faculty/fsit,
 * /department/cse. Pictures come with S03 (media import).
 */
class S04FacultiesDepartments extends Step
{
    /** faculties: label column => figure column (DIU's faculty page "States") */
    public const FIGURES = ['publication_title' => 'publication', 'partner_title' => 'active_partner', 'research_title' => 'research_lab', 'phd_faculty_title' => 'phd_faculty'];

    /** Labels of the counters FacultyWebsite::createStatSet gives a new faculty */
    public const MADE_WITH_FACULTY = ['Departments', 'Programs', 'Faculty Members', 'Students'];

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
            ], function (Faculty $faculty, bool $created) use ($row): void {
                $this->keepSlug($faculty, $row->faculty_short_name, $created);
                $this->figures($faculty, $row);
            }, existing: fn () => Faculty::query()->withoutGlobalScopes()->whereNull('deleted_at')->where('short_name', Normalizer::shortName($row->faculty_short_name))->first());

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

    /**
     * Newer dumps: the four figures of DIU's faculty page (label column => figure column, "390+",
     * "41%") become the faculty's counters in that order; the counters made with the faculty
     * (FacultyWebsite::createStatSet) are turned off, items added by hand stay.
     */
    protected function figures(Faculty $faculty, object $row): void
    {
        $figures = collect(self::FIGURES)
            ->map(fn (string $figure, string $label): array => [Normalizer::text($row->{$label} ?? null), Normalizer::text($row->{$figure} ?? null)])
            ->filter(fn (array $f): bool => filled($f[0]) && preg_match('/^\s*([\d,]+)\s*(\D*)$/', (string) $f[1]))->values();
        $set = StatSet::query()->ownedBy($faculty->getMorphClass().':'.$faculty->getKey())->where('key', 'default')->first();
        if ($figures->isEmpty() || $set === null) {
            return;
        }

        $items = $set->items()->get();
        foreach ($figures as $i => [$label, $figure]) {
            preg_match('/^\s*([\d,]+)\s*(\D*)$/', $figure, $m);
            $item = $items->first(fn (StatItem $s): bool => $s->getTranslation('label', 'en', false) === $label) ?? new StatItem(['stat_set_id' => $set->id]);
            $item->fill(['label' => ['en' => $label], 'source' => 'manual', 'manual_value' => (int) str_replace(',', '', $m[1]), 'rounding' => null, 'suffix' => trim($m[2]) ?: null, 'sort_order' => $i + 1, 'is_active' => true])->save();
        }
        $items->filter(fn (StatItem $s): bool => in_array($s->getTranslation('label', 'en', false), self::MADE_WITH_FACULTY, true))->each->update(['is_active' => false]);
    }
}
