<?php

/*
| Test Plan §7.1.1 — a fast sample of the waiver golden master (every 25th case) on
| every push; tests/Golden runs all 27,648 nightly.
*/

use Tests\Support\WaiverGolden;

afterEach(fn () => WaiverGolden::drop());

it('answers a sample of the golden cases exactly as the legacy calculator', function () {
    WaiverGolden::prepare();
    $cases = WaiverGolden::cases(25);

    $differences = WaiverGolden::differences($cases);

    expect(count($cases))->toBeGreaterThan(1000)
        ->and(array_slice($differences, 0, 15))->toBe([], count($differences).' of '.count($cases).' cases differ');
});
