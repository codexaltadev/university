<?php

namespace Modules\Program\Models;

use Platform\Concerns\HasMediaUsage;

/** A Learning Material of a Program (FR-PROG-07): a link or a file (media "file"). */
class ProgramMaterial extends ProgramItem
{
    use HasMediaUsage;

    protected $table = 'program_materials';

    protected $fillable = ['program_id', 'title', 'url', 'sort_order', 'is_active'];

    public array $translatable = ['title'];

    /** The file wins over the link. */
    public function href(): ?string
    {
        return $this->firstMedia('file')?->url() ?? $this->url;
    }
}
