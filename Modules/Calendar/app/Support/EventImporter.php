<?php

namespace Modules\Calendar\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;
use Throwable;

/**
 * Calendar dates import (FR-CAL-08): Excel or CSV with a header row — Title, Start, End,
 * Date (words), Type, Audience (several with commas). Start / End are Excel dates or text
 * ("9 May 2026"); without them the Date words are read as dates when they can be, else kept
 * as words. Type and audience are matched by name. Checked first, errors by line; the new
 * dates come after the calendar's own.
 */
class EventImporter
{
    /**
     * @return array{rows: list<array<string, mixed>>, errors: list<string>, total: int}
     */
    public function check(Calendar $calendar, string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));
        $reader = $extension === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);

        $types = $this->terms(CalendarEvent::TYPE);
        $audiences = $this->terms(CalendarEvent::AUDIENCE);
        $columns = null;
        $rows = $errors = [];
        $line = $total = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $cells = $row->toArray();

                if ($columns === null) {
                    $columns = [];
                    foreach ($cells as $i => $cell) {
                        $word = Str::lower(trim((string) $cell));
                        $key = match (true) {
                            str_starts_with($word, 'title'), str_starts_with($word, 'event') => 'title',
                            str_starts_with($word, 'start'), $word === 'from' => 'start',
                            str_starts_with($word, 'end'), $word === 'to' => 'end',
                            str_starts_with($word, 'date') => 'words',
                            str_starts_with($word, 'type') => 'type',
                            str_starts_with($word, 'audience') => 'audience',
                            default => null,
                        };
                        if ($key !== null) {
                            $columns[$key] ??= $i;
                        }
                    }
                    if (! isset($columns['title']) || ! (isset($columns['start']) || isset($columns['words']))) {
                        $reader->close();

                        return ['rows' => [], 'errors' => [__('calendar::calendar.import.bad_header')], 'total' => 0];
                    }

                    continue;
                }

                $get = fn (string $key): mixed => isset($columns[$key]) ? ($cells[$columns[$key]] ?? null) : null;
                if (implode('', array_map(fn ($v): string => $v instanceof DateTimeInterface ? 'd' : trim((string) $v), $cells)) === '') {
                    continue;
                }
                $total++;
                $problem = function (string $why) use (&$errors, $line): void {
                    $errors[] = __('calendar::calendar.import.line', ['line' => $line, 'why' => $why]);
                };

                $title = trim((string) $get('title'));
                $words = trim((string) ($get('words') instanceof DateTimeInterface ? '' : $get('words')));
                $start = $this->date($get('start'), $calendar->year);
                $end = $this->date($get('end'), $calendar->year);
                if ($title === '') {
                    $problem(__('calendar::calendar.import.no_title'));

                    continue;
                }
                if ($start === false || $end === false) {
                    $problem(__('calendar::calendar.import.bad_date'));

                    continue;
                }
                if ($start === null && $words !== '' && ($parsed = DateText::parse($words, $calendar->year))) {
                    [$start, $end, $words] = [$parsed['starts_on'], $parsed['ends_on'], $parsed['rest']];
                }
                if ($start === null && $words === '') {
                    $problem(__('calendar::calendar.errors.no_date'));

                    continue;
                }
                if ($end && (! $start || $end->lt($start))) {
                    $problem(__('calendar::calendar.errors.end_before_start'));

                    continue;
                }

                $type = trim((string) $get('type'));
                $typeId = $type === '' ? null : ($types[Str::lower($type)] ?? false);
                if ($typeId === false) {
                    $problem(__('calendar::calendar.import.unknown', ['name' => $type]));

                    continue;
                }
                $audienceIds = [];
                foreach (array_filter(array_map('trim', explode(',', (string) $get('audience')))) as $name) {
                    if (! isset($audiences[Str::lower($name)])) {
                        $problem(__('calendar::calendar.import.unknown', ['name' => $name]));

                        continue 2;
                    }
                    $audienceIds[] = $audiences[Str::lower($name)];
                }

                $rows[] = [
                    'title' => Str::limit($title, 255, ''), 'starts_on' => $start?->toDateString(), 'ends_on' => $end?->toDateString(),
                    'date_label' => $words !== '' ? Str::limit($words, 255, '') : null, 'type_term_id' => $typeId, 'audience' => $audienceIds,
                ];
            }

            break;   // the first sheet only
        }

        $reader->close();

        return ['rows' => $rows, 'errors' => $errors, 'total' => $total];
    }

    /** @param list<array<string, mixed>> $rows */
    public function save(Calendar $calendar, array $rows): int
    {
        return DB::transaction(function () use ($calendar, $rows): int {
            $position = (int) $calendar->events()->withTrashed()->max('sort_order');
            foreach ($rows as $row) {
                $event = $calendar->events()->create([
                    'title' => ['en' => $row['title']], 'starts_on' => $row['starts_on'], 'ends_on' => $row['ends_on'],
                    'date_label' => $row['date_label'] ? ['en' => $row['date_label']] : null,
                    'type_term_id' => $row['type_term_id'], 'sort_order' => ++$position, 'is_active' => true,
                ]);
                if ($row['audience'] !== []) {
                    $event->syncTerms(CalendarEvent::AUDIENCE, $row['audience']);
                }
            }

            return count($rows);
        });
    }

    /** A cell as a date: null when empty, false when it is not a date. */
    protected function date(mixed $value, int $year): Carbon|false|null
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return Carbon::createFromFormat('!Y-m-d', $value);
            } catch (Throwable) {
                return false;
            }
        }
        $parsed = DateText::parse($value, $year);

        return $parsed && $parsed['ends_on'] === null && $parsed['rest'] === '' ? $parsed['starts_on'] : false;
    }

    /** @return array<string, int> lower-case name (every language) => term id */
    protected function terms(string $vocabulary): array
    {
        $map = [];
        $terms = Term::query()->where('vocabulary_id', app(Vocabularies::class)->find($vocabulary)->id)->get();
        foreach ($terms as $term) {
            foreach ((array) $term->getTranslations('name') as $name) {
                $map[Str::lower(trim((string) $name))] ??= $term->id;
            }
        }

        return $map;
    }
}
