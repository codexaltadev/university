<?php

namespace Legacy\Migration\Steps;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Alumni\Models\Alumnus;
use Modules\Department\Models\Department;

/**
 * S11 (alumni part, DB-04 v1.5 §8, §11.1): `alumnis` → alumni, in their department (S04), with
 * DIU's address, order, Pride flag and story. Contacts come in hidden (FR-ALUM-06). A student
 * ID used twice is kept for the first only. Uploaded videos and photos wait for the files (S03);
 * "is faculty" waits for the Faculty Member module; DIU's "show on home" has no counterpart
 * (Pride decides the homepage section). Run after S04.
 */
class S11Alumni extends Step
{
    public const JOB_TYPES = ['service' => 'job', 'service holder' => 'job', 'job' => 'job', 'entrepreneur' => 'business', 'business' => 'business'];

    public static function key(): string
    {
        return 'S11A';
    }

    public static function title(): string
    {
        return 'Alumni';
    }

    public static function requires(): array
    {
        return ['alumni'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('alumnis')) {
            $this->report->review('No alumnis table in the legacy database: nothing to migrate.');

            return;
        }

        $seen = [];
        $counts = ['files' => 0, 'faculty' => 0, 'home' => 0, 'no_department' => 0];
        foreach ($this->rows('alumnis', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            $name = Normalizer::text($row->name);
            if (blank($name)) {
                $this->report->skipped('alumnis', 'no name');

                continue;
            }

            $studentId = mb_substr((string) Normalizer::text($row->student_id), 0, 30) ?: null;
            $mine = $this->ids->record('alumnis', $row->id, Alumnus::class);
            if ($studentId !== null && (isset($seen[$studentId]) || Alumnus::query()->withoutGlobalScopes()->whereNull('deleted_at')
                ->where('student_id', $studentId)->whereKeyNot($mine?->getKey())->exists())) {
                $this->report->review("alumnis #{$row->id} {$name}: student ID {$studentId} is someone else's too — left empty.");
                $studentId = null;
            }
            if ($studentId !== null) {
                $seen[$studentId] = true;
            }

            $department = $row->department_id ? $this->ids->record('departments', $row->department_id, Department::class) : null;
            $counts['no_department'] += $row->department_id && ! $department ? 1 : 0;
            $counts['files'] += ($row->video_type === 'local' ? 1 : 0) + (filled($row->photo) ? 1 : 0);
            $counts['faculty'] += (int) $row->is_faculty;
            $counts['home'] += (string) $row->is_home === '1' ? 1 : 0;
            $year = preg_match('/^(19|20)\d{2}$/', trim((string) $row->passing_year)) ? (int) $row->passing_year : null;
            $video = in_array($row->video_type, ['youtube', 'gdrive'], true) ? Normalizer::url($row->video_url) : null;

            $this->upsert('alumnis', $row, Alumnus::class, [
                'name' => Normalizer::en($name),
                'student_id' => $studentId,
                'department_id' => $department?->getKey(),
                'batch' => mb_substr((string) Normalizer::text($row->batch), 0, 30) ?: null,
                'passing_year' => $year,
                'current_designation' => Normalizer::en(Normalizer::text($row->designation)),
                'organization' => Normalizer::en(Normalizer::text($row->organization_name)),
                'job_type' => self::JOB_TYPES[Str::lower((string) Normalizer::text($row->job_type))] ?? (str_contains(Str::lower((string) $row->job_type), 'study') ? 'higher_study' : null),
                'office_address' => Normalizer::en(Normalizer::text(strip_tags((string) $row->office_address)) ?: null),
                'email' => filter_var(Normalizer::text($row->email), FILTER_VALIDATE_EMAIL) ?: null,
                'phone' => mb_substr((string) Normalizer::text($row->phone_number), 0, 50) ?: null,
                'social_links' => array_filter(['facebook' => Normalizer::url($row->facebook), 'x' => Normalizer::url($row->twitter), 'linkedin' => Normalizer::url($row->linkedin)]) ?: null,
                'video_url' => $video ? mb_substr($video, 0, 2048) : null,
                'story' => Normalizer::en(Normalizer::html($row->detail)),
                'is_pride' => (bool) $row->pride_alumni,
                'submission_source' => 'admin',
                'sort_order' => max(0, (int) $row->serial),
                'is_active' => (bool) $row->publication_status,
            ], after: fn (Alumnus $alumnus, bool $created) => $this->keepSlug($alumnus, Str::slug((string) $row->name_slug), $created));
        }

        $this->report->review("{$counts['files']} photos / uploaded videos wait for the files (S03).");
        $this->report->review("{$counts['faculty']} alumni are marked as teachers here: link them when the Faculty Member module comes (FR-ALUM-08).");
        $this->report->review("{$counts['home']} were on DIU's homepage: the new homepage section shows Pride alumni — mark them Pride if wanted.");
        if ($counts['no_department'] > 0) {
            $this->report->review("{$counts['no_department']} alumni belonged to a department that is not migrated (run S04 first).");
        }
    }
}
