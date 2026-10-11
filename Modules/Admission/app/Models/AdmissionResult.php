<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Models\BaseModel;

/**
 * One applicant's admission test result (FR-ADM-05/06): looked up by application serial;
 * only the name and the result are ever shown (BRL-ADM-02).
 */
class AdmissionResult extends BaseModel
{
    /** Which message the applicant sees. */
    public const RESULTS = ['selected', 'not_selected', 'waiting', 'absent'];

    public static string $titleAttribute = 'application_serial';

    protected $table = 'admission_results';

    protected $fillable = ['batch_id', 'program_id', 'program_label', 'application_serial', 'student_name', 'result', 'result_label', 'is_active'];

    protected function casts(): array
    {
        return ['batch_id' => 'integer', 'program_id' => 'integer'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ResultBatch::class, 'batch_id');
    }

    /**
     * A result as written in a file ("Selected 1st Merit List", "Not Selected", "Absent") →
     * one of RESULTS, or null when it is none of them.
     */
    public static function normalize(?string $text): ?string
    {
        $text = strtolower(trim((string) $text));

        return match (true) {
            $text === '' => null,
            str_starts_with($text, 'not') => 'not_selected',
            str_starts_with($text, 'select') => 'selected',
            str_starts_with($text, 'wait') => 'waiting',
            str_starts_with($text, 'absent') => 'absent',
            in_array(str_replace([' ', '-'], '_', $text), self::RESULTS, true) => str_replace([' ', '-'], '_', $text),
            default => null,
        };
    }
}
