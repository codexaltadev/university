<?php

namespace Modules\Faq\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Faq\Models\Faq;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * FAQ import (FR-FAQ-06): Excel or CSV with a header row — Question, Answer, Category
 * (optional, matched by name; a new name becomes a category). Checked first: rows without a
 * question or answer are reported by line. Saved FAQs are shown where the importer chose.
 */
class FaqImporter
{
    /**
     * @return array{rows: list<array{question: string, answer: string, category: ?string}>, errors: list<string>, total: int}
     */
    public function check(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));
        $reader = $extension === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);

        $columns = null;
        $rows = $errors = [];
        $line = $total = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $cells = array_map(fn ($v): string => trim((string) $v), $row->toArray());

                if ($columns === null) {
                    $columns = [];
                    foreach ($cells as $i => $cell) {
                        $word = Str::lower($cell);
                        $key = match (true) {
                            str_starts_with($word, 'question') => 'question',
                            str_starts_with($word, 'answer') => 'answer',
                            str_starts_with($word, 'categor') => 'category',
                            default => null,
                        };
                        if ($key !== null) {
                            $columns[$key] ??= $i;
                        }
                    }
                    if (! isset($columns['question'], $columns['answer'])) {
                        $reader->close();

                        return ['rows' => [], 'errors' => [__('faq::faq.import.bad_header')], 'total' => 0];
                    }

                    continue;
                }

                if (implode('', $cells) === '') {
                    continue;
                }
                $total++;
                $get = fn (string $key): string => isset($columns[$key]) ? ($cells[$columns[$key]] ?? '') : '';

                if ($get('question') === '' || $get('answer') === '') {
                    $errors[] = __('faq::faq.import.line', ['line' => $line]);

                    continue;
                }

                $rows[] = ['question' => Str::limit($get('question'), 500, ''), 'answer' => $get('answer'), 'category' => $get('category') ?: null];
            }

            break;   // the first sheet only
        }

        $reader->close();

        return ['rows' => $rows, 'errors' => $errors, 'total' => $total];
    }

    /**
     * @param  list<array{question: string, answer: string, category: ?string}>  $rows
     * @param  list<string>  $targets  "Show in" keys
     */
    public function save(array $rows, array $targets, bool $mainSite): int
    {
        return DB::transaction(function () use ($rows, $targets, $mainSite): int {
            $engine = app(RelationEngine::class);
            $terms = [];

            foreach ($rows as $position => $row) {
                $answer = str_contains($row['answer'], '<') ? $row['answer'] : '<p>'.nl2br(e($row['answer'])).'</p>';
                $faq = Faq::create(['question' => ['en' => $row['question']], 'answer' => ['en' => $answer], 'sort_order' => $position + 1, 'is_active' => true]);

                if ($row['category']) {
                    $terms[$row['category']] ??= $this->term($row['category']);
                    $faq->syncTerms(Faq::CATEGORY, [$terms[$row['category']]]);
                }

                $engine->sync($faq, $targets, mainSite: $mainSite);
            }

            return count($rows);
        });
    }

    protected function term(string $name): int
    {
        $find = fn (): ?int => Term::query()->where('vocabulary_id', app(Vocabularies::class)->find(Faq::CATEGORY)->id)
            ->get()->first(fn (Term $t): bool => Str::lower((string) $t->getTranslation('name', 'en', false)) === Str::lower($name))?->id;

        if ($id = $find()) {
            return $id;
        }
        Vocabularies::ensureTerms(Faq::CATEGORY, [['name' => ['en' => $name], 'slug' => Str::slug($name)]]);

        return (int) $find();
    }
}
