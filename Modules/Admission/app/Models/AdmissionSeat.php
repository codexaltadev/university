<?php

namespace Modules\Admission\Models;

use Platform\Models\BaseModel;

/**
 * One applicant's seat at the admission test (FR-ADM-17, DIU's /seat-plan): looked up by application serial; the
 * name, program, building, room and floor are shown, nothing else. The whole list is replaced by each upload.
 */
class AdmissionSeat extends BaseModel
{
    public static string $titleAttribute = 'application_serial';

    protected $table = 'admission_seats';

    protected $fillable = ['application_serial', 'student_name', 'program_id', 'program_label', 'building', 'room', 'floor', 'is_active'];

    protected function casts(): array
    {
        return ['program_id' => 'integer'];
    }
}
