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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Legacy\Migration\Steps\S08Admission;
use Legacy\Migration\Steps\S19People;
use Legacy\Migration\Support\IdMap;
use Legacy\Migration\Support\Normalizer;
use Modules\Administration\Models\AdministrationCategory;
use Modules\Administration\Models\Member;
use Modules\Admission\Support\AdmissionPage;
use Modules\Department\Models\Department;
use Modules\Events\Models\Event;
use Modules\Facilities\Models\Facility;
use Modules\Faculty\Models\Faculty;
use Modules\Menu\Models\Menu;
use Modules\Notice\Models\Notice;
use Modules\Office\Models\Office;
use Modules\Page\Models\Page;
use Modules\Program\Database\Seeders\ProgramDatabaseSeeder;
use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramFacilitator;
use Modules\Program\Models\ProgramListItem;
use Modules\Program\Models\ProgramMajor;
use Modules\Program\Models\ProgramMaterial;
use Modules\Program\Models\ProgramVariant;
use Modules\Seo\Models\Redirect;
use Modules\Settings\Groups\ContactSettings;
use Modules\Settings\Groups\GeneralSettings;
use Modules\Testimonials\Models\Testimonial;
use Modules\Tuition\Models\FeeCategory;
use Modules\Tuition\Models\Tuition;
use Modules\Tuition\Models\TuitionLine;
use Platform\Assignments\Assignment;
use Platform\Assignments\ManualPerson;
use Platform\Assignments\RoleCatalog;
use Platform\Media\MediaItem;
use Platform\Media\MediaLibrary;
use Platform\Relations\RelationEngine;
use Platform\Statistics\Statistics;
use Platform\Statistics\StatSet;
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
        'faculties' => fn (Blueprint $t) => [$t->string('faculty_name'), $t->string('faculty_short_name'), $t->string('faculty_photo')->nullable(), $t->integer('faculty_serial')->nullable(), $t->text('faculty_content')->nullable(), $t->tinyInteger('publication_status')->default(1), $t->tinyInteger('is_optional')->default(0), $t->tinyInteger('show_list')->nullable(), $t->timestamp('deleted_at')->nullable(),
            ...collect(['publication_title', 'publication', 'partner_title', 'active_partner', 'research_title', 'research_lab', 'phd_faculty_title', 'phd_faculty'])->map(fn (string $c) => $t->string($c)->nullable())],
        'departments' => fn (Blueprint $t) => [$t->unsignedBigInteger('faculty_id'), $t->string('department_name'), $t->string('short_name'), $t->string('department_photo')->nullable(), $t->string('department_cover')->nullable(), $t->string('department_link')->nullable(), $t->integer('department_serial')->nullable(), $t->string('about_title')->nullable(), $t->text('department_content')->nullable(), $t->tinyInteger('publication_status')->default(1), $t->timestamp('deleted_at')->nullable()],
        'events' => fn (Blueprint $t) => [$t->string('title')->nullable(), $t->longText('content')->nullable(), $t->date('event_date')->nullable(), $t->string('event_time')->nullable(), $t->string('location')->nullable(), $t->string('url')->nullable(), $t->string('photo')->nullable(), $t->unsignedBigInteger('department_id')->nullable(), $t->unsignedBigInteger('lab_facility_id')->nullable(), $t->unsignedBigInteger('event_category_id')->nullable(), $t->string('is_home')->default('0'), $t->tinyInteger('publication_status')->default(1), $t->timestamp('created_at')->nullable(), $t->timestamp('deleted_at')->nullable()],
        'testimonials' => fn (Blueprint $t) => [$t->string('name'), $t->string('designation')->nullable(), $t->string('organization')->nullable(), $t->text('quote')->nullable(), $t->string('photo')->nullable(), $t->integer('sort_order')->default(0), $t->string('testimonialable_type')->nullable(), $t->unsignedBigInteger('testimonialable_id')->nullable()],
        'mba_testimonials' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_program_type_id')->nullable(), $t->string('name'), $t->string('batch')->nullable(), $t->string('photo')->nullable(), $t->text('description')->nullable(), $t->tinyInteger('publication_status')->default(1)],
        'admin_categories' => fn (Blueprint $t) => [$t->string('category_name'), $t->string('category_slug')->nullable(), $t->integer('serial')->nullable(), $t->tinyInteger('publication_status')->default(1), $t->timestamp('deleted_at')->nullable()],
        'admins' => fn (Blueprint $t) => [$t->unsignedBigInteger('admin_category_id'), $t->string('name'), $t->string('designation')->nullable(), $t->string('email')->nullable(), $t->string('phone')->nullable(), $t->string('extension')->nullable(), $t->string('office_id')->nullable(), $t->string('photo')->nullable(), $t->integer('serial')->nullable(), $t->tinyInteger('publication_status')->default(1), $t->string('unique_id')->nullable(), $t->timestamp('deleted_at')->nullable()],
        'staff_categories' => fn (Blueprint $t) => [$t->string('category_name'), $t->string('category_slug')->nullable(), $t->integer('serial')->nullable(), $t->tinyInteger('publication_status')->default(1), $t->timestamp('deleted_at')->nullable()],
        'staff' => fn (Blueprint $t) => [$t->unsignedBigInteger('staff_category_id'), $t->string('name'), $t->string('designation')->nullable(), $t->string('email')->nullable(), $t->string('phone')->nullable(), $t->string('extension')->nullable(), $t->string('office_id')->nullable(), $t->string('photo')->nullable(), $t->integer('serial')->nullable(), $t->tinyInteger('publication_status')->default(1), $t->timestamp('deleted_at')->nullable()],
        'notice_categories' => fn (Blueprint $t) => [$t->string('name'), $t->timestamp('deleted_at')->nullable()],
        'notices' => fn (Blueprint $t) => [$t->string('title')->nullable(), $t->string('slug')->nullable(), $t->longText('content')->nullable(), $t->unsignedBigInteger('department_id')->nullable(), $t->unsignedBigInteger('notice_category_id')->nullable(), $t->string('is_home')->default('0'), $t->integer('view_counter')->default(0), $t->tinyInteger('publication_status')->default(1), $t->timestamp('created_at')->nullable(), $t->timestamp('deleted_at')->nullable()],
        'notice_files' => fn (Blueprint $t) => [$t->unsignedBigInteger('notice_id'), $t->string('file_title')->nullable(), $t->string('file_name')->nullable(), $t->timestamp('deleted_at')->nullable()],
        'articles' => fn (Blueprint $t) => [$t->string('article_title')->nullable(), $t->string('article_slug')->nullable(), $t->longText('article_content')->nullable(), $t->tinyInteger('publication_status')->default(1)],
        'offices' => fn (Blueprint $t) => [$t->string('title'), $t->string('slug'), $t->tinyInteger('show_page')->default(0), $t->integer('serial')->default(0), $t->tinyInteger('publication_status')->default(1)],
        'sections' => fn (Blueprint $t) => [$t->unsignedBigInteger('office_id'), $t->string('section_name')],
        'section_employees' => fn (Blueprint $t) => [$t->unsignedBigInteger('section_id'), $t->unsignedBigInteger('admin_id')->nullable(), $t->integer('serial')->default(0)],
        'office_admins' => fn (Blueprint $t) => [$t->unsignedBigInteger('office_id'), $t->unsignedBigInteger('admin_id')->nullable(), $t->integer('admin_serial')->default(0)],
        'support_contacts' => fn (Blueprint $t) => [$t->unsignedBigInteger('office_id'), $t->unsignedBigInteger('admin_id')->nullable(), $t->string('purpose')->nullable(), $t->string('phone')->nullable(), $t->string('extension')->nullable(), $t->string('office_location')->nullable(), $t->integer('serial')->default(0), $t->tinyInteger('publication_status')->default(1)],
        'side_menu_categories' => fn (Blueprint $t) => [$t->string('title'), $t->string('slug'), $t->string('menu_position')->nullable(), $t->tinyInteger('is_department')->default(0), $t->tinyInteger('status')->default(1)],
        'side_menus' => fn (Blueprint $t) => [$t->string('title'), $t->string('link_type')->nullable(), $t->string('link')->nullable(), $t->integer('serial')->default(0), $t->unsignedBigInteger('side_menu_category_id'), $t->tinyInteger('status')->default(1)],
        'office_sites' => fn (Blueprint $t) => [$t->string('title'), $t->string('slug'), $t->string('banner_photo')->nullable(), $t->string('banner_title')->nullable(), $t->text('description')->nullable(), $t->string('location')->nullable(), $t->string('extension')->nullable(), $t->string('phone')->nullable(), $t->string('email')->nullable(), $t->integer('sort_order')->default(0), $t->tinyInteger('is_active')->default(1)],
        'office_site_articles' => fn (Blueprint $t) => [$t->string('title'), $t->string('slug'), $t->longText('content')->nullable(), $t->unsignedBigInteger('office_site_id'), $t->tinyInteger('is_active')->default(1)],
        'office_site_menus' => fn (Blueprint $t) => [$t->string('title'), $t->string('link_type'), $t->string('article_link')->nullable(), $t->string('module_link')->nullable(), $t->string('external_link')->nullable(), $t->string('file')->nullable(), $t->unsignedBigInteger('office_site_id'), $t->string('target')->nullable(), $t->integer('sort_order')->default(0), $t->tinyInteger('is_active')->default(1)],
        'office_site_faqs' => fn (Blueprint $t) => [$t->text('question'), $t->text('answer')->nullable(), $t->integer('sort_order')->default(0), $t->unsignedBigInteger('office_site_id'), $t->tinyInteger('is_active')->default(1)],
        'office_site_teams' => fn (Blueprint $t) => [$t->string('name'), $t->string('designation')->nullable(), $t->string('phone')->nullable(), $t->string('photo')->nullable(), $t->string('email')->nullable(), $t->string('extension')->nullable(), $t->integer('serial')->default(0), $t->unsignedBigInteger('office_site_id'), $t->tinyInteger('is_active')->default(1)],
        'facilities' => fn (Blueprint $t) => [$t->unsignedBigInteger('reference_id')->nullable(), $t->unsignedBigInteger('facilityable_id')->nullable(), $t->string('facilityable_type')->nullable(), $t->string('facility_title')->nullable(), $t->string('facility_slug')->nullable(), $t->text('facility_content')->nullable(), $t->string('facility_icon')->nullable(), $t->string('facility_link')->nullable(), $t->string('image')->nullable(), $t->string('target')->nullable(), $t->integer('serial')->default(0), $t->tinyInteger('publication_status')->default(1), $t->tinyInteger('is_home')->default(0), $t->timestamp('deleted_at')->nullable()],
        'coordination_officers' => fn (Blueprint $t) => [$t->unsignedBigInteger('department_id'), $t->string('department_program_name')->nullable(), $t->unsignedBigInteger('admin_id')->nullable(), $t->string('name')->nullable(), $t->string('designation')->nullable(), $t->string('phone')->nullable(), $t->string('email')->nullable(), $t->string('extension')->nullable(), $t->integer('serial')->default(0), $t->tinyInteger('publication_status')->default(1)],
        'flipbooks' => fn (Blueprint $t) => [$t->string('title'), $t->string('slug'), $t->string('thumb')->nullable(), $t->unsignedBigInteger('flipbook_category_id')->nullable(), $t->string('file_name')->nullable(), $t->integer('serial')->default(0), $t->tinyInteger('publication_status')->default(1)],
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

it('gives the seat plan DIU\'s examination buildings, named pictures in Settings › Admission, the applicant\'s marked (S03 admission)', function () {
    Storage::fake('public');
    $cache = storage_path('framework/testing/legacy-files');
    config(['database.connections.legacy.files_path' => $cache]);
    File::ensureDirectoryExists("{$cache}/frontend/images/set-plan");
    foreach (array_values(S08Admission::SEAT_BUILDINGS) as $i => $path) {
        $picture = imagecreatetruecolor(40 + $i, 30);   // three different pictures, each in its own format
        str_ends_with($path, '.webp') ? imagewebp($picture, "{$cache}/frontend/{$path}") : imagejpeg($picture, "{$cache}/frontend/{$path}");
    }

    $this->artisan('legacy:migrate', ['--step' => ['S03'], '--only' => ['admission'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S03'], '--only' => ['admission'], '--no-report' => true])->assertSuccessful();   // again: no copies

    $ids = settings('admission.seat_buildings');
    expect($ids)->toHaveCount(3)->and(MediaItem::query()->count())->toBe(3)
        ->and(collect((new AdmissionPage)->seatBuildings('Inspiration Building (IB)'))->map(fn (array $b): string => $b['name'].($b['mine'] ? ' *' : ''))->all())
        ->toBe(['Inspiration Building *', 'Knowledge Tower', "Scholar's Home"]);   // "(IB)" aside, as DIU's
    $this->get('/admission/seat-plan')->assertOk()->assertSeeInOrder(['Inspiration Building', 'Knowledge Tower', 'Scholar&#039;s Home'], false);

    // Pictures chosen in Settings stay
    settings()->save('admission', ['seat_buildings' => [$ids[1]]]);
    $this->artisan('legacy:migrate', ['--step' => ['S03'], '--only' => ['admission'], '--no-report' => true])->assertSuccessful();
    expect(settings('admission.seat_buildings'))->toBe([$ids[1]]);

    File::deleteDirectory($cache);
});

it('migrates main-site articles as general pages at their own address, with Bootstrap markup and DIU\'s old addresses (S15A)', function () {
    DB::connection('legacy')->table('articles')->insert([
        ['id' => 1, 'article_title' => 'International Adviser of DIU', 'article_slug' => 'international-adviser', 'publication_status' => 1,
            'article_content' => '<table class="table" border="1"><tr><td><img src="/storage/photos/walter.jpg"></td><td><a href="/photos/cv.pdf">CV</a> <a href="/article/visiting-professors">More</a></td></tr></table>'],
        ['id' => 2, 'article_title' => 'Old Draft', 'article_slug' => 'old-draft', 'publication_status' => 0, 'article_content' => '<p>x</p>'],
        ['id' => 3, 'article_title' => 'Lost and Found', 'article_slug' => 'safetyandsecurity', 'publication_status' => 1, 'article_content' => '<p>Lost something?</p>'],
        ['id' => 4, 'article_title' => 'Safety and Security Management', 'article_slug' => 'safety-measures-of-daffodil-international-university', 'publication_status' => 1, 'article_content' => '<p>Guards.</p>'],
        ['id' => 5, 'article_title' => 'International Alumni', 'article_slug' => 'international-alumni', 'publication_status' => 1, 'article_content' => '<p>Alumni abroad.</p>'],
        ['id' => 6, 'article_title' => 'Faculty and Programs', 'article_slug' => 'programs', 'publication_status' => 1, 'article_content' => '<p>Program list as text.</p>'],   // /programs is the program list
        ['id' => 7, 'article_title' => 'FAQ', 'article_slug' => 'faq', 'publication_status' => 1, 'article_content' => '<p>Questions as text.</p>'],
        ['id' => 8, 'article_title' => 'Admission Test Schedule', 'article_slug' => 'admission-test-schedule', 'publication_status' => 1, 'article_content' => '<p>Test on 26 December.</p>'],
    ]);
    // Before 2026-10-31 they hung under a page "article": it goes, they move up
    $old = Page::factory()->create(['title' => ['en' => 'Articles']]);
    $old->changeSlug('en', 'article');
    // The schedule goes under /admission, in its frame (its Browse by Section, its Quick Links)
    $admission = Page::factory()->create(['title' => ['en' => 'Admission'], 'quick_links' => [['label' => ['en' => 'Waiver Calculator'], 'url' => '/waiver-calculator']]]);
    $admission->changeSlug('en', 'admission');
    Redirect::query()->create(['source_path' => '/admission/schedule', 'destination_url' => '/admission-test-schedule', 'status_code' => 301, 'is_active' => true]);   // before 2026-11-02

    $this->artisan('legacy:migrate', ['--step' => ['S15A'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S15A'], '--no-report' => true])->assertSuccessful();   // again: no copies

    expect(Page::query()->withoutGlobalScopes()->whereNull('deleted_at')->count())->toBe(9)   // the eight and /admission, without "article"
        ->and(Page::query()->withoutGlobalScopes()->whereNotNull('parent_id')->pluck('parent_id')->all())->toBe([$admission->id])
        ->and($old->fresh()->trashed())->toBeTrue();
    $this->get('/admission/schedule')->assertOk()->assertSee('Test on 26 December.')->assertSee('Waiver Calculator');
    $this->get('/admission-test-schedule')->assertRedirect('/admission/schedule');
    $this->get('/article/admission-test-schedule')->assertRedirect('/admission/schedule');
    expect(Page::query()->withoutGlobalScopes()->whereNotNull('parent_id')->sole()->template)->toBe('with-sidebar')
        ->and($old->fresh()->trashed())->toBeTrue();
    $this->get('/international-adviser')->assertOk()
        ->assertSee('International Adviser of DIU')
        ->assertSee('class="table" border="1"', false)
        ->assertSee('src="https://webbackend.daffodilvarsity.edu.bd/storage/photos/walter.jpg"', false)   // the old site's files from webbackend
        ->assertSee('href="https://webbackend.daffodilvarsity.edu.bd/photos/cv.pdf"', false)
        ->assertSee('href="/visiting-professors"', false);                                                // page links follow
    $this->get('/old-draft')->assertNotFound();

    // Old addresses: /article/{slug}, DIU's new site's (/international/{slug}, /safetyandsecurity → another article)
    $this->get('/article/international-adviser')->assertRedirect('/international-adviser')->assertStatus(301);
    $this->get('/article')->assertRedirect('/');
    $this->get('/international/international-alumni')->assertRedirect('/international-alumni');
    $this->get('/safetyandsecurity')->assertRedirect('/safety-measures-of-daffodil-international-university');
    $this->get('/article/safetyandsecurity')->assertRedirect('/lost-and-found');
    $this->get('/lost-and-found')->assertOk()->assertSee('Lost something?');

    // A module's own page keeps its address: the article takes its title's, or that with "-page"
    $this->get('/article/programs')->assertRedirect('/faculty-and-programs');
    $this->get('/faculty-and-programs')->assertOk()->assertSee('Program list as text.');
    $this->get('/programs')->assertDontSee('Program list as text.');
    $this->get('/article/faq')->assertRedirect('/faq-page');
    $this->get('/faq')->assertDontSee('Questions as text.');
});

it('groups DIU\'s article sections under a sidebar list, with its addresses (S15X)', function () {
    DB::connection('legacy')->table('articles')->insert([
        ['id' => 1, 'article_title' => 'BSCIC Industrial Support Centre', 'article_slug' => 'bscic-diu', 'publication_status' => 1, 'article_content' => '<p>BSCIC intro.</p>'],
        ['id' => 2, 'article_title' => 'Contact BSCIC', 'article_slug' => 'contact-bscic', 'publication_status' => 1, 'article_content' => '<p>Call us.</p>'],
        ['id' => 3, 'article_title' => 'DIU Medical Center', 'article_slug' => 'diu-medical-center', 'publication_status' => 1, 'article_content' => '<p>Care.</p>'],
        ['id' => 4, 'article_title' => 'Available Medical Personnel', 'article_slug' => 'medical-personnel', 'publication_status' => 1, 'article_content' => '<p>Doctors.</p>'],
        ['id' => 5, 'article_title' => 'DIU Delegates 2018', 'article_slug' => 'diu-delegates-2018', 'publication_status' => 1, 'article_content' => '<p>2018.</p>'],
        ['id' => 6, 'article_title' => 'DIU Delegates 2019', 'article_slug' => 'diu-delegates-2019', 'publication_status' => 1, 'article_content' => '<p>2019.</p>'],
    ]);
    DB::connection('legacy')->table('side_menu_categories')->insert(['id' => 2, 'title' => 'Medical', 'slug' => 'medical', 'menu_position' => 'left', 'is_department' => 0, 'status' => 1]);
    DB::connection('legacy')->table('side_menus')->insert([
        ['id' => 1, 'title' => 'DIU Medical Center', 'link_type' => 'article', 'link' => 'introduction', 'serial' => 1, 'side_menu_category_id' => 2],   // names the wrong article (LINK_FIXES)
        ['id' => 2, 'title' => 'Medical Personnel', 'link_type' => 'article', 'link' => 'medical-personnel', 'serial' => 2, 'side_menu_category_id' => 2],
    ]);

    $this->artisan('legacy:migrate', ['--step' => ['S15A', 'S15X'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S15A', 'S15X'], '--no-report' => true])->assertSuccessful();   // again: no copies

    $page = fn (string $slug): Page => Page::query()->withoutGlobalScopes()->get()->first(fn (Page $p): bool => $p->slug('en') === $slug);
    $bscic = Menu::query()->withoutGlobalScopes()->where('name', 'BSCIC Industrial Support Centre')->sole();
    $medical = Menu::query()->withoutGlobalScopes()->where('name', 'Medical')->sole();
    expect($bscic->location)->toBeNull()
        ->and($bscic->items()->orderBy('sort_order')->get()->map(fn ($i) => $i->getTranslation('title', 'en'))->all())->toBe(['Introduction', 'Contact'])
        ->and($page('bscic-diu'))->template->toBe('with-sidebar')->sidebar_menu_id->toBe($bscic->id)
        ->and($page('bscic-diu')->getTranslation('excerpt', 'en'))->toBe('Bridging Academia and Industry for Innovation and Excellence')
        ->and($page('bscic-diu')->quick_links)->toHaveCount(4)
        ->and($medical->items()->orderBy('sort_order')->pluck('linkable_id')->all())->toBe([$page('diu-medical-center')->id, $page('medical-personnel')->id])
        ->and($page('medical-personnel'))->template->toBe('right-sidebar')->sidebar_menu_id->toBe($medical->id)
        ->and(Menu::query()->withoutGlobalScopes()->where('name', 'DIU Delegates')->sole()->items()->orderBy('sort_order')->get()->map(fn ($i) => $i->getTranslation('title', 'en'))->all())->toBe(['DIU Delegates 2019', 'DIU Delegates 2018']);   // newest first

    $this->get('/contact-bscic')->assertOk()->assertSee('Browse by Section')->assertSee('Introduction')->assertSee('Waiver Calculator');
    $this->get('/bscic/contact-bscic')->assertRedirect('/contact-bscic')->assertStatus(301);
    $this->get('/medical/introduction')->assertRedirect('/diu-medical-center');
    $this->get('/delegates/diu-delegates-2018')->assertRedirect('/diu-delegates-2018');

    // A layout Admin chose stays
    $page('contact-bscic')->forceFill(['template' => 'full-width', 'sidebar_menu_id' => null])->save();
    $this->artisan('legacy:migrate', ['--step' => ['S15X'], '--no-report' => true])->assertSuccessful();
    expect($page('contact-bscic')->template)->toBe('full-width');
});

it('makes the pages DIU\'s new site keeps in code — Student Access Center, Teachers, Location — and its campus (S15P)', function () {
    DB::connection('legacy')->table('articles')->insert(['id' => 1, 'article_title' => 'Students', 'article_slug' => 'students', 'publication_status' => 1, 'article_content' => '<p>Old students text.</p>']);

    $this->artisan('legacy:migrate', ['--step' => ['S15A', 'S15P'], '--no-report' => true])->assertSuccessful();
    Http::fake(['studio.skill.jobs/*' => Http::response(['results' => [
        ['slug' => 'coordination-officer-X', 'title' => 'Coordination Officer', 'type' => 'Full Time', 'isExpired' => false],
    ]])]);

    $this->get('/career')->assertOk()
        ->assertSeeInOrder(['Career at DIU', 'Culture in DIU', 'DIU Values', 'Ethics', 'Ownership &amp; Responsibility', 'Available Job Listings', 'Coordination Officer'], false);
    $this->get('/students')->assertOk()
        ->assertSeeInOrder(['Student Access Center', 'Academic Essentials', 'Student Portal', 'Learning Resources', 'Support &amp; Coordination', 'Support Contact'], false)
        ->assertSeeInOrder(['href="https://studentportal.diu.edu.bd/"', 'target="_blank"', 'href="/office/registrar-office/academic-calendar"'], false)->assertDontSee('Old students text.');
    $this->get('/article/students')->assertRedirect('/students');
    $this->get('/teachers')->assertOk()->assertSeeInOrder(['Teachers Directory', 'Deans &amp; Heads', 'Faculty Members'], false);
    $this->get('/location')->assertOk()
        ->assertSeeInOrder(['Daffodil International University', 'Birulia, Savar', 'Admission Help Line: 09617901212', 'admission@daffodilvarsity.edu.bd', 'https://maps.app.goo.gl/istRkPfCLxi9WqAn9'])
        ->assertSee('tel:09617901212', false)->assertSee('https://www.google.com/maps/embed?', false);

    // Once made, Admin's: running again leaves a changed page and a typed campus alone
    Page::query()->withoutGlobalScopes()->get()->first(fn ($p) => $p->slug('en') === 'teachers')->update(['title' => ['en' => 'Our Teachers']]);
    settings()->saveFor(new ContactSettings, ['campuses' => [['name' => ['en' => 'Main Campus']]]]);
    $this->artisan('legacy:migrate', ['--step' => ['S15A', 'S15P'], '--no-report' => true])->assertSuccessful();
    $this->get('/teachers')->assertSee('Our Teachers');
    $this->get('/location')->assertSee('Main Campus')->assertDontSee('Birulia');
});

it('keeps an office page that was turned on later, and sends DIU\'s office addresses here (S10)', function () {
    DB::connection('legacy')->table('offices')->insert([
        ['id' => 2, 'title' => 'Exam Office', 'slug' => 'exam-office', 'show_page' => 0],
        ['id' => 9, 'title' => 'Proctorial Office', 'slug' => 'proctorial-office', 'show_page' => 0],
    ]);
    $this->artisan('legacy:migrate', ['--step' => ['S10'], '--no-report' => true])->assertSuccessful();
    $proctor = Office::query()->withoutGlobalScopes()->where('name->en', 'Proctorial Office')->sole();
    $proctor->update(['has_microsite' => true]);   // as S15O or Admin does

    $this->artisan('legacy:migrate', ['--step' => ['S10'], '--no-report' => true])->assertSuccessful();

    expect($proctor->fresh()->has_microsite)->toBeTrue();
    $this->get('/office/exam')->assertRedirect('/office/exam-office');
    $this->get('/office/exam/faqs')->assertRedirect('/office/exam-office/faq');
    $this->get('/support-contact')->assertRedirect('/support-contacts');
});

it('fills DIU\'s office sites into their office, a new one for TTO, with pages, menu, FAQs, team and facilities (S15T, S18F)', function () {
    $l = DB::connection('legacy');
    $l->table('offices')->insert([['id' => 2, 'title' => 'Exam Office', 'slug' => 'exam-office', 'show_page' => 0]]);
    $l->table('office_sites')->insert([
        ['id' => 1, 'title' => 'Exam', 'slug' => 'exam', 'banner_title' => 'Office of the Examinations', 'description' => '<p>Runs all examinations.</p>', 'location' => 'Daffodil Smart City', 'extension' => '90071', 'phone' => '09617901212', 'email' => 'exam@example.edu', 'sort_order' => 1],
        ['id' => 2, 'title' => 'Technology Transfer Office', 'slug' => 'tto', 'banner_title' => null, 'description' => '<p>Industry and academia.</p>', 'location' => null, 'extension' => '126', 'phone' => null, 'email' => 'tto@example.edu', 'sort_order' => 2],
    ]);
    $l->table('office_site_articles')->insert([
        ['id' => 2, 'title' => 'Service Standards', 'slug' => 'service-standards-9hsg', 'content' => '<p>Service to students.</p>', 'office_site_id' => 1],
        ['id' => 10, 'title' => 'Project Objectives', 'slug' => 'project-objectives-21fn', 'content' => '<p>Commercialise research.</p>', 'office_site_id' => 2],
    ]);
    $l->table('office_site_menus')->insert([
        ['id' => 2, 'title' => 'Service Standards', 'link_type' => 'article', 'article_link' => 'service-standards-9hsg', 'file' => null, 'office_site_id' => 1, 'target' => '_self', 'sort_order' => 1],
        ['id' => 12, 'title' => 'Exam Rules for Students', 'link_type' => 'file', 'article_link' => null, 'file' => 'images/office-site/menus/rules.pdf', 'office_site_id' => 1, 'target' => '_blank', 'sort_order' => 9],
    ]);
    $l->table('office_site_faqs')->insert([['id' => 4, 'question' => 'How do I see my result?', 'answer' => 'On the Student Portal.', 'office_site_id' => 1]]);
    $l->table('office_site_teams')->insert([
        ['id' => 1, 'name' => 'Mr. Mominul Haque Majumder', 'designation' => 'Controller of Examinations', 'phone' => '+8801713493005', 'email' => 'controller@example.edu', 'extension' => '65114', 'office_site_id' => 1, 'serial' => 0],
        ['id' => 2, 'name' => 'Ms. Kanij Fatema', 'designation' => 'Assistant Exam Officer', 'phone' => null, 'email' => 'controller@example.edu', 'extension' => null, 'office_site_id' => 1, 'serial' => 1],   // the office's shared mailbox
        ['id' => 3, 'name' => 'Md. Rafiqul Islam Anik', 'designation' => 'Assistant Coordination Officer', 'phone' => null, 'email' => null, 'extension' => null, 'office_site_id' => 1, 'serial' => 2],   // Administration: "Mr. Rafiqul Islam"
    ]);
    $l->table('facilities')->insert([['id' => 454, 'facilityable_id' => 2, 'facilityable_type' => 'App\\Models\\OfficeSite', 'facility_title' => 'AI-Based Systems', 'facility_icon' => 'fa-building', 'image' => 'images/office-site/facilities/a.jpg']]);

    $this->artisan('legacy:migrate', ['--step' => ['S10', 'S15T', 'S18F'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S15T'], '--no-report' => true])->assertSuccessful();   // again: no copies

    $exam = Office::query()->withoutGlobalScopes()->where('name->en', 'Exam Office')->sole();
    expect($exam->email)->toBe('exam@example.edu')->and($exam->extension)->toBe('90071')->and($exam->has_microsite)->toBeTrue();
    $tto = Office::query()->withoutGlobalScopes()->where('name->en', 'Technology Transfer Office')->sole();
    expect($tto->url('en'))->toBe('/office/tto')
        ->and(app(RelationEngine::class)->targetsOf(Facility::query()->withoutGlobalScopes()->sole()))->toBe([$tto->getMorphClass().':'.$tto->getKey()]);

    $this->get('/office/exam-office')->assertOk()->assertSee('Runs all examinations.')->assertSee('Daffodil Smart City');
    $this->get('/office/exam/service-standards-9hsg')->assertRedirect('/office/exam-office/service-standards');
    $this->get('/office/exam-office/service-standards')->assertOk()->assertSee('Service to students.');
    $this->get('/office/exam-office/faq')->assertOk()->assertSee('How do I see my result?');
    $this->get('/office/exam-office/team')->assertOk()->assertSee('Mr. Mominul Haque Majumder');
    $this->get('/office/tto')->assertOk()->assertSee('Industry and academia.');
    $menu = Menu::query()->withoutGlobalScopes()->ownedBy($exam->getMorphClass().':'.$exam->getKey())->where('location', 'sidebar')->sole();
    expect($menu->items()->orderBy('sort_order')->get()->map(fn ($i) => $i->getTranslation('title', 'en'))->all())->toBe(['Service Standards', 'Exam Rules for Students'])
        ->and(Assignment::query()->where('role_key', 'team')->pluck('person_type')->unique()->all())->toBe([(new ManualPerson)->getMorphClass()]);   // no member yet

    // Once Administration has the person (same email), the office team is tagged to the member, not a copy
    $l->table('admin_categories')->insert([['id' => 1, 'category_name' => 'Management', 'category_slug' => 'management', 'serial' => 1],
        ['id' => 2, 'category_name' => 'Assistant Officer', 'category_slug' => 'assistant-officer', 'serial' => 2]]);
    $l->table('admins')->insert([['id' => 527, 'admin_category_id' => 1, 'name' => 'Mominul Haque Majumder', 'designation' => 'Controller of Examinations', 'email' => 'CONTROLLER@example.edu', 'serial' => 1, 'unique_id' => 'mominul'],
        ['id' => 528, 'admin_category_id' => 2, 'name' => 'Mr. Rafiqul Islam', 'designation' => 'Assistant Coordination Officer', 'email' => null, 'serial' => 1, 'unique_id' => 'rafiqul']]);
    $this->artisan('legacy:migrate', ['--step' => ['S11', 'S19P'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S15T'], '--no-report' => true])->assertSuccessful();

    $member = Member::query()->withoutGlobalScopes()->where('email', 'CONTROLLER@example.edu')->where('employment_type', 'regular')->sole();
    $team = Assignment::query()->where('role_key', 'team')->orderBy('sort_order')->get();
    expect($team->map(fn ($a) => $a->person_type)->unique()->all())->toBe([$member->getMorphClass()])
        ->and($team[0]->person_id)->toBe($member->getKey())
        ->and(ManualPerson::query()->withoutGlobalScopes()->where('name->en', 'like', '%Mominul%')->orWhere('name->en', 'like', '%Kanij%')->exists())->toBeFalse();

    // A fuller name of one active member is that member (owner 2026-11-01: if they are in Administration, tag them)
    $rafiqul = Member::query()->withoutGlobalScopes()->where('name->en', 'Mr. Rafiqul Islam')->sole();
    expect($team[2]->person_id)->toBe($rafiqul->getKey());

    // The same mailbox, another name: not that member — someone Administration lacks, so contractual for now,
    // in the category of the designation ("Assistant Exam Officer" → Assistant Officer)
    $kanij = Member::query()->withoutGlobalScopes()->findOrFail($team[1]->person_id);
    expect($kanij->getTranslation('name', 'en'))->toBe('Ms. Kanij Fatema')
        ->and($kanij->employment_type)->toBe('contractual')
        ->and($kanij->category->getTranslation('name', 'en'))->toBe('Assistant Officer')
        ->and($kanij->isFrontendVisible())->toBeFalse();
    $this->get('/office/exam-office/team')->assertOk()->assertSee('Ms. Kanij Fatema')->assertSee('Mr. Rafiqul Islam');
    $this->get('/administration?q=rafiqul')->assertOk()->assertSee('Mr. Rafiqul Islam');
    $this->get('/administration?q=kanij')->assertOk()->assertDontSee('Ms. Kanij Fatema');

    // Reruns keep the same people, no copies
    $this->artisan('legacy:migrate', ['--step' => ['S11', 'S15T', 'S19P'], '--no-report' => true])->assertSuccessful();
    expect(Member::query()->withoutGlobalScopes()->where('employment_type', 'contractual')->count())->toBe(1)
        ->and(Assignment::query()->where('role_key', 'team')->orderBy('sort_order')->pluck('person_id')->all())->toBe([$member->getKey(), $kanij->getKey(), $rafiqul->getKey()])
        ->and(ManualPerson::query()->withoutGlobalScopes()->where(fn ($q) => $q->where('name->en', 'like', '%Mominul%')->orWhere('name->en', 'like', '%Kanij%')->orWhere('name->en', 'like', '%Rafiqul%'))->exists())->toBeFalse()
        ->and(AdministrationCategory::query()->where('name->en', S19People::CONTRACTUAL_CATEGORY)->exists())->toBeFalse();   // everyone fitted a category
});

it('puts the faculty figures of newer dumps into the faculty\'s counters (S04)', function () {
    DB::connection('legacy')->table('faculties')->where('id', 1)->update(['publication_title' => 'Intellectual Contributions', 'publication' => '390+', 'partner_title' => 'Active Industry Partnerships',
        'active_partner' => '20+', 'research_title' => 'Diverse Specialized Labs', 'research_lab' => '12', 'phd_faculty_title' => 'PhD Scholarly Faculty', 'phd_faculty' => '41%']);

    $this->artisan('legacy:migrate', ['--step' => ['S04'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S04'], '--no-report' => true])->assertSuccessful();

    $fsit = Faculty::query()->sole();
    $set = StatSet::query()->ownedBy($fsit->getMorphClass().':'.$fsit->getKey())->where('key', 'default')->sole();
    expect(collect(app(Statistics::class)->forSet($set, 'en'))->map(fn ($s) => $s['display'].' '.$s['label'])->all())
        ->toBe(['390+ Intellectual Contributions', '20+ Active Industry Partnerships', '12 Diverse Specialized Labs', '41% PhD Scholarly Faculty']);
});

it('trashes what DIU removed, unless another row still leads to it, and brings it back with its row (S24R)', function () {
    $l = DB::connection('legacy');
    $l->table('events')->insert([
        ['id' => 1, 'title' => 'Old fair', 'content' => null, 'event_date' => '2021-01-24', 'event_time' => null, 'location' => null, 'is_home' => '1'],
        ['id' => 2, 'title' => 'New fair', 'content' => null, 'event_date' => '2026-01-24', 'event_time' => null, 'location' => null, 'is_home' => '1'],
    ]);
    $l->table('offices')->insert([['id' => 3, 'title' => 'Registrar Office', 'slug' => 'registrar-office', 'show_page' => 1]]);
    $this->artisan('legacy:migrate', ['--step' => ['S10', 'S16E'], '--no-report' => true])->assertSuccessful();

    $l->table('events')->where('id', 1)->delete();
    $l->table('departments')->where('id', 36)->delete();   // the Registrar's office-"department": its office still comes from offices
    $this->artisan('legacy:migrate', ['--step' => ['S24R'], '--no-report' => true])->assertSuccessful();

    expect(Event::query()->withoutGlobalScopes()->withTrashed()->where('title->en', 'Old fair')->sole()->trashed())->toBeTrue()
        ->and(Event::query()->withoutGlobalScopes()->where('title->en', 'New fair')->exists())->toBeTrue()
        ->and(Office::query()->withoutGlobalScopes()->where('name->en', 'Registrar Office')->exists())->toBeTrue();

    $l->table('events')->insert([['id' => 1, 'title' => 'Old fair', 'content' => null, 'event_date' => '2021-01-24', 'event_time' => null, 'location' => null, 'is_home' => '1']]);
    $this->artisan('legacy:migrate', ['--step' => ['S16E', 'S24R'], '--no-report' => true])->assertSuccessful();
    expect(Event::query()->withoutGlobalScopes()->where('title->en', 'Old fair')->exists())->toBeTrue();
});

it('gives departments their coordination officers, the program text as label, and sends DIU\'s address to the directory (S11C)', function () {
    DB::connection('legacy')->table('coordination_officers')->insert([
        ['id' => 1, 'department_id' => 1, 'department_program_name' => 'CSE', 'admin_id' => null, 'name' => 'Mr. Md. Sorower Mal', 'designation' => 'Coordination Officer',
            'phone' => '+8801847140040', 'email' => 'cseoffice5@example.edu', 'extension' => '15114', 'serial' => 1, 'publication_status' => 1],
        ['id' => 2, 'department_id' => 2, 'department_program_name' => 'MSc in SWE', 'admin_id' => null, 'name' => 'Md. Alauddin', 'designation' => 'Coordination Officer',
            'phone' => null, 'email' => null, 'extension' => '35111', 'serial' => 1, 'publication_status' => 1],
        ['id' => 3, 'department_id' => 999, 'department_program_name' => 'X', 'admin_id' => null, 'name' => 'Nowhere', 'designation' => null, 'phone' => null, 'email' => null, 'extension' => null, 'serial' => 1, 'publication_status' => 1],
    ]);

    $this->artisan('legacy:migrate', ['--step' => ['S04', 'S11C'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S11C'], '--no-report' => true])->assertSuccessful();   // again: no copies

    expect(Assignment::query()->where('role_key', 'coordinator')->count())->toBe(2);
    $this->get('/coordination-officer')->assertRedirect('/coordination-officers')->assertStatus(301);
    $this->get('/coordination-officers')->assertOk()
        ->assertSeeInOrder(['Science and Information Technology', 'Mr. Md. Sorower Mal', '(CSE)', 'cseoffice5@example.edu', 'Ext: 15114', 'Md. Alauddin', '(MSc in SWE)', 'Ext: 35111'])
        ->assertDontSee('Nowhere');
});

it('builds the main menus from the live snapshot, a flipbook on webbackend opening its document here and articles at their own address (S15M)', function () {
    DB::connection('legacy')->table('flipbooks')->insert(['id' => 7, 'title' => 'Handbook for Academic', 'slug' => 'handbook-for-academic', 'flipbook_category_id' => 3,
        'file_name' => 'academic/academic-handbook', 'thumb' => 'images/flipbook/thumb/x.webp', 'serial' => 1, 'publication_status' => 1]);

    $this->artisan('legacy:migrate', ['--step' => ['S15M'], '--no-report' => true])->assertSuccessful();

    $urls = DB::table('menu_items')->pluck('url');
    expect($urls)->toContain('/downloads/handbook-for-academic')->toContain('/credit-transfer')
        ->not->toContain('https://webbackend.daffodilvarsity.edu.bd/academic/academic-handbook/')->not->toContain('/article/credit-transfer')
        ->and(Normalizer::flipbookPdf('/academic/academic-handbook/'))->toBe('academic/academic-handbook/PDF.pdf');
});

it('migrates events and sends DIU\'s /events-detail/{id} to the event\'s page (S16E)', function () {
    DB::connection('legacy')->table('events')->insert([
        ['id' => 583, 'title' => 'Industry visit', 'content' => '<p>To the factory.</p>', 'event_date' => '2025-07-13', 'event_time' => '09:00:00', 'location' => 'Gazipur', 'is_home' => '1'],
        ['id' => 1, 'title' => 'Nineteenth Foundation', 'content' => '<p>Cake.</p>', 'event_date' => '2021-01-24', 'event_time' => null, 'location' => null, 'is_home' => '0'],   // no department, not on DIU's homepage: still on /events, as DIU
    ]);

    $this->artisan('legacy:migrate', ['--step' => ['S16E'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S16E'], '--no-report' => true])->assertSuccessful();   // again: one redirect

    $this->followingRedirects()->get('/events-detail/1')->assertOk()->assertSee('Cake.');
    $event = Event::query()->withoutGlobalScopes()->where('title->en', 'Industry visit')->firstOrFail();
    expect(Redirect::query()->where('source_path', '/events-detail/583')->count())->toBe(1);
    $this->get('/events-detail/583')->assertRedirect($event->url())->assertStatus(301);
    $this->get($event->url())->assertOk()->assertSee('To the factory.')->assertSee('Gazipur');
});

it('migrates notices, keeps DIU\'s /noticeboard addresses and puts each notice\'s files on it with their titles (S16T, S03)', function () {
    $l = DB::connection('legacy');
    $l->table('notices')->insert([['id' => 3804, 'title' => 'Seat plan, 22 August', 'slug' => 'seat-plan-22-august', 'content' => null, 'is_home' => '1', 'view_counter' => 12, 'created_at' => '2026-08-22 20:37:14']]);
    $l->table('notice_files')->insert([
        ['notice_id' => 3804, 'file_title' => 'Slot A', 'file_name' => 'noticeFile/slot-a.pdf', 'deleted_at' => null],
        ['notice_id' => 3804, 'file_title' => 'Slot C', 'file_name' => 'noticeFile/slot-c.pdf', 'deleted_at' => null],
        ['notice_id' => 3804, 'file_title' => 'Old', 'file_name' => 'noticeFile/old.pdf', 'deleted_at' => '2026-08-23 00:00:00'],
    ]);
    Storage::fake('public');
    $cache = storage_path('framework/testing/legacy-files');
    config(['database.connections.legacy.files_path' => $cache]);
    File::ensureDirectoryExists("{$cache}/noticeFile");
    foreach (['slot-a', 'slot-c'] as $name) {
        File::put("{$cache}/noticeFile/{$name}.pdf", "%PDF-1.4 {$name}");
    }

    $this->artisan('legacy:migrate', ['--step' => ['S16T'], '--no-report' => true])->assertSuccessful();
    $this->artisan('legacy:migrate', ['--step' => ['S03'], '--only' => ['notices'], '--no-report' => true])->assertSuccessful();

    $notice = Notice::query()->withoutGlobalScopes()->firstOrFail();
    expect($notice->mediaUsagesIn('attachments')->map(fn ($u) => $u->meta['title']['en'] ?? null)->all())->toBe(['Slot A', 'Slot C']);
    $this->get('/noticeboard')->assertRedirect('/notices')->assertStatus(301);
    $this->get('/noticeboard/seat-plan-22-august')->assertRedirect($notice->url());
    $this->get($notice->url())->assertOk()->assertSeeInOrder(['View/Download', 'Seat plan, 22 August', 'Slot A', 'Slot C']);
});

it('migrates administration into DIU\'s sections of /administration (S11)', function () {
    $l = DB::connection('legacy');
    $l->table('admin_categories')->insert([
        ['id' => 1, 'category_name' => 'Management', 'category_slug' => 'management', 'serial' => 1],
        ['id' => 2, 'category_name' => 'Director', 'category_slug' => 'director', 'serial' => 2],
    ]);
    $l->table('admins')->insert([
        ['id' => 1, 'admin_category_id' => 1, 'name' => 'Professor Dr. M. R. Kabir', 'designation' => 'Vice Chancellor', 'email' => 'vc@example.edu', 'serial' => 1, 'unique_id' => 'kabir-686fdc089f3a'],
        ['id' => 2, 'admin_category_id' => 2, 'name' => 'Md. Director', 'designation' => 'Director (IT)', 'email' => null, 'serial' => 1, 'unique_id' => 'director-1'],
    ]);

    $this->artisan('legacy:migrate', ['--step' => ['S11'], '--no-report' => true])->assertSuccessful();

    $director = AdministrationCategory::query()->where('name->en', 'Director')->firstOrFail();
    $this->get($director->url())->assertOk()->assertSee('Md. Director')->assertDontSee('Professor Dr. M. R. Kabir');
    $this->get('/administration')->assertOk()->assertSeeInOrder(['Management (1)', 'Director (1)', 'Professor Dr. M. R. Kabir', 'Vice Chancellor', 'vc@example.edu']);
});

it('migrates testimonials onto their program and the main site, the first of each program chosen for the homepage, MBA copies skipped (S17M)', function () {
    $l = DB::connection('legacy');
    $type = 'App\\Models\\DepartmentProgramType';
    $l->table('testimonials')->insert([
        ['id' => 1, 'name' => 'Abrar Fahim', 'designation' => 'Managing Director', 'organization' => 'Damson', 'quote' => '<p>DIU shaped me.</p>', 'testimonialable_type' => $type, 'testimonialable_id' => 44],
        ['id' => 2, 'name' => 'Nisat Jebin', 'designation' => 'Engineer', 'organization' => null, 'quote' => '<p>Great teachers.</p>', 'testimonialable_type' => $type, 'testimonialable_id' => 44],
    ]);
    $l->table('mba_testimonials')->insert([['name' => 'Abrar Fahim', 'batch' => 'Managing Director', 'description' => '<p>Same person again.</p>']]);

    app(RoleCatalog::class)->sync();   // the "author" role
    $this->artisan('legacy:migrate', ['--step' => ['S04', 'S06', 'S17M'], '--no-report' => true])->assertSuccessful();

    $engine = app(RelationEngine::class);
    $all = Testimonial::query()->withoutGlobalScopes()->get();
    $first = $all->first(fn ($t) => $t->quote === 'DIU shaped me.');
    expect($all)->toHaveCount(2)                                                                   // the MBA copy skipped
        ->and($all->every(fn ($t) => $engine->isOnMainSite($t)))->toBeTrue()
        ->and($engine->for('site:0')->content(Testimonial::class)->highlighted(10)->pluck('id')->all())->toBe([$first->id]);
});

it('names the site as the live one while it still has the installer\'s name, never over a name typed in Admin (S15S)', function () {
    $this->artisan('legacy:migrate', ['--step' => ['S15S'], '--no-report' => true])->assertSuccessful();
    expect(settings('general.site_name'))->toBe('Daffodil International University')
        ->and(settings('general.university_name'))->toBe('Daffodil International University');

    settings()->saveFor(new GeneralSettings, ['site_name' => ['en' => 'DIU']]);
    $this->artisan('legacy:migrate', ['--step' => ['S15S'], '--no-report' => true])->assertSuccessful();
    expect(settings('general.site_name'))->toBe('DIU');
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

it('makes an At a Glance table the key facts block, its text as written, and leaves other tables as HTML (S15A, UI/UX §5.8)', function () {
    $glance = '<div class="table-responsive"><table class="table"><tbody>'
        .'<tr style="height: 44px;"><td style="width: 20%;">Year of establishment :</td><td style="width: 80%;">2002 With the approval of UGC</td></tr>'
        .'<tr><td>Faculties :</td><td><ol><li>Faculty of Engineering</li><li>Faculty of Agriculture Sciences</li></ol></td></tr>'
        .'<tr><td>Programs :</td><td><table class="table table-bordered"><tr><td style="width: 50%;">Bachelor Program</td><td>: 31</td></tr></table></td></tr>'
        .'<tr><td>Canteens :</td><td><ol><li>Green Gardeen 1</li></ol></td></tr>'
        .'</tbody></table></div><p>Note: mail at <a href="mailto:web@example.edu">web@example.edu</a></p>';

    $facts = Normalizer::factsTable($glance);

    expect(array_column($facts['rows'], 'label'))->toBe(['Year of establishment :', 'Faculties :', 'Programs :', 'Canteens :'])
        ->and($facts['rows'][0]['value'])->toBe('2002 With the approval of UGC')
        ->and($facts['rows'][2]['value'])->toBe('<table class="table table-bordered"><tr><td>Bachelor Program</td><td>: 31</td></tr></table>')   // nested table kept, sizes gone
        ->and($facts['rows'][3]['value'])->toContain('Green Gardeen 1')                                                                         // approved text, typos and all
        ->and($facts['note'])->toBe('<p>Note: mail at <a href="mailto:web@example.edu">web@example.edu</a></p>')
        ->and($facts['highlights'])->toBe([]);

    // A calendar, a committee or a single row: two-column tables, but no fact sheet
    expect(Normalizer::factsTable('<table><tr><td>Classes Start</td><td>5 May</td></tr><tr><td>First Quiz</td><td>1 June</td></tr><tr><td>Final</td><td>1 July</td></tr></table>'))->toBeNull()
        ->and(Normalizer::factsTable('<table><tr><td>Name :</td><td>DIU</td></tr></table>'))->toBeNull()
        ->and(Normalizer::factsTable('<p>Intro</p><table><tr><td>A :</td><td>1</td></tr><tr><td>B :</td><td>2</td></tr><tr><td>C :</td><td>3</td></tr></table>'))->toBeNull()
        ->and(Normalizer::factsTable('<table><tr><td>A :</td><td>1</td><td>x</td></tr><tr><td>B :</td><td>2</td></tr><tr><td>C :</td><td>3</td></tr></table>'))->toBeNull();
});
