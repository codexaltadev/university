<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramFacilitator;
use Modules\Program\Models\ProgramListItem;
use Modules\Program\Models\ProgramMajor;
use Modules\Program\Models\ProgramMaterial;
use Modules\Program\Models\ProgramMembership;
use Modules\Program\Models\ProgramVariant;
use Platform\Statistics\StatSet;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S06 — Programs and their parts (DB-03 §15, §15.1): department_program_type with
 * level and tags, eligibilities, counters, majors, career paths, industries,
 * facilitators, materials, memberships, variants. Old addresses are kept
 * (/program/bsc-in-swe). Needs S02 (tags, major categories) and S04 (departments).
 */
class S06Programs extends Step
{
    /** Legacy section_intros keys → the new page sections. */
    public const INTROS = ['majors' => 'majors', 'variants' => 'variants', 'materials' => 'materials', 'future-career' => 'career_path', 'professional-memberships' => 'memberships'];

    public static function key(): string
    {
        return 'S06';
    }

    public static function title(): string
    {
        return 'Programs and their parts';
    }

    public static function requires(): array
    {
        return ['department', 'program'];
    }

    protected function handle(): void
    {
        $eligibilities = $this->rows('eligibilities')->groupBy('department_program_type_id');
        $homeFlags = 0;
        $pictures = 0;

        foreach ($this->rows('department_program_type') as $row) {
            $departmentId = $this->ids->find('departments', $row->department_id);

            if ($departmentId === null) {
                $this->report->skipped('department_program_type', 'department not migrated');
                $this->report->review("Program {$row->id} \"{$row->full_program_name}\": department {$row->department_id} not migrated.");

                continue;
            }

            $level = $this->level($row);
            if ($level === null) {
                $this->report->skipped('department_program_type', 'no level (Proposed Programs)');

                continue;
            }

            $parsed = Normalizer::creditsAndDuration($row->program_credit_duration);
            $shortName = Normalizer::shortName($row->short_name) ?? Department::query()->withoutGlobalScopes()->find($departmentId)?->short_name;

            if (Normalizer::text($row->short_name) === null) {
                $this->report->review("Program {$row->id} \"{$row->full_program_name}\": no short name, used the department's ({$shortName}).");
            }

            $program = $this->upsert('department_program_type', $row, Program::class, [
                'department_id' => $departmentId,
                'level_term_id' => $level,
                'name' => Normalizer::en(Normalizer::text($row->full_program_name)),
                'short_name' => Str::limit((string) $shortName, 50, ''),
                'subtitle' => Normalizer::en(Normalizer::text($row->subtitle)),
                'summary' => Normalizer::en(Normalizer::html($row->summary)),
                'objective' => Normalizer::en(Normalizer::html($row->program_objective)),
                'ai_summary' => Normalizer::en(Normalizer::text($row->ai_summary)),
                'ai_short_summary' => Normalizer::en(Normalizer::text($row->ai_short_summary)),
                'section_intros' => $this->intros($row->section_intros),
                'total_credits' => $parsed['credits'],
                'duration_years' => $parsed['years'],
                'total_terms' => $parsed['terms'],
                'next_intake_on' => $row->next_semester_date,
                'video_url' => Normalizer::url($row->video_url),
                'show_in_listing' => (bool) $row->show_program,
                'sort_order' => (int) ($row->serial ?? $row->id),
                'published_at' => $row->created_at ?? now(),
                'is_active' => (bool) $row->publication_status,
                ...$this->eligibility($row, $eligibilities->get($row->id, collect())),
            ], fn (Program $program, bool $created) => $this->keepSlug($program, Str::slug((string) $row->slug), $created),
                existing: fn () => Program::findBySlug(Str::slug((string) $row->slug), 'en')[0] ?? null);

            $tag = isset(S02Vocabularies::NOT_TAGS[$row->program_type_id]) ? null : $this->ids->find('program_types', $row->program_type_id);
            $program->syncTerms('program.tag', $tag ? [$tag] : []);

            $this->counters($program, $row);

            if (filled($row->program_credit_duration) && ($parsed['credits'] === null || $parsed['years'] === null)) {
                $this->report->review("Program {$row->id} \"{$row->full_program_name}\": credits / years not found in the text — check by hand.");
            }
            if (filled($row->program_credit_duration)) {
                $this->report->archive("program_credit_duration of program {$row->id} ({$row->slug})", (string) $row->program_credit_duration);
            }

            $homeFlags += (int) $row->is_home;
            $pictures += (int) filled($row->program_image);
        }

        $this->report->review("{$homeFlags} programs had \"is_home\": choose them in the homepage section \"Featured Programs\".");
        if ($pictures > 0) {
            $this->report->review("{$pictures} program images wait for S03 (media import).");
        }

        $this->parts();
    }

    /** DB-03 §15.1: type 3 → Diploma, 26 → Post Graduate Diploma, else parent 1 / 2 → Undergraduate / Graduate. */
    protected function level(object $row): ?int
    {
        $name = match (true) {
            (int) $row->program_type_id === 3 => 'Diploma',
            (int) $row->program_type_id === 26 => 'Post Graduate Diploma',
            (int) $row->program_type_parent_id === 1 => 'Undergraduate',
            (int) $row->program_type_parent_id === 2 => 'Graduate',
            default => null,
        };

        if ($name === null) {
            return null;
        }

        $this->levels ??= Term::query()->where('vocabulary_id', app(Vocabularies::class)->find('program.level')->id)->get()
            ->mapWithKeys(fn (Term $t): array => [$t->getTranslation('name', 'en') => $t->id])->all();

        return $this->levels[$name] ?? throw new \RuntimeException("Program level \"{$name}\" is missing: run the Program module seeder.");
    }

    /** @var array<string, int>|null level name → term id */
    protected ?array $levels = null;

    /** @return array{en: array<string, string>}|null */
    protected function intros(?string $json): ?array
    {
        $old = json_decode((string) $json, true) ?: [];
        $new = [];

        foreach (self::INTROS as $from => $to) {
            if (filled($text = Normalizer::text($old[$from] ?? null))) {
                $new[$to] = $text;
            }
        }

        return $new === [] ? null : ['en' => $new];
    }

    /** eligibilities → eligibility_general / _english_medium; several rows are joined and flagged (DB-03 §15). */
    protected function eligibility(object $program, Collection $rows): array
    {
        $rows = $rows->sortBy([['publication_status', 'desc'], ['eligibility_serial', 'asc']])->values();

        if ($rows->count() > 1) {
            $this->report->review("Program {$program->id} \"{$program->full_program_name}\": {$rows->count()} eligibility rows joined — merge by hand.");
        }

        $join = fn (string $column): ?array => Normalizer::en(Normalizer::html($rows->map(fn ($r) => Normalizer::html($r->{$column}))->filter()->implode("\n<hr>\n")));

        return ['eligibility_general' => $join('normal_student'), 'eligibility_english_medium' => $join('em_student')];
    }

    /** Approved alumni and teachers are manual counters of the Program's stat set (DB-03 §15). */
    protected function counters(Program $program, object $row): void
    {
        $set = StatSet::query()->ownedBy($program->getMorphClass().':'.$program->getKey())->where('key', 'default')->first();

        foreach (['Approved Alumni' => $row->approved_alumni_number, 'Faculty Members' => $row->teachers_count] as $label => $value) {
            $item = $set?->items()->where('source', 'manual')->get()->first(fn ($i) => $i->getTranslation('label', 'en') === $label);
            $item?->update(['manual_value' => $value === null ? null : (int) $value]);
        }
    }

    /** Majors, career paths, industries, facilitators, materials, memberships, variants. */
    protected function parts(): void
    {
        $program = fn (string $table, object $row): ?int => $this->ids->find('department_program_type', $row->department_program_type_id)
            ?? tap(null, fn () => $this->report->skipped($table, 'program not migrated'));
        $pictures = 0;

        foreach ($this->rows('program_majors') as $row) {
            if ($id = $program('program_majors', $row)) {
                $this->upsert('program_majors', $row, ProgramMajor::class, [
                    'program_id' => $id,
                    'category_term_id' => $this->ids->find('program_major_categories', $row->program_major_category_id),
                    'title' => Normalizer::en(Normalizer::text($row->title)),
                    'short_description' => Normalizer::en(Normalizer::text($row->short_description)),
                ], existing: $this->same(ProgramMajor::class, ['program_id' => $id, 'title->en' => Normalizer::text($row->title)]));
            }
        }

        foreach (['program_career_paths' => ProgramListItem::CAREER_PATH, 'program_industries' => ProgramListItem::INDUSTRY] as $table => $type) {
            foreach ($this->rows($table) as $row) {
                if (($id = $program($table, $row)) && filled($title = Normalizer::text($row->title))) {
                    $this->upsert($table, $row, ProgramListItem::class, ['program_id' => $id, 'list_type' => $type, 'title' => ['en' => $title]],
                        existing: $this->same(ProgramListItem::class, ['program_id' => $id, 'list_type' => $type, 'title->en' => $title]));
                } elseif ($id) {
                    $this->report->skipped($table, 'no title');
                }
            }
        }

        foreach ($this->rows('program_facilitators') as $row) {
            if ($id = $program('program_facilitators', $row)) {
                $this->upsert('program_facilitators', $row, ProgramFacilitator::class, [
                    'program_id' => $id,
                    'name' => Str::limit((string) Normalizer::text($row->name), 191, ''),
                    'designation' => Normalizer::en(Normalizer::text($row->designation)),
                    'organization' => Normalizer::en(Normalizer::text($row->organization)),
                    'type' => $row->type === 'industrial' ? 'industry_expert' : 'facilitator',
                    'profile_url' => Normalizer::url($row->profile_link),
                    'linkedin_url' => Normalizer::url($row->linkedin),
                    'email' => filter_var(Normalizer::text($row->email), FILTER_VALIDATE_EMAIL) ?: null,
                    'phone' => Str::limit((string) Normalizer::text($row->mobile), 50, '') ?: null,
                    'sort_order' => (int) ($row->serial ?? $row->id),
                    'is_active' => (bool) ($row->publication_status ?? true),
                ], existing: $this->same(ProgramFacilitator::class, ['program_id' => $id, 'name' => Str::limit((string) Normalizer::text($row->name), 191, '')]));
                $pictures += (int) filled($row->image);
            }
        }

        foreach ($this->rows('program_materials') as $row) {
            if ($id = $program('program_materials', $row)) {
                $this->upsert('program_materials', $row, ProgramMaterial::class, [
                    'program_id' => $id,
                    'title' => Normalizer::en(Normalizer::text($row->title)),
                    'url' => Normalizer::url($row->material_link) ?? Normalizer::fileUrl($row->support_document),   // the file moves to the Media Library in S03
                    'sort_order' => (int) ($row->sort_order ?? $row->id),
                ], existing: $this->same(ProgramMaterial::class, ['program_id' => $id, 'title->en' => Normalizer::text($row->title)]));
            }
        }

        foreach ($this->rows('program_professional_memberships') as $row) {
            if ($id = $program('program_professional_memberships', $row)) {
                $this->upsert('program_professional_memberships', $row, ProgramMembership::class, [
                    'program_id' => $id,
                    'title' => Normalizer::en(Normalizer::text($row->title)),
                    'short_description' => Normalizer::en(Normalizer::text($row->short_description)),
                ], existing: $this->same(ProgramMembership::class, ['program_id' => $id, 'title->en' => Normalizer::text($row->title)]));
                $pictures += (int) filled($row->image);
            }
        }

        foreach ($this->rows('program_variants') as $row) {
            $id = $program('program_variants', $row);

            if ($id && Normalizer::text($row->title) === null) {
                $this->report->skipped('program_variants', 'no title');

                continue;
            }

            if ($id) {
                $related = (int) $row->related_program_id === (int) $row->department_program_type_id ? null : $this->ids->find('department_program_type', $row->related_program_id);

                $this->upsert('program_variants', $row, ProgramVariant::class, [
                    'program_id' => $id,
                    'related_program_id' => $related,
                    'title' => Normalizer::en(Normalizer::text($row->title)),
                    'short_description' => Normalizer::en(Normalizer::text($row->short_description) ?? ''),
                    'details' => Normalizer::en(Normalizer::html($row->full_details)),
                    'button_text' => Normalizer::en(Normalizer::text($row->action_button_text)),
                    'button_url' => Normalizer::url($row->action_button_url),
                ], existing: $this->same(ProgramVariant::class, ['program_id' => $id, 'title->en' => Normalizer::text($row->title)]));
                $pictures += (int) filled($row->image);
            }
        }

        if ($pictures > 0) {
            $this->report->review("{$pictures} facilitator / membership / variant pictures wait for S03 (media import).");
        }
    }
}
