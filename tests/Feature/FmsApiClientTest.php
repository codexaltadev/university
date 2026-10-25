<?php

/*
| FMS API client (DIU Faculty Management System): every endpoint with the token, cached by path +
| query, failures as null and logged, a 429 answered from the last good copy, files streamed through
| this site, fms:clear-cache. No real request leaves the test (Http::fake, preventStrayRequests).
*/

use App\Services\FmsApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const FMS = 'https://fms.test/api/v1';

beforeEach(function () {
    config(['services.fms' => ['url' => FMS, 'token' => 'test-token', 'cache_ttl' => 3600]]);
    app()->forgetInstance(FmsApiClient::class);
    Cache::flush();
    Http::preventStrayRequests();
    $this->fms = fn (): FmsApiClient => app(FmsApiClient::class);
});

it('calls each endpoint with the token and its parameters, and gives the decoded answer', function (string $method, array $args, string $url) {
    Http::fake([FMS.'/*' => Http::response(['data' => ['ok' => $method]])]);

    expect(($this->fms)()->{$method}(...$args))->toBe(['data' => ['ok' => $method]]);

    Http::assertSent(fn (Request $r): bool => $r->url() === FMS.$url
        && $r->hasHeader('Authorization', 'Bearer test-token') && $r->hasHeader('Accept', 'application/json'));
})->with([
    'faculties' => ['faculties', [], '/faculties'],
    'faculty' => ['faculty', ['FBE'], '/faculties/FBE'],
    'departments' => ['departments', ['FBE'], '/faculties/FBE/departments'],
    'department' => ['department', ['FBE', 'IE'], '/faculties/FBE/departments/IE'],
    'departmentTeachers' => ['departmentTeachers', ['FBE', 'IE', ['q' => 'razzak', 'designation' => 3, 'per_page' => 500, 'page' => 2, 'junk' => 1]], '/faculties/FBE/departments/IE/teachers?designation=3&page=2&per_page=100&q=razzak'],
    'allDepartments' => ['allDepartments', [], '/departments'],
    'teachers' => ['teachers', [['q' => 'ali', 'faculty' => 'FSIT', 'department' => 'CSE', 'per_page' => 0]], '/teachers?department=CSE&faculty=FSIT&per_page=1&q=ali'],
    'lookups' => ['lookups', [], '/lookups'],
    'publications' => ['publications', [['q' => 'zinc', 'year' => 2026, 'from' => '2025-01-01', 'to' => '2025-12-31', 'type' => 9, 'per_page' => 10]], '/publications?from=2025-01-01&per_page=10&q=zinc&to=2025-12-31&type=9&year=2026'],
    'teacher' => ['teacher', ['FBE', 'IE', 'muntachir', ['education', 'skills', 'nonsense']], '/FBE/IE/muntachir?include=education%2Cskills'],
    'teacher (all)' => ['teacher', ['FBE', 'IE', 'muntachir'], '/FBE/IE/muntachir'],
    'teacherPublications' => ['teacherPublications', ['FBE', 'IE', 'muntachir', ['per_page' => 5, 'page' => 1]], '/FBE/IE/muntachir/publications?page=1&per_page=5'],
    'publication' => ['publication', ['FBE', 'IE', 'muntachir', 'splendid-tourism'], '/FBE/IE/muntachir/publications/splendid-tourism'],
]);

it('caches each answer by path and query, so the second call makes no request', function () {
    Http::fake([FMS.'/faculties' => Http::response(['data' => [['short_name' => 'FBE']]]), FMS.'/teachers*' => Http::response(['data' => []])]);

    ($this->fms)()->faculties();
    ($this->fms)()->faculties();
    ($this->fms)()->teachers(['q' => 'a']);
    ($this->fms)()->teachers(['q' => 'a']);
    ($this->fms)()->teachers(['q' => 'b']);

    Http::assertSentCount(3);
});

it('answers a 429 from the last good copy, and gives null without one', function () {
    Http::fakeSequence(FMS.'/lookups')->push(['data' => ['designations' => []]])->push(['message' => 'Too Many Attempts.'], 429);
    $fms = ($this->fms)();

    expect($fms->lookups())->toBe(['data' => ['designations' => []]]);
    Cache::forget('fms:'.md5('/lookups?'));                                                          // the fresh copy expired
    expect($fms->lookups())->toBe(['data' => ['designations' => []]]);                              // 429 → the last good one

    Http::fake([FMS.'/faculties' => Http::response(['message' => 'Too Many Attempts.'], 429)]);
    expect($fms->faculties())->toBeNull();
});

it('gives null for an unknown teacher, logs a refused token as an error, and never throws', function () {
    Log::spy();
    Http::fake([
        FMS.'/FBE/IE/nobody' => Http::response(['message' => 'Not found.'], 404),
        FMS.'/faculties' => Http::response(['message' => 'Unauthenticated.'], 401),
        FMS.'/departments' => fn () => throw new ConnectionException('down'),
    ]);
    $fms = ($this->fms)();

    expect($fms->teacher('FBE', 'IE', 'nobody'))->toBeNull()
        ->and($fms->faculties())->toBeNull()
        ->and($fms->allDepartments())->toBeNull();
    Log::shouldHaveReceived('error')->withArgs(fn (string $message): bool => str_contains($message, '401'))->once();
});

it('needs the address and the token', function () {
    config(['services.fms.token' => '']);
    app()->forgetInstance(FmsApiClient::class);
    Http::fake();

    expect(app(FmsApiClient::class)->configured())->toBeFalse()
        ->and(app(FmsApiClient::class)->faculties())->toBeNull();
    Http::assertNothingSent();
});

it('streams a teacher\'s CV and vCard through this site, the token staying on the server', function () {
    Http::fake([
        FMS.'/FBE/IE/muntachir/cv' => Http::response('%PDF-1.4 cv', 200, ['Content-Type' => 'application/pdf']),
        FMS.'/FBE/IE/muntachir/vcard' => Http::response("BEGIN:VCARD\nEND:VCARD", 200, ['Content-Type' => 'text/vcard']),
        FMS.'/FBE/IE/nobody/cv' => Http::response(['message' => 'Not found.'], 404),
    ]);

    $this->get('/fms/FBE/IE/muntachir/cv')->assertOk()->assertHeader('Content-Type', 'application/pdf')
        ->assertSee('%PDF-1.4 cv', false)->assertDontSee('test-token');
    $this->get('/fms/FBE/IE/muntachir/vcard')->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="muntachir-vcard.vcf"');
    $this->get('/fms/FBE/IE/nobody/cv')->assertNotFound();
    $this->get('/fms/FBE/IE/muntachir/photo')->assertNotFound();
    Http::assertSent(fn (Request $r): bool => $r->hasHeader('Authorization', 'Bearer test-token'));
});

it('forgets every cached answer with fms:clear-cache', function () {
    Http::fake([FMS.'/*' => Http::response(['data' => []])]);
    ($this->fms)()->faculties();
    ($this->fms)()->lookups();

    $this->artisan('fms:clear-cache')->expectsOutput('Forgot 2 cached FMS API answers.')->assertSuccessful();

    ($this->fms)()->faculties();
    Http::assertSentCount(3);
});
