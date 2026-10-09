<?php

/*
| Test Plan §7.1.1 — Waiver Calculator golden master, every case: the new calculator
| on migrated data answers exactly as DIU's legacy calculator did. Pass = 100% match.
*/

use Tests\Support\WaiverGolden;

afterEach(fn () => WaiverGolden::drop());

it('answers every golden case exactly as the legacy calculator', function () {
    WaiverGolden::prepare();
    $cases = WaiverGolden::cases();

    $differences = WaiverGolden::differences($cases);

    expect($cases)->toHaveCount(27648)
        ->and(array_slice($differences, 0, 15))->toBe([], count($differences).' of '.count($cases).' cases differ');
});
