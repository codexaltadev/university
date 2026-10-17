<?php

namespace Legacy\Migration\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;

/**
 * The seed files (owner 2026-10-17): the project_diu tables the steps read, kept in git as
 * tools/legacy-migration/seed/ so an installation without the legacy database still gets DIU's
 * content. schema.sql holds each table as MySQL created it; {table}.jsonl.gz its rows (first
 * line the columns, then one JSON list per row, in primary key order). Loading them makes a
 * MySQL database of their own (never project_diu) that the "legacy" connection then reads, so
 * the steps run unchanged. Personal data stays out: subscriber lists and admission results are
 * structure only, the alumni's private contact columns are empty.
 */
class Seed
{
    /** The legacy tables the steps read (Data Migration Plan §5) */
    public const TABLES = [
        'accordion_categories', 'accordions', 'accreditation_categories', 'accreditations', 'admin_categories', 'admins',
        'admission_contact_types', 'admission_contacts', 'admission_facility_categories', 'admission_notices', 'admission_results',
        'admission_subscribers', 'admission_tests', 'admissions', 'alumnis', 'blog_categories', 'blog_post_blog_tag', 'blog_posts',
        'blog_tags', 'board_waiver', 'boards', 'calendar_event_calender_program', 'calendar_events', 'calender_programs', 'calenders',
        'charter_guidebook_categories', 'charter_guidebooks', 'committee_categories', 'committees', 'countdown_timers', 'countries',
        'cover_photos', 'csr_categories', 'csrs', 'department_articles', 'department_menus', 'department_program_type', 'departments',
        'discount_partner_categories', 'discount_partners', 'documentaries', 'eligibilities', 'event_categories', 'event_images', 'events',
        'facilities', 'faculties', 'faculty_waiver', 'faq_categories', 'faqs', 'featured_partners', 'flipbook_categories', 'flipbooks',
        'gallery_categories', 'gallery_folders', 'gallery_photos', 'gallery_sub_categories', 'gender_waiver', 'greetings',
        'international_conference_categories', 'international_conferences', 'international_linkages', 'lab_app_menus', 'lab_articles',
        'lab_committees', 'lab_facilities', 'lab_galleries', 'lab_menus', 'lab_mid_sections', 'lab_news', 'lab_product_categories', 'lab_products',
        'lab_project_categories', 'lab_projects', 'lab_research', 'lab_scope_categories', 'lab_section_summaries', 'lab_scopes', 'lab_tool_categories', 'lab_tools',
        'lecture_series', 'mba_sliders', 'mba_testimonials', 'mps', 'news_tickers', 'newsletters', 'notice_categories', 'notice_files',
        'notices', 'office_admins', 'offices', 'policies', 'program_career_paths', 'program_facilitators', 'program_industries',
        'program_major_categories', 'program_majors', 'program_materials', 'program_professional_memberships', 'program_sliders',
        'program_types', 'program_variants', 'program_videos', 'prospectuses', 'rankings', 'road_schedule', 'road_service_schedule',
        'road_service_stop', 'road_services', 'road_stop', 'roads', 'schedules', 'scholarship_categories', 'scholarships',
        'section_employees', 'sections', 'semesters', 'sliders', 'staff', 'staff_categories', 'stops', 'support_contacts', 'testimonials',
        'transport_services', 'transport_settings', 'tuition_categories', 'tuitions', 'tuition_waiver', 'visitors', 'waiver_categories',
        'waiver_waiver_category', 'waivers',
    ];

    /** Personal data, not content: structure only */
    public const NO_ROWS = ['admission_results', 'admission_subscribers', 'newsletters'];

    /** table => private columns exported empty */
    public const BLANK = ['alumnis' => ['email', 'phone_number', 'student_id', 'office_address']];

    /** Where the loaded seed lives: a database of its own, recreated on every load */
    public const DATABASE = 'diu_legacy_seed';

    /** Rows per INSERT stay under this many bytes (max_allowed_packet) */
    private const PACKET = 4_000_000;

    public static function path(): string
    {
        return base_path('tools/legacy-migration/seed');
    }

    /**
     * Write the seed files from the legacy database (read only).
     *
     * @param  list<string>  $tables
     * @return array<string, int> rows written per table
     */
    public function export(Connection $legacy, string $dir, array $tables = self::TABLES): array
    {
        File::ensureDirectoryExists($dir);
        File::delete(File::glob("{$dir}/*.jsonl.gz"));
        $schema = "-- project_diu tables the legacy migration reads (tools/legacy-migration/src/Support/Seed.php)\n";
        $written = [];

        foreach ($tables as $table) {
            $create = array_values((array) $legacy->selectOne("SHOW CREATE TABLE `{$table}`"))[1];
            // No foreign keys (the referenced tables, users …, are not in the seed) and no counter
            $create = preg_replace('/^\s*CONSTRAINT `[^`]+` FOREIGN KEY.*$\n?/m', '', $create);
            $create = preg_replace('/,(\s*\n\))/', '$1', $create);
            $create = preg_replace('/ AUTO_INCREMENT=\d+/', '', $create);
            $schema .= "\n{$create};\n";

            $columns = array_map(fn (object $c): string => $c->Field, $legacy->select("SHOW COLUMNS FROM `{$table}`"));
            $key = $this->primaryKey($legacy, $table) ?: $columns;
            $blank = array_flip(self::BLANK[$table] ?? []);
            $lines = [json_encode($columns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];

            if (! in_array($table, self::NO_ROWS, true)) {
                $query = $legacy->table($table);
                foreach ($key as $column) {
                    $query->orderBy($column);
                }
                foreach ($query->cursor() as $row) {
                    $values = [];
                    foreach ($columns as $column) {
                        $values[] = isset($blank[$column]) ? null : $row->{$column};
                    }
                    $lines[] = json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                }
            }

            // gzencode leaves the time out of the header: an unchanged table gives the same file
            File::put("{$dir}/{$table}.jsonl.gz", gzencode(implode("\n", $lines)."\n", 9));
            $written[$table] = count($lines) - 1;
        }

        File::put("{$dir}/schema.sql", $schema);

        return $written;
    }

    /**
     * Load the seed files into their own database and point the "legacy" connection at it.
     *
     * @return array<string, int> rows loaded per table
     */
    public function load(string $dir, string $database = self::DATABASE): array
    {
        if (! File::exists("{$dir}/schema.sql")) {
            throw new RuntimeException("No seed files in {$dir} (php artisan legacy:export-seed).");
        }
        if ($database === config('database.connections.legacy.database') || $database === config('database.connections.mysql.database')) {
            throw new RuntimeException("The seed is not loaded into {$database}: it is the legacy or the application database.");
        }

        // Written with the application's database user (the legacy one may only read)
        $c = config('database.connections.mysql');
        (new PDO("mysql:host={$c['host']};port={$c['port']}", $c['username'], $c['password']))
            ->exec("DROP DATABASE IF EXISTS `{$database}`; CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        config(['database.connections.legacy' => array_merge(config('database.connections.legacy'), [
            'host' => $c['host'], 'port' => $c['port'], 'database' => $database, 'username' => $c['username'], 'password' => $c['password'],
        ])]);
        DB::purge('legacy');
        $legacy = DB::connection('legacy');
        $legacy->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', File::get("{$dir}/schema.sql")))) as $statement) {
            $statement = trim(preg_replace('/^--.*$/m', '', $statement));
            if ($statement !== '') {
                $legacy->unprepared($statement);
            }
        }

        $loaded = [];
        foreach (File::glob("{$dir}/*.jsonl.gz") as $file) {
            $table = basename($file, '.jsonl.gz');
            $lines = explode("\n", rtrim(gzdecode(File::get($file)), "\n"));
            $columns = json_decode(array_shift($lines), true, flags: JSON_THROW_ON_ERROR);
            $batch = [];
            $bytes = 0;
            foreach ($lines as $line) {
                $batch[] = array_combine($columns, json_decode($line, true, flags: JSON_THROW_ON_ERROR));
                $bytes += strlen($line);
                if ($bytes > self::PACKET || count($batch) >= 500) {
                    $legacy->table($table)->insert($batch);
                    [$batch, $bytes] = [[], 0];
                }
            }
            if ($batch !== []) {
                $legacy->table($table)->insert($batch);
            }
            $loaded[$table] = count($lines);
        }

        return $loaded;
    }

    /** @return list<string> */
    private function primaryKey(Connection $legacy, string $table): array
    {
        return array_map(fn (object $k): string => $k->Column_name, $legacy->select("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'"));
    }
}
