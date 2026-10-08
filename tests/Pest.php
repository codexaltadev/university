<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Module statuses for tests live in their own file (phpunit.xml: MODULES_STATUSES_FILE),
| so enabling / disabling a module in a test never changes the real modules_statuses.json.
*/
$statuses = __DIR__.'/../storage/framework/testing/modules_statuses.json';
@mkdir(dirname($statuses), 0775, true);
file_put_contents($statuses, json_encode(['Faq' => true, 'Office' => true], JSON_PRETTY_PRINT));

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../Modules/*/tests');
