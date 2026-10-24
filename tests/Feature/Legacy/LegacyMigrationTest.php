<?php

/*
| Data Migration Plan §4–5: legacy:migrate S02, S04, S06 against a small legacy
| database (university_legacy_test, same shape as project_diu): mapping, old web
| addresses, offices left for S10, level / tag rules (DB-03 §15.1), eligibility
| merge, counters, sub-items, a second run updates instead of duplicating (M-3),
| a record entered by hand is taken over, the legacy side is never written (M-1).
*/

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Legacy\Migration\Support\IdMap;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Events\Models\Event;
use Modules\Faculty\Models\Faculty;
use Modules\Page\Models\Page;
use Modules\Program\Database\Seeders\ProgramDatabaseSeeder;
use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramFacilitator;
use Modules\Program\Models\ProgramListItem;
use Modules\Program\Models\ProgramMajor;
use Modules\Program\Models\ProgramMaterial;
use Modules\Program\Models\ProgramVariant;
use Modules\Seo\Models\Redirect;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Modules\Tuition\Models\TuitionLine;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
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
        'events' => fn (Blueprint $t) => [$t->string('title')->nullable(), $t->longText('content')->nullable(), $t->date('event_date')->nullable(), $t->string('event_time')->nullable(), $t->string('location')->nullable(), $t->string('url')->nullable(), $t->string('photo')->nullable(), $t->unsignedBigInteger('department_id')->nullable(), $t->unsignedBigInteger('lab_facility_id')->nullable(), $t->unsignedBigInteger('event_category_id')->nullable(), $t->string('is_home')->default('0'), $t->tinyInteger('publication_status')->default(1), $t->timestamp('created_at')->nullable(), $t->timestamp('deleted_at')->nullable()],
        'articles' => fn (Blueprint $t) => [$t->string('article_title')->nullable(), $t->string('article_slug')->nullable(), $t->longText('article_content')->nullable(), $t->tinyInteger('publication_status')->default(1)],
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
        'tuition_categories' => fn (Blueprint $t) => [$t->string('category_name'), $t->string('category_code'), $t->tinyInteger('publication_status')->default(1)],
        'tuitions' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id'), $t->unsignedBigInteger('tuition_category_id'), $t->text('majors')->nullable(), $t->string('credit_hours')->nullable(), $t->integer('max_credit')->nullable(), $t->string('total_semester')->nullable(), $t->string('year')->nullable(), $t->string('admission_fees')->nullable(), $t->integer('first_year')->nullable(), $t->integer('second_year')->nullable(), $t->integer('third_year')->nullable(), $t->integer('fourth_year')->nullable(), $t->string('semester_cost')->nullable(), $t->string('total_tuition_fees')->nullable(), $t->integer('max_tuition_fees')->nullable(), $t->string('other_fee')->nullable(), $t->string('total_fees')->nullable(), $t->integer('max_total_fees')->nullable(), $t->integer('tuition_serial')->nullable(), $t->string('prospectus_link')->nullable(), $t->string('publication_status')->default('1')],
        // Waiver tables (rows: the golden master snapshot covers them)
        'boards' => fn (Blueprint $t) => [$t->unsignedBigInteger('program_type_id'), $t->string('name'), $t->string('is_active')->default('1'), $t->timestamp('deleted_at')->nullable()],
        'waiver_categories' => fn (Blueprint $t) => [$t->unsignedBigInteger('program_type_id'), $t->string('waiver_categories')->nullable(), $t->string('name'), $t->string('is_active')->default('1'), $t->timestamp('deleted_at')->nullable()],
        'waivers' => fn (Blueprint $t) => [$t->string('name'), $t->unsignedBigInteger('tuition_category_id'), $t->unsignedBigInteger('program_type_id'), $t->double('hsc_min', 3, 2)->nullable(), $t->double('hsc_max', 3, 2)->nullable(), $t->boolean('hsc_golden')->default(false), $t->double('ssc_min', 3, 2)->nullable(), $t->double('ssc_max', 3, 2)->nullable(), $t->boolean('ssc_golden')->default(false), $t->string('message')->nullable(), $t->string('is_active')->default('1'), $t->double('waiver_rate', 5, 2), $t->double('max_waiver_rate', 5, 2)->nullable(), $t->timestamp('deleted_at')->nullable(), $t->double('undergraduate_min', 3, 2)->nullable(), $t->double('undergraduate_max', 3, 2)->nullable(), $t->double('international_undergraduate_min', 3, 2)->nullable(), $t->double('international_undergraduate_max', 3, 2)->nullable()],
        'board_waiver' => fn (Blueprint $t) => [$t->unsignedBigInteger('waiver_id'), $t->unsignedBigInteger('board_id')],
        'gender_waiver' => fn (Blueprint $t) => [$t->unsignedBigInteger('waiver_id'), $t->unsignedBigInteger('gender_id')],
        'tuition_waiver' => fn (Blueprint $t) => [$t->unsignedBigInteger('waiver_id'), $t->unsignedBigInteger('tuition_id')],
        'waiver_waiver_category' => fn (Blueprint $t) => [$t->unsignedBigInteger('waiver_id'), $t->unsignedBigInteger('waiver_category_id')],
        'faculty_waiver' => fn (Blueprint $t) => [$t->unsignedBigInteger('waiver_id'), $t->unsignedBigInteger('faculty_id')],
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
    $l->table('tuition_categories')->insert([
        ['id' => 1, 'category_name' => 'Local Tuition Fee', 'category_code' => 'local-tuition', 'publication_status' => 1],
        ['id' => 2, 'category_name' => 'International Tuition Fee', 'category_code' => 'international-tuition', 'publication_status' => 1],
    ]);
    $tuition = fn (array $row) => ['majors' => null, 'semester_cost' => null, 'max_credit' => 0, 'total_semester' => '12', 'year' => '4 Years', 'first_year' => null, 'second_year' => null, 'third_year' => null, 'fourth_year' => null, 'max_tuition_fees' => 0, 'other_fee' => null, 'max_total_fees' => 0, 'tuition_serial' => 5, 'prospectus_link' => null, 'publication_status' => '1', ...$row];
    $l->table('tuitions')->insert([
        $tuition(['id' => 4, 'department_program_type_id' => 44, 'tuition_category_id' => 1, 'credit_hours' => '147', 'admission_fees' => '61750', 'semester_cost' => '78,000', 'total_tuition_fees' => '709500', 'total_fees' => '952,500', 'majors' => 'AI, Data Science']),
        $tuition(['id' => 44, 'department_program_type_id' => 44, 'tuition_category_id' => 2, 'credit_hours' => '147', 'total_semester' => '8', 'admission_fees' => '1514', 'first_year' => 2664, 'second_year' => 2300, 'third_year' => 2300, 'fourth_year' => 2462, 'semester_cost' => '1216', 'total_tuition_fees' => '7271', 'other_fee' => '2181', 'total_fees' => '9726']),
        // A range the legacy calculator shows: max total, max tuition and max credit
        $tuition(['id' => 7, 'department_program_type_id' => 45, 'tuition_category_id' => 1, 'credit_hours' => '42', 'max_credit' => 63, 'total_semester' => '4 - 6', 'year' => '1.5 - 2 Years', 'admission_fees' => '28750', 'semester_cost' => '40,000', 'total_tuition_fees' => '99000', 'max_tuition_fees' => 162000, 'total_fees' => '173,500', 'max_total_fees' => 249000]),
        // Two of one program and category: told apart by the duration
        $tuition(['id' => 61, 'department_program_type_id' => 45, 'tuition_category_id' => 2, 'credit_hours' => '40', 'year' => '1 Year', 'admission_fees' => '1118', 'total_tuition_fees' => '1485', 'total_fees' => '2021']),
        $tuition(['id' => 80, 'department_program_type_id' => 45, 'tuition_category_id' => 2, 'credit_hours' => '69', 'year' => '2 Years', 'admission_fees' => '1118', 'total_tuition_fees' => '2475', 'total_fees' => '3383']),
    ]);
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
    expect($fsit->url())->toBe('/faculties/fsit')
        ->and($fsit->getTranslation('overview', 'en'))->toBe('<p>Computing at DIU.</p>');

    expect(Department::query()->orderBy('sort_order')->pluck('short_name')->all())->toBe(['CSE', 'SWE'])    // not the deleted one, not the registrar
        ->and(Department::query()->where('short_name', 'SWE')->sole()->external_url)->toBe('https://swe.daffodilvarsity.edu.bd')
        ->and(Department::query()->where('short_name', 'CSE')->sole()->url())->toBe('/fsit/cse');

    $this->get('/fsit/cse')->assertOk()->assertSee('CSE about.');
    $this->get('/department/cse')->assertRedirect('/fsit/cse');   // DIU's old address (FR-GEN-36)
});

it('migrates programs with level, tag, eligibility, counters and their parts (S02, S06)', function () {
    $this->artisan('legacy:migrate', ['--all' => true, '--no-report' => true])->assertSuccessful();

    $swe = Program::findBySlug('bsc-in-swe', 'en')[0];
    expect($swe->url())->toBe($swe->department->url().'/bsc-in-swe')
        ->and((float) $swe->total_credits)->toBe(147.0)
        ->and((float) $swe->duration_years)->toBe(4.0)
        ->and($swe->total_terms)->toBe(12)
        ->and($swe->getTranslation('credit_duration', 'en'))->toContain('Credit-147')                             // the text as well
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

    $this->get('/program/bsc-in-swe')->assertRedirect($swe->url());   // the old form (FR-GEN-36)
    $this->get($swe->url())->assertOk()->assertSee('Build software.')->assertSee('Data Science')->assertSee('Ayesha Rahman');
});

it('migrates tuition fees with DIU\'s totals, lines, ranges and labels (S07)', function () {
    $this->artisan('legacy:migrate', ['--all' => true, '--no-report' => true])->assertSuccessful();

    expect(FeeCategory::query()->orderBy('sort_order')->get()->map(fn ($c) => [$c->code, $c->currency])->all())->toBe([['local', 'BDT'], ['international', 'USD']]);

    $bsc = Program::findBySlug('bsc-in-swe', 'en')[0];
    $local = Tuition::query()->where('program_id', $bsc->id)->whereHas('category', fn ($q) => $q->where('code', 'local'))->sole();
    expect($local->totals_overridden)->toBeTrue()
        ->and((float) $local->tuition_min)->toBe(709500.0)
        ->and((float) $local->total_min)->toBe(952500.0)                                       // "952,500"
        ->and($local->total_max)->toBeNull()
        ->and($local->lines->pluck('line_type')->all())->toBe(['admission', 'term'])
        ->and($local->admissionFee())->toBe(61750.0)
        ->and($local->otherFees())->toBe(243000.0);                                            // total − tuition (no other fee entered)

    $intl = Tuition::query()->where('program_id', $bsc->id)->whereHas('category', fn ($q) => $q->where('code', 'international'))->sole();
    expect($intl->lines->pluck('line_type')->all())->toBe(['admission', 'year', 'year', 'year', 'year', 'term', 'other'])
        ->and($intl->otherFees())->toBe(2181.0);

    $msc = Program::findBySlug('msc-in-swe', 'en')[0];
    $ranged = Tuition::query()->where('program_id', $msc->id)->whereHas('category', fn ($q) => $q->where('code', 'local'))->sole();
    expect($ranged->creditsText())->toBe('42 – 63')
        ->and((float) $ranged->total_max)->toBe(249000.0)
        ->and((float) $ranged->tuition_max)->toBe(162000.0)
        ->and($ranged->total_terms)->toBeNull();                                               // "4 - 6" stays in the duration

    expect(Tuition::query()->where('program_id', $msc->id)->whereHas('category', fn ($q) => $q->where('code', 'international'))->get()
        ->map(fn ($t) => $t->getTranslation('label', 'en'))->sort()->values()->all())->toBe(['1 Year', '2 Years']);

    // A second run changes nothing
    $this->artisan('legacy:migrate', ['--step' => ['S07'], '--no-report' => true])->assertSuccessful();
    expect(Tuition::query()->count())->toBe(5)
        ->and(TuitionLine::query()->count())->toBe(13);   // 2 + 7 + 2 + 1 + 1
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

it('puts each legacy file into the Media Library once and into its records, from the cache (S03)', function () {
    Storage::fake('public');
    $cache = storage_path('framework/testing/legacy-files');
    config(['database.connections.legacy.files_path' => $cache]);
    File::ensureDirectoryExists("{$cache}/images");
    $picture = imagecreatetruecolor(40, 30);
    imagejpeg($picture, "{$cache}/images/a.jpg");
    File::ensureDirectoryExists("{$cache}/program_images");
    File::copy("{$cache}/images/a.jpg", "{$cache}/program_images/swe.jpg");   // the same picture under another name
    DB::connection('legacy')->table('department_program_type')->where('id', 44)->update(['program_image' => '/program_images/swe.jpg']);

    $this->artisan('legacy:migrate', ['--step' => ['S04', 'S06'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S03'], '--only' => ['program'], '--no-report' => true])->assertSuccessful();

    $swe = Program::findBySlug('bsc-in-swe', 'en')[0];
    $ayesha = ProgramFacilitator::query()->where('name', 'Ayesha Rahman')->first();
    expect($swe->firstMedia('image'))->not->toBeNull()
        ->and($ayesha->firstMedia('photo')?->id)->toBe($swe->firstMedia('image')->id)          // same content = one item
        ->and(MediaItem::query()->count())->toBe(1)
        ->and(MediaItem::query()->first()->folder->name)->toBe('department_program_type')
        ->and(app(IdMap::class)->record('files', 'https://webbackend.daffodilvarsity.edu.bd/images/a.jpg', MediaItem::class)?->id)->toBe($ayesha->firstMedia('photo')->id);   // other steps find it by path

    // Again: nothing new; a picture chosen by hand stays
    $own = app(MediaLibrary::class)->store(UploadedFile::fake()->image('own.jpg', 30, 20), ['scope_type' => 'site', 'scope_id' => 0]);
    $swe->syncMedia('image', [$own->id]);
    $this->artisan('legacy:migrate', ['--step' => ['S03'], '--only' => ['program'], '--no-report' => true])->assertSuccessful();
    expect(MediaItem::query()->count())->toBe(2)->and($swe->fresh()->firstMedia('image')->id)->toBe($own->id);

    File::deleteDirectory($cache);
});

it('migrates main-site articles under /article, keeping their address and Bootstrap markup (S15A)', function () {
    DB::connection('legacy')->table('articles')->insert([
        ['id' => 1, 'article_title' => 'International Adviser of DIU', 'article_slug' => 'international-adviser', 'publication_status' => 1,
            'article_content' => '<table class="table" border="1"><tr><td><img src="/storage/photos/walter.jpg"></td><td><a href="/photos/cv.pdf">CV</a> <a href="/article/visiting-professors">More</a></td></tr></table>'],
        ['id' => 2, 'article_title' => 'Old Draft', 'article_slug' => 'old-draft', 'publication_status' => 0, 'article_content' => '<p>x</p>'],
    ]);

    $this->artisan('legacy:migrate', ['--step' => ['S15A'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S15A'], '--no-report' => true])->assertSuccessful();   // again: no copies

    expect(Page::query()->withoutGlobalScopes()->count())->toBe(3);   // "article" + 2
    $this->get('/article/international-adviser')->assertOk()
        ->assertSee('International Adviser of DIU')
        ->assertSee('class="table" border="1"', false)
        ->assertSee('src="https://webbackend.daffodilvarsity.edu.bd/storage/photos/walter.jpg"', false)   // the old site's files from webbackend
        ->assertSee('href="https://webbackend.daffodilvarsity.edu.bd/photos/cv.pdf"', false)
        ->assertSee('href="/article/visiting-professors"', false);                                       // page links stay
    $this->get('/article/old-draft')->assertNotFound();
});

it('migrates events and sends DIU\'s /events-detail/{id} to the event\'s page (S16E)', function () {
    DB::connection('legacy')->table('events')->insert([
        ['id' => 583, 'title' => 'Industry visit', 'content' => '<p>To the factory.</p>', 'event_date' => '2025-07-13', 'event_time' => '09:00:00', 'location' => 'Gazipur', 'is_home' => '1'],
    ]);

    $this->artisan('legacy:migrate', ['--step' => ['S16E'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S16E'], '--no-report' => true])->assertSuccessful();   // again: one redirect

    $event = Event::query()->withoutGlobalScopes()->firstOrFail();
    expect(Redirect::query()->where('source_path', '/events-detail/583')->count())->toBe(1);
    $this->get('/events-detail/583')->assertRedirect($event->url())->assertStatus(301);
    $this->get($event->url())->assertOk()->assertSee('To the factory.')->assertSee('Gazipur');
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
