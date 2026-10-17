<?php

namespace Modules\Newsletter\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Newsletter\Models\Subscriber;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Writer as CsvWriter;

/**
 * Subscriber import / export (FR-NLT-04): CSV or Excel with Email (and Name). Imported ones are
 * subscribed to the chosen lists; an email that unsubscribed stays unsubscribed (BRL-NLT-02).
 */
class SubscriberImporter
{
    /** @return array{rows: list<array{email: string, name: ?string}>, errors: list<string>, total: int} */
    public function check(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));
        $reader = $extension === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);
        $columns = null;
        $rows = $errors = $seen = [];
        $line = $total = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $cells = $row->toArray();
                if ($columns === null) {
                    $columns = [];
                    foreach ($cells as $i => $cell) {
                        $word = Str::lower(trim((string) $cell));
                        if (str_contains($word, 'mail')) {
                            $columns['email'] ??= $i;
                        } elseif (str_starts_with($word, 'name')) {
                            $columns['name'] ??= $i;
                        }
                    }
                    if (! isset($columns['email'])) {
                        $reader->close();

                        return ['rows' => [], 'errors' => [__('newsletter::newsletter.import.no_header')], 'total' => 0];
                    }

                    continue;
                }
                $email = Subscriber::normalize((string) ($cells[$columns['email']] ?? ''));
                if ($email === '') {
                    continue;
                }
                $total++;
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = __('newsletter::newsletter.import.line', ['line' => $line, 'why' => __('newsletter::newsletter.errors.email')]);

                    continue;
                }
                if (isset($seen[$email])) {
                    continue;
                }
                $seen[$email] = true;
                $name = isset($columns['name']) ? trim((string) ($cells[$columns['name']] ?? '')) : '';
                $rows[] = ['email' => $email, 'name' => $name !== '' ? mb_substr($name, 0, 191) : null];
            }
        }
        $reader->close();

        return ['rows' => $rows, 'errors' => $errors, 'total' => $total];
    }

    /**
     * @param  list<array{email: string, name: ?string}>  $rows
     * @param  list<int>  $listIds
     * @return array{added: int, kept: int}
     */
    public function save(array $rows, array $listIds, string $source = 'import'): array
    {
        return DB::transaction(function () use ($rows, $listIds, $source): array {
            $added = $kept = 0;
            foreach ($rows as $row) {
                $subscriber = Subscriber::query()->firstOrNew(['email' => $row['email']]);
                if ($subscriber->exists && $subscriber->status === 'unsubscribed') {   // suppression (BRL-NLT-02)
                    $kept++;

                    continue;
                }
                if (! $subscriber->exists) {
                    $subscriber->fill(['name' => $row['name'], 'status' => 'subscribed', 'verified_at' => now(), 'source' => $source, 'is_active' => true])->save();
                    $added++;
                }
                $subscriber->join($listIds);
            }

            return ['added' => $added, 'kept' => $kept];
        });
    }

    /** Export (FR-NLT-04): email, name, status, lists, source, dates — as CSV. */
    public function export(string $path, ?int $listId = null): int
    {
        $writer = new CsvWriter;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['Email', 'Name', 'Status', 'Lists', 'Source', 'Subscribed', 'Unsubscribed']));
        $count = 0;
        Subscriber::query()->with('lists')->when($listId, fn ($q) => $q->whereHas('lists', fn ($l) => $l->whereKey($listId)))
            ->orderBy('id')->chunk(500, function ($subscribers) use ($writer, &$count): void {
                foreach ($subscribers as $s) {
                    $writer->addRow(Row::fromValues([$s->email, (string) $s->name, $s->status, $s->lists->pluck('key')->implode(', '), $s->source,
                        $s->verified_at?->toDateTimeString() ?? '', $s->unsubscribed_at?->toDateTimeString() ?? '']));
                    $count++;
                }
            });
        $writer->close();

        return $count;
    }
}
