<?php

namespace Modules\Program\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Taxonomy\Term;

/** A Major / Specialization of a Program (FR-PROG-04), grouped by a program.major-category term. */
class ProgramMajor extends ProgramItem
{
    protected $table = 'program_majors';

    protected $fillable = ['program_id', 'category_term_id', 'title', 'short_description', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'short_description'];

    protected function casts(): array
    {
        return [...parent::casts(), 'category_term_id' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'category_term_id');
    }
}
