<?php

namespace Modules\Program\Models;

/** A Career Path or an Industry of a Program (FR-PROG-05): same shape, one table. */
class ProgramListItem extends ProgramItem
{
    public const CAREER_PATH = 'career_path';

    public const INDUSTRY = 'industry';

    public const TYPES = [self::CAREER_PATH, self::INDUSTRY];

    protected $table = 'program_list_items';

    protected $fillable = ['program_id', 'list_type', 'title', 'sort_order', 'is_active'];

    public array $translatable = ['title'];
}
