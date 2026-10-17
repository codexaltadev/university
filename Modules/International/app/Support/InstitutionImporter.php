<?php

namespace Modules\International\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\International\Models\Country;
use Modules\International\Models\Institution;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Platform\Relations\RelationEngine;
use Throwable;

/**
 * Partner list import (FR-INTL-07): Excel or CSV with a header row — Name, Country (name or ISO
 * code), Website, Agreement, Signed, Expires. Checked first, errors by line; a name already there
 * for that country is updated, not doubled. New ones are shown on the main site.
 */
class InstitutionImporter
{
    /** @return array{rows: list<array<string, mixed>>, errors: list<string>, total: int} */
    public function check(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));
        $reader = $extension === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);
        $countries = Country::query()->get();
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
                            str_starts_with($word, 'name'), str_starts_with($word, 'institution'), str_starts_with($word, 'university') => 'name',
                            str_starts_with($word, 'country') => 'country',
                            str_starts_with($word, 'web'), $word === 'url' => 'website',
                            str_starts_with($word, 'agreement'), str_starts_with($word, 'type') => 'agreement',
                            str_starts_with($word, 'sign') => 'signed',
                            str_starts_with($word, 'expir'), str_starts_with($word, 'valid') => 'expires',
                            default => null,
                        };
                        if ($key !== null) {
                            $columns[$key] ??= $i;
                        }
                    }
                    if (! isset($columns['name'], $columns['country'])) {
                        $reader->close();

                        return ['rows' => [], 'errors' => [__('international::international.import.no_header')], 'total' => 0];
                    }

                    continue;
                }
                $value = fn (string $key): mixed => isset($columns[$key]) ? ($cells[$columns[$key]] ?? null) : null;
                $name = trim((string) $value('name'));
                if ($name === '' && trim((string) $value('country')) === '') {
                    continue;
                }
                $total++;
                $problem = function (string $why) use (&$errors, $line): void {
                    $errors[] = __('international::international.import.line', ['line' => $line, 'why' => $why]);
                };
                if ($name === '') {
                    $problem(__('international::international.import.no_name'));

                    continue;
                }
                $wanted = Str::lower(trim((string) $value('country')));
                $country = $countries->first(fn (Country $c): bool => Str::lower($c->iso2) === $wanted
                    || collect((array) $c->getTranslations('name'))->contains(fn ($n): bool => Str::lower((string) $n) === $wanted));
                if ($country === null) {
                    $problem(__('international::international.import.no_country', ['country' => $value('country')]));

                    continue;
                }
                $agreement = Str::snake(Str::lower(trim((string) $value('agreement'))));
                $agreement = in_array($agreement, Institution::AGREEMENTS, true) ? $agreement : ($agreement === '' ? 'mou' : 'other');
                $website = trim((string) $value('website'));
                if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
                    $website = 'https://'.$website;
                }
                [$signed, $expires] = [$this->date($value('signed')), $this->date($value('expires'))];
                if ($signed === false || $expires === false) {
                    $problem(__('international::international.import.bad_date'));

                    continue;
                }
                $rows[] = ['name' => $name, 'country_id' => $country->id, 'website_url' => $website ?: null, 'agreement_type' => $agreement,
                    'signed_on' => $signed?->toDateString(), 'expires_on' => $expires?->toDateString()];
            }
        }
        $reader->close();

        return ['rows' => $rows, 'errors' => $errors, 'total' => $total];
    }

    /** @param list<array<string, mixed>> $rows */
    public function save(array $rows): int
    {
        return DB::transaction(function () use ($rows): int {
            $locale = config('app.fallback_locale', 'en');
            foreach ($rows as $row) {
                $institution = Institution::query()->where('country_id', $row['country_id'])->where("name->{$locale}", $row['name'])->first();
                $institution ??= new Institution(['is_active' => true]);
                $institution->fill([...$row, 'name' => [$locale => $row['name']]])->save();
                if ($institution->wasRecentlyCreated) {
                    app(RelationEngine::class)->sync($institution, [], mainSite: true, user: auth()->user());
                }
            }

            return count($rows);
        });
    }

    protected function date(mixed $value): Carbon|false|null
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        try {
            return is_numeric($text) ? Carbon::create(1899, 12, 30)->addDays((int) $text) : Carbon::parse($text);
        } catch (Throwable) {
            return false;
        }
    }
}
