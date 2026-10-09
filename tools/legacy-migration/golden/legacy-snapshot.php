<?php

/*
| A small copy of project_diu for the golden master in CI (no legacy database there):
| the tables S02, S04, S06 and S07 read — structure of all, rows of the ones the
| calculator depends on; large rich text left out.
|
|   php tools/legacy-migration/golden/legacy-snapshot.php tests/Fixtures/legacy-fee-snapshot.sql.gz
|
| Runs in this app through the "legacy" connection (read only).
*/

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$out = $argv[1] ?? __DIR__.'/../../../tests/Fixtures/legacy-fee-snapshot.sql.gz';
$legacy = DB::connection('legacy');

/** table => columns to blank (rich text the calculator does not need); null = structure only */
$tables = [
    'faculties' => ['faculty_content'],
    'departments' => ['department_content'],
    'program_major_categories' => [],
    'program_types' => [],
    'department_program_type' => ['summary', 'program_objective', 'program_credit_duration', 'ai_summary', 'ai_short_summary'],
    'tuition_categories' => [],
    'tuitions' => ['majors'],
    'boards' => [],
    'waiver_categories' => [],
    'waivers' => [],
    'board_waiver' => [],
    'gender_waiver' => [],
    'tuition_waiver' => [],
    'waiver_waiver_category' => [],
    'faculty_waiver' => [],
    'eligibilities' => null,
    'program_majors' => null,
    'program_career_paths' => null,
    'program_industries' => null,
    'program_facilitators' => null,
    'program_materials' => null,
    'program_professional_memberships' => null,
    'program_variants' => null,
];

$sql = "SET FOREIGN_KEY_CHECKS=0;\n";
foreach ($tables as $table => $blank) {
    $create = (array) $legacy->selectOne("SHOW CREATE TABLE `{$table}`");
    $create = array_values($create)[1];
    // No foreign keys: the referenced tables (users …) are not in the snapshot
    $create = preg_replace('/^\s*CONSTRAINT `[^`]+` FOREIGN KEY.*$\n?/m', '', $create);
    $create = preg_replace('/,(\s*\n\))/', '$1', $create);
    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n";

    if ($blank === null) {
        continue;
    }

    foreach ($legacy->table($table)->get()->chunk(200) as $chunk) {
        $rows = $chunk->map(function ($row) use ($blank, $legacy) {
            $row = (array) $row;
            foreach ($blank as $column) {
                $row[$column] = $row[$column] === null ? null : '';   // some are NOT NULL
            }

            return '('.implode(',', array_map(fn ($v) => $v === null ? 'NULL' : $legacy->getPdo()->quote((string) $v), $row)).')';
        });
        $columns = implode(',', array_map(fn ($c) => "`{$c}`", array_keys((array) $chunk->first())));
        $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES\n".$rows->implode(",\n").";\n";
    }
}
$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

file_put_contents($out, gzencode($sql, 9));
fwrite(STDERR, count($tables).' tables → '.$out.' ('.strlen($sql).' bytes)'.PHP_EOL);
