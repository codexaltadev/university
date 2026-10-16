<?php

namespace Modules\Alumni\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Alumni\Models\Alumnus;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Bulk import and export (FR-ALUM-09), one column set both ways: Student ID, Name, Department,
 * Program, Batch, Passing year, Designation, Organization, Job type, Email, Phone, Pride, Active.
 * A row updates the alumnus with that student ID (or else that email); otherwise it adds one.
 * Department / Program by name or short name.
 */
class AlumniSheet
{
    public const COLUMNS = ['Student ID', 'Name', 'Department', 'Program', 'Batch', 'Passing year', 'Designation', 'Organization', 'Job type', 'Email', 'Phone', 'Pride', 'Active'];

    /** @param resource $out */
    public function export($out): void
    {
        fputcsv($out, self::COLUMNS);
        $departments = $this->names(Alumnus::DEPARTMENT, byId: true);
        $programs = $this->names(Alumnus::PROGRAM, byId: true);
        Alumnus::query()->orderBy('id')->chunk(500, function ($rows) use ($out, $departments, $programs): void {
            foreach ($rows as $a) {
                fputcsv($out, [
                    $a->student_id, $a->getTranslation('name', 'en', true), $departments[$a->department_id] ?? '', $programs[$a->program_id] ?? '',
                    $a->batch, $a->passing_year, $a->getTranslation('current_designation', 'en', true), $a->getTranslation('organization', 'en', true),
                    $a->job_type, $a->email, $a->phone, $a->is_pride ? 'yes' : 'no', $a->is_active ? 'yes' : 'no',
                ]);
            }
        });
    }

    /** @return array{rows: list<array<string, mixed>>, errors: list<string>, total: int} */
    public function check(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));
        $reader = $extension === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);

        $departments = $this->names(Alumnus::DEPARTMENT);
        $programs = $this->names(Alumnus::PROGRAM);
        $columns = null;
        $rows = $errors = [];
        $line = $total = 0;
        $yes = fn (string $v, bool $default): bool => $v === '' ? $default : in_array(Str::lower($v), ['yes', '1', 'true', 'y'], true);

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
                    if (! isset($columns['Name'])) {
                        $reader->close();

                        return ['rows' => [], 'errors' => [__('alumni::alumni.import.bad_header')], 'total' => 0];
                    }

                    continue;
                }
                if (implode('', $cells) === '') {
                    continue;
                }
                $total++;
                $get = fn (string $key): string => isset($columns[$key]) ? ($cells[$columns[$key]] ?? '') : '';
                $fail = function (string $why) use (&$errors, $line): void {
                    $errors[] = __('alumni::alumni.import.line', ['line' => $line, 'why' => $why]);
                };

                if ($get('Name') === '') {
                    $fail(__('alumni::alumni.import.no_name'));

                    continue;
                }
                $department = $get('Department') === '' ? null : ($departments[Str::lower($get('Department'))] ?? false);
                $program = $get('Program') === '' ? null : ($programs[Str::lower($get('Program'))] ?? false);
                if ($department === false || $program === false) {
                    $fail(__('alumni::alumni.import.unknown', ['name' => $department === false ? $get('Department') : $get('Program')]));

                    continue;
                }
                $job = Str::snake(Str::lower($get('Job type')));
                if ($job !== '' && ! in_array($job, Alumnus::JOB_TYPES, true)) {
                    $fail(__('alumni::alumni.import.unknown', ['name' => $get('Job type')]));

                    continue;
                }
                $year = $get('Passing year');
                if ($year !== '' && ! preg_match('/^(19|20)\d{2}$/', $year)) {
                    $fail(__('alumni::alumni.import.bad_year'));

                    continue;
                }
                if ($get('Email') !== '' && ! filter_var($get('Email'), FILTER_VALIDATE_EMAIL)) {
                    $fail(__('alumni::alumni.import.bad_email'));

                    continue;
                }

                $rows[] = [
                    'student_id' => Str::limit($get('Student ID'), 30, '') ?: null, 'name' => Str::limit($get('Name'), 150, ''),
                    'department_id' => $department, 'program_id' => $program, 'batch' => Str::limit($get('Batch'), 30, '') ?: null,
                    'passing_year' => $year !== '' ? (int) $year : null,
                    'current_designation' => $get('Designation') ?: null, 'organization' => $get('Organization') ?: null,
                    'job_type' => $job ?: null, 'email' => $get('Email') ?: null, 'phone' => Str::limit($get('Phone'), 50, '') ?: null,
                    'is_pride' => $yes($get('Pride'), false), 'is_active' => $yes($get('Active'), true),
                ];
            }

            break;   // the first sheet only
        }
        $reader->close();

        return ['rows' => $rows, 'errors' => $errors, 'total' => $total];
    }

    /** @return array{created: int, updated: int} */
    public function save(array $rows): array
    {
        return DB::transaction(function () use ($rows): array {
            $created = $updated = 0;
            foreach ($rows as $row) {
                $alumnus = ($row['student_id'] ? Alumnus::query()->where('student_id', $row['student_id'])->first() : null)
                    ?? ($row['email'] ? Alumnus::query()->where('email', $row['email'])->first() : null);
                $alumnus ? $updated++ : $created++;
                $alumnus ??= new Alumnus;

                $translated = fn (string $field) => $row[$field] !== null ? [...(array) $alumnus->getTranslations($field), 'en' => $row[$field]] : $alumnus->getTranslations($field);
                $alumnus->fill([
                    ...$row,
                    'name' => [...(array) $alumnus->getTranslations('name'), 'en' => $row['name']],
                    'current_designation' => $translated('current_designation') ?: null,
                    'organization' => $translated('organization') ?: null,
                ])->save();
            }

            return ['created' => $created, 'updated' => $updated];
        });
    }

    /** @return array<int|string, int|string> lower-case name / short name => id, or id => name */
    protected function names(string $class, bool $byId = false): array
    {
        if (! class_exists($class)) {
            return [];
        }
        $map = [];
        foreach ($class::query()->withoutGlobalScopes()->whereNull('deleted_at')->get() as $model) {
            if ($byId) {
                $map[$model->id] = (string) $model->getTranslation('name', 'en', true);

                continue;
            }
            foreach ([...array_values($model->getTranslations('name')), $model->short_name ?? null] as $name) {
                if (filled($name)) {
                    $map[Str::lower(trim((string) $name))] ??= $model->id;
                }
            }
        }

        return $map;
    }
}
