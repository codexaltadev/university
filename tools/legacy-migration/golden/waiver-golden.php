<?php

/*
| Waiver Calculator golden master (Test Plan §7.1.1, step 2–3).
|
|   php tools/legacy-migration/golden/waiver-golden.php /home/Project/diu tests/Fixtures/waiver-golden.json.gz
|
| Runs INSIDE the legacy DIU app (its own database, project_diu): builds the inputs the
| live form can send — every published tuition and active waiver category, boards and
| genders the form offers, and results at every rule's limits (just below, at, just above;
| golden only where the GPA is 5) — and records what the legacy WaiverApiController::
| calculate() answers. tests/Golden/WaiverGoldenTest.php replays them on the new module.
*/

use App\Http\Controllers\Api\V1\Public\WaiverApiController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$legacyPath = $argv[1] ?? '/home/Project/diu';
$out = $argv[2] ?? __DIR__.'/../../../tests/Fixtures/waiver-golden.json.gz';

require $legacyPath.'/vendor/autoload.php';
$app = require $legacyPath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$rules = DB::table('waivers')->where('is_active', '1')->whereNull('deleted_at')->get();
$limits = function (string $min, string $max, float $low, float $high, array $extra) use ($rules): array {
    $values = $extra;
    foreach ($rules as $r) {
        foreach ([$r->{$min}, $r->{$max}] as $v) {
            if ($v !== null) {
                array_push($values, (float) $v, round((float) $v - 0.01, 2), round((float) $v + 0.01, 2));
            }
        }
    }

    return collect($values)->filter(fn ($v) => $v >= $low && $v <= $high)->unique()->sort()->values()->all();
};

// What the form offers (WaiverApiController: programTypes, boards, waiverCategory, programs)
$categories = fn (int $type) => DB::table('waiver_categories')->where('is_active', '1')->where('program_type_id', $type)->whereNull('deleted_at')->pluck('id')->all();
$boards = function (int $tc, int $type): array {
    $q = DB::table('boards')->where('is_active', '1')->where('program_type_id', $type)->whereNull('deleted_at');
    if ($tc === 2 && $type === 2) {
        $q->whereIn('id', [8]);
    } elseif ($tc === 1 && $type === 2) {
        $q->whereNotIn('id', [8]);
    }

    return $q->pluck('id')->all();
};
$tuitions = fn (int $tc, int $type) => DB::table('tuitions')->where('publication_status', 1)->where('tuition_category_id', $tc)->where('program_type_id', $type)->orderBy('id')->pluck('id')->all();

$cases = [];
$add = function (array $input) use (&$cases): void {
    $cases[json_encode($input)] = $input;
};

$hsc = $limits('hsc_min', 'hsc_max', 0, 5, [5.0, 4.5, 3.0]);
$ssc = [2.49, 2.5, 4.0, 5.0];
$ug = $limits('undergraduate_min', 'undergraduate_max', 0, 4, [3.0, 3.95]);
$intl = $limits('international_undergraduate_min', 'international_undergraduate_max', 0, 4, [3.0, 3.95]);

foreach ([[1, 1], [1, 2], [2, 2]] as [$tc, $type]) {
    $list = $tuitions($tc, $type);
    $n = 0;
    foreach ($categories($type) as $cat) {
        foreach ($boards($tc, $type) as $board) {
            foreach ([1, 2] as $gender) {
                // two tuitions per combination, rotating through all of them
                $pick = [$list[$n++ % count($list)], $list[$n++ % count($list)]];
                $base = ['program_type_id' => $type, 'tuition_category_id' => $tc, 'waiver_category_id' => $cat, 'board_id' => $board, 'gender_id' => $gender];

                foreach ($pick as $tuition) {
                    if ($tc === 1 && $type === 1) {
                        foreach ($ssc as $s) {
                            foreach ($hsc as $h) {
                                foreach ([false, true] as $sg) {
                                    foreach ([false, true] as $hg) {
                                        if (($sg && $s != 5.0) || ($hg && $h != 5.0)) {
                                            continue;   // the form enables golden only at GPA 5
                                        }
                                        $add([...$base, 'tuition_id' => $tuition, 'ssc_result' => $s, 'hsc_result' => $h] + ($sg ? ['ssc_golden' => 1] : []) + ($hg ? ['hsc_golden' => 1] : []));
                                    }
                                }
                            }
                        }
                    } elseif ($tc === 1) {
                        foreach ($ug as $g) {
                            $add([...$base, 'tuition_id' => $tuition, 'undergraduate_result' => $g]);
                        }
                    } else {
                        foreach ($intl as $g) {
                            $add([...$base, 'tuition_id' => $tuition, 'international_undergraduate_result' => $g]);
                        }
                    }
                }
            }
        }
    }

    // Every tuition with every category at two typical results
    foreach ($list as $tuition) {
        foreach ($categories($type) as $cat) {
            $base = ['program_type_id' => $type, 'tuition_category_id' => $tc, 'waiver_category_id' => $cat, 'tuition_id' => $tuition, 'board_id' => $boards($tc, $type)[0], 'gender_id' => 2];
            if ($tc === 1 && $type === 1) {
                $add([...$base, 'ssc_result' => 5.0, 'hsc_result' => 5.0, 'ssc_golden' => 1, 'hsc_golden' => 1]);
                $add([...$base, 'ssc_result' => 4.0, 'hsc_result' => 4.5]);
            } else {
                $add([...$base, $tc === 1 ? 'undergraduate_result' : 'international_undergraduate_result' => 3.95]);
                $add([...$base, $tc === 1 ? 'undergraduate_result' : 'international_undergraduate_result' => 3.2]);
            }
        }
    }
}

$controller = app(WaiverApiController::class);
$golden = [];
foreach ($cases as $input) {
    $response = $controller->calculate(Request::create('/api/v1/public/waiver/calculate', 'POST', $input));
    $data = $response->getData(true);
    $answer = ['status' => $response->getStatusCode()];

    if ($response->getStatusCode() === 200) {
        $d = $data['data'];
        $answer += [
            'rate' => (float) $d['waiver_rate'], 'category' => $d['category_name'],
            'total' => (float) $d['fees']['total_cost'], 'tuition' => (float) $d['fees']['tuition_fees'], 'other' => (float) $d['fees']['other_fees'],
            'admission' => (float) $d['fees']['admission_fees'], 'waiver' => (float) $d['fees']['waiver_amount'], 'final' => (float) $d['fees']['final_cost_after_waiver'],
        ];
        if ($d['has_range_values']) {
            $answer['max'] = [
                'total' => (float) $d['fees']['total_cost_range']['max'], 'tuition' => (float) $d['fees']['tuition_fees_range']['max'],
                'other' => (float) $d['fees']['other_fees_range']['max'], 'final' => (float) $d['fees']['final_cost_after_waiver_range']['max'],
            ];
        }
    } else {
        $answer['message'] = $data['message'] ?? null;
    }

    $golden[] = ['in' => $input, 'out' => $answer];
}

$statuses = collect($golden)->countBy(fn ($g) => $g['out']['status'])->all();
file_put_contents($out, gzencode(json_encode(['generated' => date('c'), 'source' => 'project_diu via '.$legacyPath, 'cases' => $golden], JSON_UNESCAPED_UNICODE), 9));
fwrite(STDERR, count($golden).' cases → '.$out.' '.json_encode($statuses).PHP_EOL);
