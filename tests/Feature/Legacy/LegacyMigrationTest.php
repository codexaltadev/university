<?php

/*
| Data Migration Plan §4–5: legacy:migrate S02, S04, S06 against a small legacy
| database (university_legacy_test, same shape as project_diu): mapping, old web
| addresses, offices left for S10, level / tag rules (DB-03 §15.1), eligibility
| merge, counters, sub-items, a second run updates instead of duplicating (M-3),
| a record entered by hand is taken over, the legacy side is never written (M-1).
*/

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\IdMap;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Database\Seeders\ProgramDatabaseSeeder;
use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramFacilitator;
use Modules\Program\Models\ProgramListItem;
use Modules\Program\Models\ProgramMajor;
use Modules\Program\Models\ProgramMaterial;
use Modules\Program\Models\ProgramVariant;
use Platform\Statistics\Statistics;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/** CREATE / DROP DATABASE on its own connection: on the test connection it would commit RefreshDatabase's transaction. */
function legacyServer(string $sql): void
{
    $c = config('database.connections.legacy');
    (new PDO("mysql:host={$c['host']};port={$c['port']}", $c['username'], $c['password']))->exec($sql);
}

/** Build the legacy tables the steps read, with only the columns they use. */
function legacySchema(): void
{
    $legacy = config('database.connections.legacy.database');
    legacyServer("CREATE DATABASE IF NOT EXISTS `{$legacy}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    DB::purge('legacy');

    $tables = [
        'faculties' => fn (Blueprint $t) => [$t->string('faculty_name'), $t->string('faculty_short_name'), $t->string('faculty_photo')->nullable(), $t->integer('faculty_serial')->nullable(), $t->text('faculty_content')->nullable(), $t->tinyInteger('publication_status')->default(1), $t->tinyInteger('is_optional')->default(0), $t->tinyInteger('show_list')->nullable(), $t->timestamp('deleted_at')->nullable()],
        'departments' => fn (Blueprint $t) => [$t->unsignedBigInteger('faculty_id'), $t->string('department_name'), $t->string('short_name'), $t->string('department_photo')->nullable(), $t->string('department_cover')->nullable(), $t->string('department_link')->nullable(), $t->integer('department_serial')->nullable(), $t->string('about_title')->nullable(), $t->text('department_content')->nullable(), $t->tinyInteger('publication_status')->default(1), $t->timestamp('deleted_at')->nullable()],
        'program_major_categories' => fn (Blueprint $t) => [$t->string('title'), $t->text('description')->nullable()],
        'program_types' => fn (Blueprint $t) => [$t->integer('parent_id'), $t->string('program_name'), $t->tinyInteger('publication_status')->default(1), $t->timestamp('deleted_at')->nullable()],
        'department_program_type' => fn (Blueprint $t) => [$t->string('full_program_name'), $t->string('subtitle')->nullable(), $t->string('program_image')->nullable(), $t->string('video_url')->nullable(), $t->string('short_name')->nullable(), $t->string('slug'), $t->unsignedBigInteger('department_id'), $t->unsignedBigInteger('program_type_id'), $t->integer('program_type_parent_id'), $t->longText('summary')->nullable(), $t->longText('program_objective')->nullable(), $t->integer('approved_alumni_number')->nullable(), $t->integer('teachers_count')->nullable(), $t->date('next_semester_date')->nullable(), $t->json('section_intros')->nullable(), $t->longText('program_credit_duration')->nullable(), $t->longText('ai_summary')->nullable(), $t->text('ai_short_summary')->nullable(), $t->tinyInteger('is_home')->default(0), $t->integer('serial')->nullable(), $t->tinyInteger('show_program')->default(1), $t->tinyInteger('publication_status')->default(1), $t->timestamp('created_at')->nullable()],
        'eligibilities' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id')->nullable(), $t->integer('eligibility_serial')->nullable(), $t->text('normal_student')->nullable(), $t->text('em_student')->nullable(), $t->tinyInteger('publication_status')->default(1)],
        'program_majors' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id'), $t->unsignedBigInteger('program_major_category_id')->nullable(), $t->string('title'), $t->text('short_description')->nullable()],
        'program_career_paths' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id'), $t->string('title')->nullable()],
        'program_industries' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id'), $t->string('title')->nullable()],
        'program_facilitators' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id'), $t->string('name'), $t->string('designation')->nullable(), $t->string('organization')->nullable(), $t->string('type')->nullable(), $t->string('image')->nullable(), $t->string('profile_link')->nullable(), $t->string('linkedin')->nullable(), $t->string('email')->nullable(), $t->string('mobile')->nullable(), $t->integer('serial')->nullable(), $t->tinyInteger('publication_status')->nullable()],
        'program_materials' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id'), $t->string('title'), $t->string('material_link')->nullable(), $t->string('support_document')->nullable(), $t->integer('sort_order')->nullable()],
        'program_professional_memberships' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id'), $t->string('title'), $t->text('short_description')->nullable(), $t->string('image')->nullable()],
        'program_variants' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id'), $t->unsignedBigInteger('related_program_id')->nullable(), $t->string('title')->nullable(), $t->text('short_description')->nullable(), $t->string('action_button_text')->nullable(), $t->string('action_button_url')->nullable(), $t->string('image')->nullable(), $t->longText('full_details')->nullable()],
    ];

    $schema = Schema::connection('legacy');
    foreach ($tables as $name => $columns) {
        $schema->dropIfExists($name);
        $schema->create($name, function (Blueprint $t) use ($columns): void {
            $t->id();
            $columns($t);
        });
    }
}

beforeEach(function () {
    legacySchema();
    (new ProgramDatabaseSeeder)->run();
    $l = DB::connection('legacy');

    $l->table('faculties')->insert([
        ['id' => 1, 'faculty_name' => 'Science and Information Technology', 'faculty_short_name' => 'fsit', 'faculty_serial' => 1, 'faculty_content' => '<p>Computing at DIU.</p>', 'is_optional' => 0, 'show_list' => 1, 'publication_status' => 1, 'deleted_at' => null],
        ['id' => 6, 'faculty_name' => 'Office', 'faculty_short_name' => 'office', 'faculty_serial' => 20, 'faculty_content' => null, 'is_optional' => 1, 'show_list' => null, 'publication_status' => 1, 'deleted_at' => null],
    ]);
    $l->table('departments')->insert([
        ['id' => 1, 'faculty_id' => 1, 'department_name' => 'Computer Science and Engineering', 'short_name' => 'cse', 'department_serial' => 1, 'department_link' => null, 'department_content' => '<p>CSE about.</p>', 'publication_status' => 1, 'deleted_at' => null],
        ['id' => 2, 'faculty_id' => 1, 'department_name' => 'Software Engineering', 'short_name' => 'swe', 'department_serial' => 2, 'department_link' => 'https://swe.daffodilvarsity.edu.bd', 'department_content' => null, 'publication_status' => 1, 'deleted_at' => null],
        ['id' => 24, 'faculty_id' => 1, 'department_name' => 'Old CIS', 'short_name' => 'bcis', 'department_serial' => 9, 'department_link' => null, 'department_content' => null, 'publication_status' => 0, 'deleted_at' => now()],
        ['id' => 36, 'faculty_id' => 6, 'department_name' => 'Registrar Office', 'short_name' => 'registrar', 'department_serial' => 1, 'department_link' => null, 'department_content' => null, 'publication_status' => 1, 'deleted_at' => null],
    ]);
    $l->table('program_major_categories')->insert([['id' => 2, 'title' => 'Specialization', 'description' => null]]);
    $l->table('program_types')->insert([
        ['id' => 1, 'parent_id' => 1, 'program_name' => 'Undergraduate', 'publication_status' => 1, 'deleted_at' => null],
        ['id' => 2, 'parent_id' => 2, 'program_name' => 'Postgraduate', 'publication_status' => 1, 'deleted_at' => null],
        ['id' => 3, 'parent_id' => 1, 'program_name' => 'Diploma Holder', 'publication_status' => 1, 'deleted_at' => null],
        ['id' => 9, 'parent_id' => 1, 'program_name' => 'Major in Data Science', 'publication_status' => 1, 'deleted_at' => null],
    ]);
    $program = fn (array $row) => [
        'subtitle' => null, 'program_image' => null, 'video_url' => null, 'summary' => null, 'program_objective' => null, 'approved_alumni_number' => null,
        'teachers_count' => null, 'next_semester_date' => null, 'section_intros' => null, 'program_credit_duration' => null, 'ai_summary' => null,
        'ai_short_summary' => null, 'is_home' => 0, 'serial' => null, 'show_program' => 1, 'publication_status' => 1, 'created_at' => '2024-01-01 00:00:00', ...$row,
    ];
    $l->table('department_program_type')->insert([
        $program(['id' => 44, 'full_program_name' => 'B. Sc. in Software Engineering (SWE)', 'short_name' => 'SWE', 'slug' => 'bsc-in-swe', 'department_id' => 2, 'program_type_id' => 1, 'program_type_parent_id' => 1,
            'summary' => '<p>Build software.</p>', 'approved_alumni_number' => 50, 'teachers_count' => 150, 'next_semester_date' => '2026-05-09',
            'section_intros' => json_encode(['majors' => 'Pick a track.', 'future-career' => null]), 'program_credit_duration' => '<p>Credit-147</p><p>Duration -04 Years (12 Semesters)</p>']),
        $program(['id' => 48, 'full_program_name' => 'B.Sc. in Software Engineering (Major in Data Science)', 'short_name' => null, 'slug' => 'bsc-in-swe-major-datascience', 'department_id' => 2, 'program_type_id' => 9, 'program_type_parent_id' => 1]),
        $program(['id' => 46, 'full_program_name' => 'B.Sc. in SWE (Diploma Holder)', 'short_name' => 'Diploma', 'slug' => 'bsc-in-swe-diploma-holder', 'department_id' => 2, 'program_type_id' => 3, 'program_type_parent_id' => 2, 'publication_status' => 0]),
        $program(['id' => 45, 'full_program_name' => 'M. Sc. in Software Engineering', 'short_name' => 'SWE', 'slug' => 'msc-in-swe', 'department_id' => 2, 'program_type_id' => 2, 'program_type_parent_id' => 2]),
    ]);
    $l->table('eligibilities')->insert([
        ['department_program_type_id' => 44, 'eligibility_serial' => 2, 'normal_student' => '<p>Second rule.</p>', 'em_student' => null, 'publication_status' => 1],
        ['department_program_type_id' => 44, 'eligibility_serial' => 1, 'normal_student' => '<p>GPA 2.5 in SSC and HSC.</p>', 'em_student' => '<p>5 O Level, 2 A Level.</p>', 'publication_status' => 1],
    ]);
    $l->table('program_majors')->insert([['department_program_type_id' => 44, 'program_major_category_id' => 2, 'title' => 'Data Science', 'short_description' => 'Data at scale.']]);
    $l->table('program_career_paths')->insert([['department_program_type_id' => 44, 'title' => 'Software Engineer'], ['department_program_type_id' => 44, 'title' => null]]);
    $l->table('program_industries')->insert([['department_program_type_id' => 44, 'title' => 'Fintech']]);
    $l->table('program_facilitators')->insert([['department_program_type_id' => 44, 'name' => 'Ayesha Rahman', 'designation' => 'CTO', 'organization' => 'Example Ltd', 'type' => 'industrial', 'image' => 'images/a.jpg', 'profile_link' => 'not a url', 'linkedin' => 'https://linkedin.com/in/ayesha', 'email' => 'ayesha@example.com', 'mobile' => '+8801700000000', 'serial' => 1, 'publication_status' => 1]]);
    $l->table('program_materials')->insert([['department_program_type_id' => 44, 'title' => 'Hand Books', 'material_link' => null, 'support_document' => 'program_materials/handbook.pdf', 'sort_order' => 1]]);
    $l->table('program_professional_memberships')->insert([['department_program_type_id' => 44, 'title' => 'IEB', 'short_description' => 'Accredited.', 'image' => null]]);
    $l->table('program_variants')->insert([
        ['department_program_type_id' => 44, 'related_program_id' => 45, 'title' => 'Go further: M.Sc.', 'short_description' => 'One more year.', 'action_button_text' => 'See M.Sc.', 'action_button_url' => null, 'image' => null, 'full_details' => null],
        ['department_program_type_id' => 44, 'related_program_id' => 44, 'title' => 'Itself', 'short_description' => 'x', 'action_button_text' => null, 'action_button_url' => null, 'image' => null, 'full_details' => null],
        ['department_program_type_id' => 44, 'related_program_id' => null, 'title' => null, 'short_description' => null, 'action_button_text' => null, 'action_button_url' => null, 'image' => null, 'full_details' => null],
    ]);
});

afterEach(function () {
    DB::purge('legacy');
    legacyServer('DROP DATABASE IF EXISTS `'.config('database.connections.legacy.database').'`');
});

it('migrates academic faculties and departments, keeping old addresses and leaving offices for S10 (S04)', function () {
    $this->artisan('legacy:migrate', ['--step' => ['S04'], '--no-report' => true])->assertSuccessful();

    expect(Faculty::query()->pluck('short_name')->all())->toBe(['FSIT']);
    $fsit = Faculty::query()->sole();
    expect($fsit->url())->toBe('/faculty/fsit')
        ->and($fsit->getTranslation('overview', 'en'))->toBe('<p>Computing at DIU.</p>');

    expect(Department::query()->orderBy('sort_order')->pluck('short_name')->all())->toBe(['CSE', 'SWE'])    // not the deleted one, not the registrar
        ->and(Department::query()->where('short_name', 'SWE')->sole()->external_url)->toBe('https://swe.daffodilvarsity.edu.bd')
        ->and(Department::query()->where('short_name', 'CSE')->sole()->url())->toBe('/department/cse');

    $this->get('/department/cse')->assertOk()->assertSee('CSE about.');
});

it('migrates programs with level, tag, eligibility, counters and their parts (S02, S06)', function () {
    $this->artisan('legacy:migrate', ['--all' => true, '--no-report' => true])->assertSuccessful();

    $swe = Program::findBySlug('bsc-in-swe', 'en')[0];
    expect($swe->url())->toBe('/program/bsc-in-swe')
        ->and((float) $swe->total_credits)->toBe(147.0)
        ->and((float) $swe->duration_years)->toBe(4.0)
        ->and($swe->total_terms)->toBe(12)
        ->and($swe->next_intake_on->toDateString())->toBe('2026-05-09')
        ->and($swe->intro('majors', 'en'))->toBe('Pick a track.')
        ->and($swe->getTranslation('eligibility_general', 'en'))->toStartWith('<p>GPA 2.5 in SSC and HSC.</p>')     // serial 1 first
        ->and($swe->getTranslation('eligibility_general', 'en'))->toContain('Second rule.')
        ->and($swe->getTranslation('eligibility_english_medium', 'en'))->toBe('<p>5 O Level, 2 A Level.</p>');

    // DB-03 §15.1: type 3 → Diploma (even under parent 2), sub-type → tag; no short name → the department's
    $diploma = Program::query()->where('id', app(IdMap::class)->find('department_program_type', 46))->sole();
    $dataScience = Program::query()->where('id', app(IdMap::class)->find('department_program_type', 48))->sole();
    expect($diploma->level->getTranslation('name', 'en'))->toBe('Diploma')
        ->and($diploma->is_active)->toBeFalse()
        ->and($dataScience->short_name)->toBe('SWE')
        ->and($dataScience->termsIn('program.tag')->map(fn ($t) => $t->getTranslation('name', 'en'))->all())->toBe(['Major in Data Science'])
        ->and($swe->termsIn('program.tag'))->toBeEmpty();

    // Manual counters
    $stats = collect(app(Statistics::class)->forOwner($swe->getMorphClass().':'.$swe->id));
    expect($stats->firstWhere('label', 'Approved Alumni')['display'])->toBe('50+')
        ->and($stats->firstWhere('label', 'Faculty Members')['display'])->toBe('150');

    // Parts
    $major = ProgramMajor::query()->sole();
    expect($major->category->getTranslation('name', 'en'))->toBe('Specialization')
        ->and(ProgramListItem::query()->pluck('list_type')->sort()->values()->all())->toBe(['career_path', 'industry']);   // the empty title is skipped

    $person = ProgramFacilitator::query()->sole();
    expect($person->type)->toBe('industry_expert')
        ->and($person->profile_url)->toBeNull()                                                 // not a URL
        ->and($person->linkedin_url)->toBe('https://linkedin.com/in/ayesha')
        ->and(ProgramMaterial::query()->sole()->url)->toBe('https://webbackend.daffodilvarsity.edu.bd/program_materials/handbook.pdf');

    $variants = ProgramVariant::query()->orderBy('id')->get();
    expect($variants)->toHaveCount(2)                                                           // the one without a title is skipped
        ->and($variants[0]->relatedProgram->slug())->toBe('msc-in-swe')
        ->and($variants[1]->related_program_id)->toBeNull();                                    // pointing at itself

    $this->get('/program/bsc-in-swe')->assertOk()->assertSee('Build software.')->assertSee('Data Science')->assertSee('Ayesha Rahman');
});

it('updates on a second run instead of duplicating, and takes over a record entered by hand (M-3)', function () {
    $handMade = Faculty::factory()->create(['short_name' => 'FSIT', 'name' => ['en' => 'Faculty of Science and IT', 'bn' => 'বিজ্ঞান ও তথ্যপ্রযুক্তি অনুষদ']]);

    $this->artisan('legacy:migrate', ['--all' => true, '--no-report' => true])->assertSuccessful();
    DB::connection('legacy')->table('department_program_type')->where('id', 44)->update(['full_program_name' => 'B.Sc. in Software Engineering']);
    $this->artisan('legacy:migrate', ['--all' => true, '--no-report' => true])->assertSuccessful();

    expect(Faculty::query()->count())->toBe(1)
        ->and(Faculty::query()->sole()->is($handMade))->toBeTrue()
        ->and($handMade->fresh()->getTranslation('name', 'bn'))->toBe('বিজ্ঞান ও তথ্যপ্রযুক্তি অনুষদ')    // other languages stay
        ->and(Department::query()->count())->toBe(2)
        ->and(Program::query()->count())->toBe(4)
        ->and(ProgramMajor::query()->count())->toBe(1)
        ->and(Program::findBySlug('bsc-in-swe', 'en')[0]->getTranslation('name', 'en'))->toBe('B.Sc. in Software Engineering');

    // The legacy database was only read
    expect(DB::connection('legacy')->table('faculties')->count())->toBe(2);
});

it('does not duplicate when the id map is lost, and keeps two rows with an equal title apart', function () {
    DB::connection('legacy')->table('program_career_paths')->insert(['department_program_type_id' => 44, 'title' => 'Software Engineer']);   // a second, equal title

    $this->artisan('legacy:migrate', ['--all' => true, '--no-report' => true])->assertSuccessful();
    expect(ProgramListItem::query()->where('list_type', 'career_path')->count())->toBe(2);

    DB::table('migration_id_map')->delete();   // e.g. a fresh dry-run database restored without it
    $this->artisan('legacy:migrate', ['--all' => true, '--no-report' => true])->assertSuccessful();

    expect(Program::query()->count())->toBe(4)
        ->and(ProgramListItem::query()->where('list_type', 'career_path')->count())->toBe(2)
        ->and(ProgramMajor::query()->count())->toBe(1)
        ->and(Term::query()->where('vocabulary_id', app(Vocabularies::class)->find('program.tag')->id)->count())->toBe(1);
});

it('refuses to run without a step and when the legacy database is missing', function () {
    $this->artisan('legacy:migrate')->assertFailed();

    config(['database.connections.legacy.database' => 'no_such_legacy_db']);
    DB::purge('legacy');
    $this->artisan('legacy:migrate', ['--all' => true, '--no-report' => true])->assertFailed();
});

it('reads credits, years and semesters from the free text', function (string $text, ?float $credits, ?float $years, ?int $terms) {
    expect(Normalizer::creditsAndDuration($text))->toBe(['credits' => $credits, 'years' => $years, 'terms' => $terms]);
})->with([
    ['<p>Credit-154</p><p>Duration -04 Years</p>', 154.0, 4.0, null],
    ['comprises a total of 194 credit hours … five (5) academic years', 194.0, 5.0, null],
    ['requires a <strong>minimum of 40 credit hours</strong> … 1 year', 40.0, 1.0, null],
    ['complete 142 credits … The program having 12 semesters normally extends over four academic years', 142.0, null, 12],
    ['<p>Nothing useful here.</p>', null, null, null],
]);
