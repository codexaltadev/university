<?php

namespace Modules\Program\Models;

use Platform\Concerns\HasMediaUsage;

/** A Facilitator / Industry Expert of a Program (FR-PROG-06); media "photo". */
class ProgramFacilitator extends ProgramItem
{
    use HasMediaUsage;

    public const TYPES = ['industry_expert', 'facilitator', 'advisor', 'guest_faculty'];

    public static string $titleAttribute = 'name';

    protected $table = 'program_facilitators';

    protected $fillable = ['program_id', 'name', 'designation', 'organization', 'type', 'profile_url', 'linkedin_url', 'email', 'phone', 'sort_order', 'is_active'];

    public array $translatable = ['designation', 'organization'];
}
