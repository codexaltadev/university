<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Office\Models\Office;
use Modules\Office\Models\OfficeSection;
use Modules\Office\Models\OfficeSupportContact;
use Platform\Assignments\Assignment;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;

/**
 * S10 Offices (DB-04 v1.1 §11.1): the 24 offices (old addresses kept, own page = show_page),
 * the 12 office-"departments" S04 left out (matched to an office, or a new one; campuses wait for
 * the Campus module), the section and its people, office staff (office_admins) as the office
 * team and the support contacts — people as manual persons until Administration (S11).
 */
class S10Offices extends Step
{
    /** Legacy office-"department" short name → the office it is (null = a new office). */
    public const DEPARTMENT_OFFICES = [
        'accounts' => 'accounts-office', 'admission' => 'admission-office', 'cdc' => 'cdc-office', 'exam' => 'exam-office',
        'registrar' => 'registrar-office', 'transport' => 'transport', 'hall' => 'hall-office', 'hrdi' => 'hrdi-office',
        'dil' => null, 'diuysbc' => null,
    ];

    /** Campuses are the Campus module's (M-08), not offices. */
    public const CAMPUSES = ['ashulia', 'ucampus'];

    public static function key(): string
    {
        return 'S10';
    }

    public static function title(): string
    {
        return 'Offices: profiles, sections, team, support contacts';
    }

    public static function requires(): array
    {
        return ['office'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('offices')) {
            $this->report->review('No offices table in the legacy database: nothing to migrate.');

            return;
        }

        app(RoleCatalog::class)->sync();   // head, team, section team, responsible, contact

        $bySlug = $this->offices();
        $this->departmentOffices($bySlug);
        $this->sections($bySlug);
        $this->team();
        $this->supportContacts();
    }

    /** @return Collection<string, Office> by legacy slug */
    protected function offices(): Collection
    {
        $bySlug = collect();
        foreach ($this->rows('offices') as $row) {
            $name = Normalizer::text($row->title);
            $office = $this->upsert('offices', $row, Office::class, [
                'name' => Normalizer::en($name),
                'has_microsite' => (bool) $row->show_page,
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Office $o, bool $created) => $this->keepSlug($o, $row->slug, $created),
                existing: $this->same(Office::class, ['name->en' => $name]));
            $bySlug[$row->slug] = $office;
        }

        $this->report->review('Office types are new: give each office its type in People › Offices (Administration, Academic Support, Student Services …).');

        return $bySlug;
    }

    /** @param Collection<string, Office> $bySlug */
    protected function departmentOffices(Collection $bySlug): void
    {
        $faculties = $this->legacy()->table('faculties')->where('is_optional', 1)->pluck('id');
        foreach ($this->rows('departments', fn ($q) => $q->whereIn('faculty_id', $faculties)) as $row) {
            $short = strtolower((string) $row->short_name);
            if (in_array($short, self::CAMPUSES, true)) {
                $this->report->review("Legacy \"{$row->department_name}\" is a campus: it goes to the Campus module (M-08), not migrated now.");

                continue;
            }

            $target = array_key_exists($short, self::DEPARTMENT_OFFICES) ? self::DEPARTMENT_OFFICES[$short] : false;
            if ($target === false) {
                $this->report->review("Legacy office-department \"{$row->department_name}\" ({$short}) has no mapping — left out.");

                continue;
            }

            if ($target !== null && isset($bySlug[$target])) {
                $this->ids->remember('departments', $row->id, $bySlug[$target]);   // its FAQs etc. go to that office
                $this->report->saved('departments', false);

                continue;
            }

            $name = Normalizer::text($row->department_name);
            $this->upsert('departments', $row, Office::class, [
                'name' => Normalizer::en($name),
                'short_name' => Normalizer::shortName($row->short_name),
                'has_microsite' => false,   // no pages or menus in the legacy site
                'sort_order' => 100 + (int) $row->id,
                'is_active' => true,
            ], after: fn (Office $o, bool $created) => $this->keepSlug($o, $short, $created),
                existing: $this->same(Office::class, ['name->en' => $name]));
        }
    }

    /** @param Collection<string, Office> $bySlug */
    protected function sections(Collection $bySlug): void
    {
        foreach ($this->rows('sections') as $row) {
            $office = $bySlug[$row->slug] ?? null;   // the legacy section has no office: matched by its name
            if ($office === null) {
                $this->report->skipped('sections', 'no office of the same name');

                continue;
            }

            $this->upsert('sections', $row, OfficeSection::class, [
                'office_id' => $office->id,
                'name' => Normalizer::en(Normalizer::text($row->section_name)),
                'is_active' => (bool) $row->publication_status,
            ]);
        }

        foreach ($this->rows('section_employees') as $row) {
            $section = $this->ids->record('sections', $row->section_id, OfficeSection::class);
            $person = $this->adminPerson($row->admin_id);
            if ($section === null || $person === null) {
                $this->report->skipped('section_employees', 'no section or person');

                continue;
            }
            $this->assign('section_employees', $row, $person, $section, OfficeSection::ROLE, (int) $row->admin_serial, (bool) $row->publication_status);
        }
    }

    /**
     * office_admins: the office team. (admins.office_id is not an office: it holds employee codes
     * like 710003029 — DB-04 v1.1 §11.1; Administration (S11) keeps it as the employee id.)
     */
    protected function team(): void
    {
        foreach ($this->rows('office_admins') as $row) {
            $office = $this->ids->record('offices', $row->office_id, Office::class);
            $person = $this->adminPerson($row->admin_id) ?? $this->rowPerson('office_admins', $row);
            if ($office === null || $person === null) {
                $this->report->skipped('office_admins', 'no office or person');

                continue;
            }

            $overrides = array_filter(['phone' => Normalizer::text($row->phone), 'extension' => Normalizer::text($row->extension)]);
            $this->assign('office_admins', $row, $person, $office, 'team', (int) $row->serial, (bool) $row->publication_status, $overrides);
        }
    }

    /** One support contact per office and purpose; its rows are its people. */
    protected function supportContacts(): void
    {
        foreach ($this->rows('support_contacts') as $row) {
            $office = $this->ids->record('offices', $row->office_id, Office::class);
            $person = $this->adminPerson($row->admin_id) ?? $this->rowPerson('support_contacts', $row);
            if ($office === null || $person === null) {
                $this->report->skipped('support_contacts', 'no office or person');

                continue;
            }

            $purpose = Normalizer::text($row->purpose);
            $contact = OfficeSupportContact::query()->where('office_id', $office->id)->where('purpose->en', $purpose)->first()
                ?? OfficeSupportContact::create([
                    'office_id' => $office->id,
                    'purpose' => Normalizer::en($purpose),
                    'location' => Normalizer::en(Normalizer::text($row->office_location)),
                    'sort_order' => max(0, (int) $row->serial),
                    'is_active' => true,
                ]);

            $overrides = array_filter(['phone' => Normalizer::text($row->phone), 'extension' => Normalizer::text($row->extension)]);
            $this->assign('support_contacts', $row, $person, $contact, OfficeSupportContact::ROLE, (int) $row->serial, (bool) $row->publication_status, $overrides);
        }
    }

    /** @param array<string, string> $overrides */
    protected function assign(string $table, object $row, ManualPerson $person, $place, string $role, int $order, bool $public, array $overrides = []): void
    {
        $this->upsert($table, $row, Assignment::class, [
            'person_type' => $person->getMorphClass(),
            'person_id' => $person->getKey(),
            'assignable_type' => $place->getMorphClass(),
            'assignable_id' => $place->getKey(),
            'role_key' => $role,
            'sort_order' => max(0, $order),
            'is_public' => $public,
            'overrides' => $overrides ?: null,
            'is_active' => true,
        ]);
    }
}
