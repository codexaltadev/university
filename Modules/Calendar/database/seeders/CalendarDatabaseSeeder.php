<?php

namespace Modules\Calendar\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Calendar\Models\CalendarEvent;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * Required default data only, no demo content; must be safe to run twice.
 * FR-CAL-03: the date types an Admin starts with, each with a colour; the audiences
 * (DIU's `calender_programs`). An Admin's own lists stay as they are.
 */
class CalendarDatabaseSeeder extends Seeder
{
    /** @var array<string, array{0: string, 1: string, 2: string}> slug => [en, bn, colour] */
    public const TYPES = [
        'registration' => ['Registration', 'নিবন্ধন', '#034EA2'],
        'class' => ['Class', 'ক্লাস', '#39B54A'],
        'exam' => ['Exam', 'পরীক্ষা', '#D97706'],
        'result' => ['Result', 'ফলাফল', '#7C3AED'],
        'holiday' => ['Holiday', 'ছুটি', '#DC2626'],
        'other' => ['Other', 'অন্যান্য', '#6B7280'],
    ];

    public function run(): void
    {
        $vocabularies = app(Vocabularies::class);

        if ($vocabularies->options(CalendarEvent::TYPE) === []) {
            Vocabularies::ensureTerms(CalendarEvent::TYPE, collect(self::TYPES)
                ->map(fn (array $t, string $slug): array => ['name' => ['en' => $t[0], 'bn' => $t[1]], 'slug' => $slug])->values()->all());

            $vocabulary = $vocabularies->find(CalendarEvent::TYPE)->id;
            foreach (self::TYPES as [$en, , $color]) {
                Term::query()->where('vocabulary_id', $vocabulary)->get()
                    ->first(fn (Term $t): bool => $t->getTranslation('name', 'en', false) === $en)
                    ?->update(['color' => $color]);
            }
        }

        if ($vocabularies->options(CalendarEvent::AUDIENCE) === []) {
            Vocabularies::ensureTerms(CalendarEvent::AUDIENCE, [
                ['name' => ['en' => 'Undergraduate', 'bn' => 'স্নাতক'], 'slug' => 'undergraduate'],
                ['name' => ['en' => 'Graduate', 'bn' => 'স্নাতকোত্তর'], 'slug' => 'graduate'],
            ]);
        }
    }
}
