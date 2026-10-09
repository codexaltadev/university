<?php

namespace Modules\Program\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * Base of the Program sub-items (DB-03 §4.2): majors, career paths / industries,
 * facilitators, materials, memberships, variants. Edited on the Program's page
 * (BRL-PROG-04); whoever may update the Program may edit them (AC-PROG-03).
 */
abstract class ProgramItem extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;

    public static string $titleAttribute = 'title';

    protected function casts(): array
    {
        return ['program_id' => 'integer'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
