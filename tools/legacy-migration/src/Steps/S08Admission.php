<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Admission\Models\AdmissionContactGroup;
use Modules\Admission\Models\AdmissionHighlight;
use Modules\Admission\Models\AdmissionResult;
use Modules\Admission\Models\AdmissionSubscriber;
use Modules\Admission\Models\Circular;
use Modules\Admission\Models\ResultBatch;
use Modules\Admission\Support\ResultImporter;
use Platform\Assignments\Assignment;
use Platform\Assignments\RoleCatalog;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S08 Admission (DB-03 v1.5 §10, §15): circulars, information cards and their categories,
 * contact groups and their people (from `admins`, as manual people until the Administration
 * module), the result batch with its messages and results, subscribers. Results and
 * subscribers go in bulk (thousands of rows); a second run replaces / skips, never doubles.
 * Pictures wait for S03 (media import).
 */
class S08Admission extends Step
{
    /** Old DIU addresses in the cards → the new ones. */
    public const LINKS = [
        '/tuition-fees' => '/tuition-fees?category=local',
        '/int-tuition-fees' => '/tuition-fees?category=international',
        '/scholarship' => '/scholarships',
        '/tuition-fee-calculator' => '/waiver-calculator',
        '/admission-contact' => '/admission#contacts',
    ];

    public static function key(): string
    {
        return 'S08';
    }

    public static function title(): string
    {
        return 'Admission: circulars, information, contacts, results, subscribers';
    }

    public static function requires(): array
    {
        return ['admission'];
    }

    protected function handle(): void
    {
        // DIU's own addresses keep working (Site › Redirects), except where only the query differs
        foreach (self::LINKS as $old => $new) {
            if (parse_url($new, PHP_URL_PATH) !== $old) {
                $this->oldAddress($old, $new);
            }
        }

        if (! Schema::connection('legacy')->hasTable('admission_notices')) {
            $this->report->review('No admission tables in the legacy database: nothing to migrate.');

            return;
        }

        $this->circulars();
        $this->highlights();
        $this->contacts();
        $this->results();
        $this->subscribers();
    }

    protected function circulars(): void
    {
        foreach ($this->rows('admission_notices') as $row) {
            $title = Normalizer::text($row->notice_title);

            $this->upsert('admission_notices', $row, Circular::class, [
                'title' => Normalizer::en($title),
                'session_label' => mb_substr(trim((string) preg_replace('/\(.*\)/', '', (string) $title)), 0, 50) ?: null,   // "Fall 2026 (Tri Semester)" → "Fall 2026"
                'content' => Normalizer::en(Normalizer::html($row->notice)),
                'apply_ends_on' => $row->apply_date,
                'test_on' => $row->admission_test,
                'admission_on' => $row->admission_date,
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], existing: $this->same(Circular::class, ['title->en' => $title]));
        }
    }

    protected function highlights(): void
    {
        $terms = [];
        foreach ($this->rows('admission_facility_categories') as $row) {
            $name = Normalizer::text($row->title);
            Vocabularies::ensureTerms(AdmissionHighlight::CATEGORY, [['name' => ['en' => $name], 'slug' => $row->slug]]);
            $terms[$row->id] = Term::query()->where('vocabulary_id', app(Vocabularies::class)->find(AdmissionHighlight::CATEGORY)->id)
                ->get()->first(fn (Term $t): bool => $t->getTranslation('name', 'en', false) === $name)?->id;
        }

        $pictures = 0;
        foreach ($this->rows('admissions', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $title = Normalizer::text($row->title);
            $term = $terms[$row->admission_facility_category_id] ?? null;
            $pictures += filled($row->image) ? 1 : 0;

            $this->upsert('admissions', $row, AdmissionHighlight::class, [
                'title' => Normalizer::en($title),
                'url' => $this->link($row->link),
                'open_in_new_tab' => $row->target === '_blank',
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], after: fn (AdmissionHighlight $h) => $h->syncTerms(AdmissionHighlight::CATEGORY, array_filter([$term])),
                existing: $this->same(AdmissionHighlight::class, ['title->en' => $title]));
        }

        $this->report->review("{$pictures} admission information pictures wait for S03 (media import).");
    }

    protected function contacts(): void
    {
        app(RoleCatalog::class)->sync();   // "contact" for admission contact groups

        foreach ($this->rows('admission_contact_types') as $row) {
            $name = Normalizer::text($row->title);
            $this->upsert('admission_contact_types', $row, AdmissionContactGroup::class, [
                'name' => Normalizer::en($name),
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], existing: $this->same(AdmissionContactGroup::class, ['name->en' => $name]));
        }

        $photos = 0;
        foreach ($this->rows('admission_contacts') as $row) {
            $group = $this->ids->record('admission_contact_types', $row->admission_contact_type_id, AdmissionContactGroup::class);
            // One manual person per legacy admin (the same officer may be in both groups); S11 links them to Administration later
            $person = $this->adminPerson($row->admin_id) ?? $this->rowPerson('admission_contacts', $row);
            if ($group === null || $person === null) {
                $this->report->skipped('admission_contacts', 'no contact group, or no person');

                continue;
            }
            $photos += filled($row->photo) || filled($this->admins?->get((int) $row->admin_id)?->photo) ? 1 : 0;

            $this->upsert('admission_contacts', $row, Assignment::class, [
                'person_type' => $person->getMorphClass(),
                'person_id' => $person->getKey(),
                'assignable_type' => $group->getMorphClass(),
                'assignable_id' => $group->getKey(),
                'role_key' => AdmissionContactGroup::ROLE,
                'sort_order' => max(0, (int) $row->serial),
                'is_public' => (bool) $row->publication_status,
                'overrides' => filled($row->extension) ? ['extension' => (string) $row->extension] : null,
                'is_active' => true,
            ]);
        }

        $this->report->review("{$photos} admission contact photos wait for S03 (media import); the people are manual until the Administration module (S11).");
    }

    protected function results(): void
    {
        $tests = $this->rows('admission_tests');
        $test = $tests->first();
        if ($test === null) {
            return;
        }

        $text = fn (?string $message): ?array => filled($message) ? ['en' => str_replace('student_name', '{name}', trim((string) $message))] : null;
        $title = Normalizer::text($test->title);

        $batch = $this->upsert('admission_tests', $test, ResultBatch::class, [
            'name' => Normalizer::en($title),
            'session_label' => mb_substr((string) $title, 0, 50),
            'messages' => array_filter([
                'selected' => $text($test->select_mgs),
                'not_selected' => $text($test->not_select_mgs),
                'waiting' => $text($test->waiting_mgs),
                'absent' => $text($test->absent_mgs),
                'not_found' => $text($test->not_found),
            ]),
            'is_active' => (bool) $test->box_show,
        ], existing: $this->same(ResultBatch::class, ['name->en' => $title]));
        if ($tests->count() > 1) {
            $this->report->review('More than one admission_tests row: only the first became a batch; the results are all in it.');
        }

        $importer = app(ResultImporter::class);
        $programs = $importer->programs();
        $rows = $unknown = [];
        foreach ($this->rows('admission_results') as $row) {
            $result = AdmissionResult::normalize($row->result);
            $label = Normalizer::text($row->program);
            if ($result === null || blank($row->application_serial)) {
                $this->report->skipped('admission_results', 'no serial, or a result that is not selected / not selected / waiting / absent');

                continue;
            }

            $programId = $programs[ResultImporter::programKey((string) $label)] ?? null;
            if ($programId === null && $label) {
                $unknown[$label] = true;
            }
            $rows[mb_strtolower(trim($row->application_serial))] = [   // a serial once per batch: the later row wins
                'program_label' => $label ? mb_substr($label, 0, 188) : null,
                'program_id' => $programId,
                'application_serial' => mb_substr(trim($row->application_serial), 0, 50),
                'student_name' => mb_substr((string) Normalizer::text($row->student_name), 0, 191),
                'result' => $result,
                'result_label' => mb_substr((string) Normalizer::text($row->result), 0, 100),
            ];
        }

        $saved = $importer->save($batch, array_values($rows));   // replaces the batch's results: a second run does not double them
        $this->report->review("{$saved} admission results in the batch \"{$title}\".");
        if ($unknown !== []) {
            $this->report->review('Program names in the results without a matching program (kept as text): '.implode('; ', array_keys($unknown)).'.');
        }
    }

    protected function subscribers(): void
    {
        $now = now();
        $rows = [];
        foreach ($this->rows('admission_subscribers') as $row) {
            $channel = $row->contact_type === 'whatsapp' ? 'whatsapp' : 'email';
            $raw = $channel === 'whatsapp' ? ($row->whatsapp ?: $row->email) : ($row->email ?: $row->whatsapp);
            if (blank($raw)) {
                $this->report->skipped('admission_subscribers', 'no email or number');

                continue;
            }

            $contact = mb_substr(AdmissionSubscriber::normalizeContact($channel, (string) $raw), 0, 191);
            $topic = mb_substr(filled($row->reason) ? trim((string) $row->reason) : 'admission', 0, 50);
            $rows["{$channel}|{$contact}|".mb_strtolower($topic)] ??= [
                'channel' => $channel,
                'contact' => $contact,
                'topic' => $topic,
                'source' => $row->is_footer ? 'footer' : 'admission',
                'verified_at' => $row->verified_at,
                'unsubscribed_at' => $row->is_active ? null : ($row->updated_at ?? $now),
                'is_active' => true,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $now,
            ];
        }

        $before = AdmissionSubscriber::query()->count();
        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table('admission_subscribers')->insertOrIgnore($chunk);   // the unique (channel, contact, topic) key: a second run adds nothing
        }

        $this->report->review(sprintf('%d admission subscribers (%d new); the same contact and topic twice became one.', count($rows), AdmissionSubscriber::query()->count() - $before));
    }

    /** An old DIU address → a path on this website (known pages mapped); other sites as they are; "#" → none. */
    protected function link(?string $link): ?string
    {
        $link = trim((string) $link);
        if ($link === '' || $link === '#') {
            return null;
        }

        $path = preg_replace('#^https?://(www\.)?daffodilvarsity\.edu\.bd#i', '', $link);
        if ($path === $link) {
            return Normalizer::url($link) ?? $link;   // another site
        }

        $path = '/'.ltrim((string) $path, '/');
        if (! isset(self::LINKS[$path])) {
            $this->report->review("Admission card link {$path} kept as it is — check it once that page exists here.");
        }

        return self::LINKS[$path] ?? $path;
    }
}
