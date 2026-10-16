<?php

namespace Modules\Administration\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Bulk import and export (FR-ADMN-06), one column set both ways so an exported file can be
 * edited and imported again: Employee ID, Name, Designation, Category, Office, Email, Phone,
 * Extension, Active. A row updates the member with that employee ID (or else that email);
 * otherwise it adds one. Category by name (must exist); Office by name or short name.
 */
class MemberSheet
{
    public const COLUMNS = ['Employee ID', 'Name', 'Designation', 'Category', 'Office', 'Email', 'Phone', 'Extension', 'Active'];

    protected const OFFICE = 'Modules\\Office\\Models\\Office';

    /** @param resource $out */
    public function export($out): void
    {
        fputcsv($out, self::COLUMNS);
        $offices = $this->offices(byId: true);
        Member::query()->with('category')->directoryOrder()->chunk(500, function ($members) use ($out, $offices): void {
            foreach ($members as $m) {
                fputcsv($out, [
                    $m->employee_id, $m->getTranslation('name', 'en', true), $m->getTranslation('designation', 'en', true),
                    $m->category?->getTranslation('name', 'en', true), $offices[$m->primary_office_id] ?? '',
                    $m->email, $m->phone, $m->extension, $m->is_active ? 'yes' : 'no',
                ]);
            }
        });
    }

    /**
     * @return array{rows: list<array<string, mixed>>, errors: list<string>, total: int}
     */
    public function check(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));
        $reader = $extension === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);

        $categories = $this->categories();
        $offices = $this->offices();
        $columns = null;
        $rows = $errors = [];
        $line = $total = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $cells = array_map(fn ($v): string => trim((string) $v), $row->toArray());

                if ($columns === null) {
                    $wanted = array_map(fn (string $c): string => Str::lower($c), self::COLUMNS);
                    $columns = [];
                    foreach ($cells as $i => $cell) {
                        if (($k = array_search(Str::lower($cell), $wanted, true)) !== false) {
                            $columns[self::COLUMNS[$k]] ??= $i;
                        }
                    }
                    if (! isset($columns['Name'], $columns['Category'])) {
                        $reader->close();

                        return ['rows' => [], 'errors' => [__('administration::administration.import.bad_header')], 'total' => 0];
                    }

                    continue;
                }

                if (implode('', $cells) === '') {
                    continue;
                }
                $total++;
                $get = fn (string $key): string => isset($columns[$key]) ? ($cells[$columns[$key]] ?? '') : '';
                $fail = function (string $why) use (&$errors, $line): void {
                    $errors[] = __('administration::administration.import.line', ['line' => $line, 'why' => $why]);
                };

                if ($get('Name') === '') {
                    $fail(__('administration::administration.import.no_name'));

                    continue;
                }
                $category = $categories[Str::lower($get('Category'))] ?? null;
                if ($category === null) {
                    $fail(__('administration::administration.import.unknown', ['name' => $get('Category') ?: '—']));

                    continue;
                }
                $office = null;
                if ($get('Office') !== '') {
                    $office = $offices[Str::lower($get('Office'))] ?? null;
                    if ($office === null) {
                        $fail(__('administration::administration.import.unknown', ['name' => $get('Office')]));

                        continue;
                    }
                }
                if ($get('Email') !== '' && ! filter_var($get('Email'), FILTER_VALIDATE_EMAIL)) {
                    $fail(__('administration::administration.import.bad_email'));

                    continue;
                }

                $rows[] = [
                    'employee_id' => Str::limit($get('Employee ID'), 30, '') ?: null,
                    'name' => Str::limit($get('Name'), 150, ''),
                    'designation' => Str::limit($get('Designation'), 191, '') ?: null,
                    'category_id' => $category, 'primary_office_id' => $office,
                    'email' => $get('Email') ?: null, 'phone' => Str::limit($get('Phone'), 50, '') ?: null,
                    'extension' => Str::limit($get('Extension'), 20, '') ?: null,
                    'is_active' => ! in_array(Str::lower($get('Active')), ['no', '0', 'false', 'inactive'], true),
                ];
            }

            break;   // the first sheet only
        }

        $reader->close();

        return ['rows' => $rows, 'errors' => $errors, 'total' => $total];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{created: int, updated: int}
     */
    public function save(array $rows): array
    {
        return DB::transaction(function () use ($rows): array {
            $created = $updated = 0;
            foreach ($rows as $row) {
                $member = ($row['employee_id'] ? Member::query()->where('employee_id', $row['employee_id'])->first() : null)
                    ?? ($row['email'] ? Member::query()->where('email', $row['email'])->first() : null);
                $member ? $updated++ : $created++;
                $member ??= new Member(['sort_order' => 0]);

                $member->fill([
                    ...$row,
                    'name' => [...(array) ($member->getTranslations('name') ?? []), 'en' => $row['name']],
                    'designation' => $row['designation'] ? [...(array) $member->getTranslations('designation'), 'en' => $row['designation']] : $member->getTranslations('designation'),
                ])->save();
            }

            return ['created' => $created, 'updated' => $updated];
        });
    }

    /** @return array<string, int> lower-case name (every language) => id */
    protected function categories(): array
    {
        $map = [];
        foreach (AdministrationCategory::query()->get() as $category) {
            foreach ($category->getTranslations('name') as $name) {
                $map[Str::lower(trim((string) $name))] ??= $category->id;
            }
        }

        return $map;
    }

    /** @return array<int|string, int|string> name / short name => id, or id => name */
    protected function offices(bool $byId = false): array
    {
        if (! class_exists(self::OFFICE)) {
            return [];
        }
        $map = [];
        foreach ((self::OFFICE)::query()->get() as $office) {
            if ($byId) {
                $map[$office->id] = (string) $office->getTranslation('name', 'en', true);

                continue;
            }
            foreach ([...array_values($office->getTranslations('name')), $office->short_name ?? null] as $name) {
                if (filled($name)) {
                    $map[Str::lower(trim((string) $name))] ??= $office->id;
                }
            }
        }

        return $map;
    }
}
