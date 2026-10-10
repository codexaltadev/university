<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Legacy\Migration\Support\IdMap;
use Modules\Program\Database\Seeders\ProgramDatabaseSeeder;
use Modules\Waiver\Support\WaiverCalculator;
use Modules\Waiver\Support\WaiverResult;
use PDO;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * Waiver Calculator golden master (Test Plan §7.1.1, step 4): load the project_diu
 * snapshot into the legacy test database, migrate it (S02, S04, S06, S07), replay the
 * answers the legacy calculator gave (tests/Fixtures/waiver-golden.json.gz, made by
 * tools/legacy-migration/golden/waiver-golden.php) through the new calculator.
 */
final class WaiverGolden
{
    public const CENT = 0.005;

    /** Snapshot → legacy test database → migrated into the test database. */
    public static function prepare(): void
    {
        $c = config('database.connections.legacy');
        $pdo = new PDO("mysql:host={$c['host']};port={$c['port']}", $c['username'], $c['password']);   // CREATE DATABASE outside the test transaction
        $pdo->exec("DROP DATABASE IF EXISTS `{$c['database']}`");
        $pdo->exec("CREATE DATABASE `{$c['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        DB::purge('legacy');
        DB::connection('legacy')->unprepared(gzdecode(file_get_contents(base_path('tests/Fixtures/legacy-fee-snapshot.sql.gz'))));

        (new ProgramDatabaseSeeder)->run();
        Artisan::call('legacy:migrate', ['--step' => ['S02', 'S04', 'S06', 'S07'], '--no-report' => true]);
    }

    public static function drop(): void
    {
        $c = config('database.connections.legacy');
        DB::purge('legacy');
        (new PDO("mysql:host={$c['host']};port={$c['port']}", $c['username'], $c['password']))->exec("DROP DATABASE IF EXISTS `{$c['database']}`");
    }

    /** @return list<array{in: array, out: array}> every $step-th case (1 = all) */
    public static function cases(int $step = 1): array
    {
        $cases = json_decode(gzdecode(file_get_contents(base_path('tests/Fixtures/waiver-golden.json.gz'))), true)['cases'];

        return $step === 1 ? $cases : array_values(array_filter($cases, fn (int $i): bool => $i % $step === 0, ARRAY_FILTER_USE_KEY));
    }

    /**
     * Replay cases; returns the differences (empty = 100% match).
     *
     * @param  list<array{in: array, out: array}>  $cases
     * @return list<string>
     */
    public static function differences(array $cases): array
    {
        $ids = app(IdMap::class);
        $calculator = app(WaiverCalculator::class);
        $levels = Term::query()->where('vocabulary_id', app(Vocabularies::class)->find('program.level')->id)->get()
            ->mapWithKeys(fn (Term $t): array => [$t->getTranslation('name', 'en') => $t->id]);
        $levelOf = [1 => $levels['Undergraduate'], 2 => $levels['Graduate']];
        $gender = [1 => 'male', 2 => 'female'];
        $diff = [];

        foreach ($cases as $n => ['in' => $in, 'out' => $out]) {
            $result = $calculator->calculate([
                'level_id' => $levelOf[$in['program_type_id']],
                'fee_category_id' => $ids->find('tuition_categories', $in['tuition_category_id']),
                'tuition_id' => $ids->find('tuitions', $in['tuition_id']),
                'waiver_category_id' => $ids->find('waiver_categories', $in['waiver_category_id']),
                'board_id' => $ids->find('boards', $in['board_id']),
                'gender' => $gender[$in['gender_id']],
                'ssc' => $in['ssc_result'] ?? null,
                'ssc_golden' => (bool) ($in['ssc_golden'] ?? false),
                'hsc' => $in['hsc_result'] ?? null,
                'hsc_golden' => (bool) ($in['hsc_golden'] ?? false),
                'ug_cgpa' => $in['undergraduate_result'] ?? $in['international_undergraduate_result'] ?? null,
            ]);

            if ($problem = self::compare($out, $result)) {
                $diff[] = "#{$n} ".json_encode($in).' → '.$problem;
            }
        }

        return $diff;
    }

    protected static function compare(array $out, WaiverResult $result): ?string
    {
        $expected = match ($out['status']) {
            200 => WaiverResult::OK,
            404 => WaiverResult::NO_WAIVER,
            400 => WaiverResult::NO_TUITION,
            default => 'legacy '.$out['status'],
        };

        if ($result->status !== $expected) {
            return "status {$result->status}, legacy {$expected}";
        }

        if (! $result->ok()) {
            return null;
        }

        $near = fn (float $a, ?float $b): bool => abs($a - (float) $b) < self::CENT;
        $min = $result->min;

        foreach (['rate' => $result->percent, 'total' => $min['total'], 'tuition' => $min['tuition'], 'other' => $min['other'], 'admission' => $min['admission'], 'waiver' => $min['waiver'], 'final' => $min['final']] as $key => $value) {
            if (! $near($out[$key], $value)) {
                return "{$key} ".json_encode($value).", legacy {$out[$key]}";
            }
        }

        if ($out['category'] !== $result->categoryName) {
            return "category \"{$result->categoryName}\", legacy \"{$out['category']}\"";
        }

        if (isset($out['max']) !== $result->hasRange()) {
            return 'range '.($result->hasRange() ? 'yes' : 'no').', legacy '.(isset($out['max']) ? 'yes' : 'no');
        }

        foreach ($out['max'] ?? [] as $key => $value) {
            if (! $near($value, $result->max[$key])) {
                return "max {$key} {$result->max[$key]}, legacy {$value}";
            }
        }

        return null;
    }
}
