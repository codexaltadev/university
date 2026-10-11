<?php

namespace Modules\Admission\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admission\Models\AdmissionResult;
use Modules\Admission\Models\ResultBatch;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Platform\Scope\UserScopeFilter;

/**
 * Result upload (FR-ADM-05): an Excel or CSV file with a header row — Program, Application
 * serial, Student name, Result — read into a batch. Checked first (preview): rows with a
 * missing serial or name, an unknown result or a serial twice are reported by line and left
 * out. Saved in bulk (thousands of rows), replacing the batch's earlier results.
 */
class ResultImporter
{
    /** Header words → column. */
    protected const HEADERS = [
        'program' => ['program', 'programme', 'program name'],
        'application_serial' => ['application serial', 'serial', 'application no', 'application number', 'roll'],
        'student_name' => ['student name', 'name', 'applicant name'],
        'result' => ['result', 'status'],
    ];

    /**
     * Read and check a file.
     *
     * @return array{rows: list<array<string, mixed>>, errors: list<string>, total: int}
     */
    public function check(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));
        $reader = $extension === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);

        $columns = null;
        $rows = $errors = $seen = [];
        $line = $total = 0;
        $programs = $this->programs();

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $cells = array_map(fn ($v): string => trim((string) $v), $row->toArray());

                if ($columns === null) {
                    $columns = $this->columns($cells);
                    if (! isset($columns['application_serial'], $columns['student_name'], $columns['result'])) {
                        $reader->close();

                        return ['rows' => [], 'errors' => [__('admission::admission.import.bad_header')], 'total' => 0];
                    }

                    continue;
                }

                if (implode('', $cells) === '') {
                    continue;
                }
                $total++;

                $get = fn (string $key): string => isset($columns[$key]) ? ($cells[$columns[$key]] ?? '') : '';
                $serial = $get('application_serial');
                $name = $get('student_name');
                $result = AdmissionResult::normalize($get('result'));

                $problem = match (true) {
                    $serial === '' => __('admission::admission.import.no_serial'),
                    $name === '' => __('admission::admission.import.no_name'),
                    $result === null => __('admission::admission.import.bad_result', ['result' => $get('result')]),
                    isset($seen[mb_strtolower($serial)]) => __('admission::admission.import.twice', ['serial' => $serial, 'line' => $seen[mb_strtolower($serial)]]),
                    default => null,
                };
                if ($problem !== null) {
                    $errors[] = __('admission::admission.import.line', ['line' => $line, 'problem' => $problem]);

                    continue;
                }

                $seen[mb_strtolower($serial)] = $line;
                $program = $get('program');
                $rows[] = [
                    'program_label' => $program !== '' ? Str::limit($program, 188, '') : null,
                    'program_id' => $programs[static::programKey($program)] ?? null,
                    'application_serial' => Str::limit($serial, 50, ''),
                    'student_name' => Str::limit($name, 191, ''),
                    'result' => $result,
                    'result_label' => Str::limit($get('result'), 100, ''),
                ];
            }

            break;   // the first sheet only
        }

        $reader->close();

        return ['rows' => $rows, 'errors' => $errors, 'total' => $total];
    }

    /**
     * Save checked rows into the batch, in place of its earlier results.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function save(ResultBatch $batch, array $rows): int
    {
        DB::transaction(function () use ($batch, $rows): void {
            $batch->results()->withTrashed()->forceDelete();

            $now = now();
            foreach (array_chunk($rows, 500) as $chunk) {
                AdmissionResult::query()->insert(array_map(fn (array $row): array => [
                    ...$row, 'batch_id' => $batch->id, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                    'created_by' => auth()->id(), 'updated_by' => auth()->id(),
                ], $chunk));
            }

            $batch->update(['imported_rows' => count($rows), 'imported_at' => $now]);
        });

        return count($rows);
    }

    /**
     * @param  list<string>  $cells
     * @return array<string, int>
     */
    protected function columns(array $cells): array
    {
        $found = [];
        foreach ($cells as $index => $cell) {
            $word = Str::of($cell)->lower()->replace(['_', '-', '.'], ' ')->squish()->value();
            foreach (self::HEADERS as $column => $words) {
                if (! isset($found[$column]) && in_array($word, $words, true)) {
                    $found[$column] = $index;
                }
            }
        }

        return $found;
    }

    /** @return array<string, int> program name / short name (as programKey()) → id, when the Program module is active */
    public function programs(): array
    {
        $class = 'Modules\\Program\\Models\\Program';
        if (! class_exists($class) || ! $class::moduleIsActive()) {
            return [];
        }

        $names = [];
        foreach ($class::query()->withoutGlobalScope(UserScopeFilter::class)->get() as $program) {   // every program, whoever imports
            foreach ((array) $program->getTranslations('name') as $name) {
                $names[static::programKey((string) $name)] = $program->id;
                // without the short form at the end: "Bachelor of Business Administration (BBA)"
                $names[static::programKey((string) preg_replace('/\s*\([^()]*\)\s*$/', '', (string) $name))] ??= $program->id;
            }
            if (filled($program->short_name ?? null)) {
                $names[static::programKey((string) $program->short_name)] ??= $program->id;
            }
        }

        return $names;
    }

    /** "B.Sc. (Hon's) in Computer Science & Engineering" and "BSc in Computer Science and Engineering" → one key. */
    public static function programKey(string $name): string
    {
        $name = mb_strtolower($name);
        $name = str_replace('&', ' and ', $name);
        $name = preg_replace("/\(\s*hon'?s\.?\s*\)|\bhon'?s\b\.?/u", ' ', $name);

        return (string) preg_replace('/[^a-z0-9]+/u', '', $name);
    }
}
