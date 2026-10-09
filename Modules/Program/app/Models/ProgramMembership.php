<?php

namespace Modules\Program\Models;

use Platform\Concerns\HasMediaUsage;

/** A Professional Membership / Accreditation of a Program (FR-PROG-08); media "logo". */
class ProgramMembership extends ProgramItem
{
    use HasMediaUsage;

    protected $table = 'program_memberships';

    protected $fillable = ['program_id', 'title', 'short_description', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'short_description'];
}
