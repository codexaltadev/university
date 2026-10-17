<?php

/*
| The seed files (owner 2026-10-17): legacy tables written to tools/legacy-migration/seed and
| loaded into a database of their own that the "legacy" connection then reads — rows and
| types come back as they were, personal data stays out, an unchanged table gives the same
| file, and the seed is never loaded over the legacy or the application database.
*/

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Seed;

/** CREATE / DROP DATABASE on its own connection: on the test connection it would commit RefreshDatabase's transaction. */
function seedServer(string $sql): void
{
    $c = config('database.connections.legacy');
    (new PDO("mysql:host={$c['host']};port={$c['port']}", $c['username'], $c['password']))->exec($sql);
}

beforeEach(function () {
    $this->legacyDb = config('database.connections.legacy.database');
    $this->dir = storage_path('framework/testing/legacy-seed');
    seedServer("CREATE DATABASE IF NOT EXISTS `{$this->legacyDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    DB::purge('legacy');

    $schema = Schema::connection('legacy');
    foreach (['faculties', 'alumnis', 'newsletters'] as $table) {
        $schema->dropIfExists($table);
    }
    $schema->create('faculties', function (Blueprint $t): void {
        $t->id();
        $t->string('faculty_name');
        $t->decimal('ratio', 5, 2)->nullable();
        $t->enum('is_home', ['0', '1'])->default('0');
        $t->longText('faculty_content')->nullable();
        $t->timestamp('deleted_at')->nullable();
    });
    $schema->create('alumnis', function (Blueprint $t): void {
        $t->id();
        $t->string('name');
        $t->string('email')->nullable();
        $t->string('phone_number')->nullable();
        $t->string('student_id')->nullable();
        $t->string('office_address')->nullable();
    });
    $schema->create('newsletters', function (Blueprint $t): void {
        $t->id();
        $t->string('email');
    });

    $l = DB::connection('legacy');
    $l->table('faculties')->insert([
        ['id' => 7, 'faculty_name' => 'Business & Entrepreneurship', 'ratio' => 12.5, 'is_home' => '1', 'faculty_content' => '<p>ব্যবসা</p>', 'deleted_at' => null],
        ['id' => 2, 'faculty_name' => 'Science and Information Technology', 'ratio' => null, 'is_home' => '0', 'faculty_content' => null, 'deleted_at' => '2024-01-02 03:04:05'],
    ]);
    $l->table('alumnis')->insert(['id' => 1, 'name' => 'Rahim', 'email' => 'rahim@example.com', 'phone_number' => '01700000000', 'student_id' => '111-15-1', 'office_address' => 'Dhaka']);
    $l->table('newsletters')->insert(['id' => 1, 'email' => 'reader@example.com']);
});

afterEach(function () {
    File::deleteDirectory($this->dir);
    seedServer('DROP DATABASE IF EXISTS `university_seed_test`');
    seedServer("DROP DATABASE IF EXISTS `{$this->legacyDb}`");
});

it('writes the seed and loads it into a database of its own', function () {
    $written = app(Seed::class)->export(DB::connection('legacy'), $this->dir, ['faculties', 'alumnis', 'newsletters']);

    expect($written)->toBe(['faculties' => 2, 'alumnis' => 1, 'newsletters' => 0])
        ->and(File::get("{$this->dir}/schema.sql"))->toContain('CREATE TABLE `faculties`')->not->toContain('AUTO_INCREMENT=');

    $loaded = app(Seed::class)->load($this->dir, 'university_seed_test');

    expect($loaded)->toMatchArray(['faculties' => 2, 'alumnis' => 1, 'newsletters' => 0])
        ->and(config('database.connections.legacy.database'))->toBe('university_seed_test');

    $rows = DB::connection('legacy')->table('faculties')->orderBy('id')->get();
    expect($rows->pluck('id')->all())->toBe([2, 7])                  // primary key order, ints stay ints
        ->and($rows[1]->ratio)->toBe('12.50')                        // decimals as MySQL gives them
        ->and($rows[1]->is_home)->toBe('1')
        ->and($rows[1]->faculty_content)->toBe('<p>ব্যবসা</p>')
        ->and($rows[0]->deleted_at)->toBe('2024-01-02 03:04:05')
        ->and(Schema::connection('legacy')->hasTable('newsletters'))->toBeTrue();

    // Personal data: subscribers structure only, the alumni's private columns empty
    $alumnus = DB::connection('legacy')->table('alumnis')->first();
    expect($alumnus->name)->toBe('Rahim')
        ->and([$alumnus->email, $alumnus->phone_number, $alumnus->student_id, $alumnus->office_address])->toBe([null, null, null, null])
        ->and(DB::connection('legacy')->table('newsletters')->count())->toBe(0);
});

it('gives the same file for an unchanged table', function () {
    app(Seed::class)->export(DB::connection('legacy'), $this->dir, ['faculties']);
    $first = File::get("{$this->dir}/faculties.jsonl.gz");
    app(Seed::class)->export(DB::connection('legacy'), $this->dir, ['faculties']);

    expect(File::get("{$this->dir}/faculties.jsonl.gz"))->toBe($first);
});

it('never loads the seed over the legacy or the application database', function () {
    app(Seed::class)->export(DB::connection('legacy'), $this->dir, ['faculties']);

    expect(fn () => app(Seed::class)->load($this->dir, $this->legacyDb))->toThrow(RuntimeException::class)
        ->and(fn () => app(Seed::class)->load($this->dir, config('database.connections.mysql.database')))->toThrow(RuntimeException::class)
        ->and(DB::connection('legacy')->table('faculties')->count())->toBe(2);
});
