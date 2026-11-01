<?php

namespace Modules\Admission\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admission\Models\AdmissionSeat;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Seat plan upload (FR-ADM-17): an Excel or CSV file with a header row — Application serial, Student name, Program,
 * Building, Room, Level — checked first (rows without a serial, name, building or room, or a serial twice, are reported
 * by line and left out), then saved in bulk in place of the whole earlier seat plan.
 */
class SeatImporter
{
    /** Header words → column. */
    protected const HEADERS = [
        'application_serial' => ['application serial', 'serial', 'application no', 'application number', 'roll'],
        'student_name' => ['student name', 'name', 'applicant name'],
        'program' => ['program', 'programme', 'program name'],
        'building' => ['building', 'building name', 'venue'],
        'room' => ['room', 'room name', 'room no', 'room number'],
        'floor' => ['level', 'floor'],
    ];

    /** @return array{rows: list<array<string, mixed>>, errors: list<string>, total: int} */
    public function check(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));
        $reader = $extension === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);

        $columns = null;
        $rows = $errors = $seen = [];
        $line = $total = 0;
        $programs = app(ResultImporter::class)->programs();

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $cells = array_map(fn ($v): string => trim((string) $v), $row->toArray());

                if ($columns === null) {
                    $columns = $this->columns($cells);
                    if (! isset($columns['application_serial'], $columns['student_name'], $columns['building'], $columns['room'])) {
                        $reader->close();

                        return ['rows' => [], 'errors' => [__('admission::admission.seats.bad_header')], 'total' => 0];
                    }

                    continue;
                }
                if (implode('', $cells) === '') {
                    continue;
                }
                $total++;

                $get = fn (string $key): string => isset($columns[$key]) ? ($cells[$columns[$key]] ?? '') : '';
                $serial = $get('application_serial');
                $problem = match (true) {
                    $serial === '' => __('admission::admission.import.no_serial'),
                    $get('student_name') === '' => __('admission::admission.import.no_name'),
                    $get('building') === '' || $get('room') === '' => __('admission::admission.seats.no_room'),
                    isset($seen[mb_strtolower($serial)]) => __('admission::admission.import.twice', ['serial' => $serial, 'line' => $seen[mb_strtolower($serial)]]),
                    default => null,
                };
                if ($problem !== null) {
                    $errors[] = __('admission::admission.import.line', ['line' => $line, 'problem' => $problem]);

                    continue;
                }

                $seen[mb_strtolower($serial)] = $line;
                $rows[] = static::row($serial, $get('student_name'), $get('program'), $get('building'), $get('room'), $get('floor'), $programs);
            }

            break;   // the first sheet only
        }

        $reader->close();

        return ['rows' => $rows, 'errors' => $errors, 'total' => $total];
    }

    /** @param array<string, int> $programs programKey → id */
    public static function row(string $serial, string $name, ?string $program, string $building, string $room, ?string $floor, array $programs = []): array
    {
        return [
            'application_serial' => Str::limit(trim($serial), 50, ''),
            'student_name' => Str::limit(trim($name), 191, ''),
            'program_label' => filled($program) ? Str::limit(trim($program), 188, '') : null,
            'program_id' => filled($program) ? ($programs[ResultImporter::programKey($program)] ?? null) : null,
            'building' => Str::limit(trim($building), 150, ''),
            'room' => Str::limit(trim($room), 50, ''),
            'floor' => filled($floor) ? Str::limit(trim($floor), 50, '') : null,
        ];
    }

    /**
     * The new seat plan in place of the old one.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function save(array $rows): int
    {
        DB::transaction(function () use ($rows): void {
            AdmissionSeat::query()->withoutGlobalScopes()->forceDelete();

            $now = now();
            foreach (array_chunk($rows, 500) as $chunk) {
                AdmissionSeat::query()->insert(array_map(fn (array $row): array => [
                    ...$row, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now, 'created_by' => auth()->id(), 'updated_by' => auth()->id(),
                ], $chunk));
            }
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
}
