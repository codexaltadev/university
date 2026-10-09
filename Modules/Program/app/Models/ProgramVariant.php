<?php

namespace Modules\Program\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Concerns\HasMediaUsage;

/**
 * Another form of the same Program (FR-PROG-09), e.g. Dual Degree or Evening: text,
 * a link to the related Program and an action button; media "image".
 */
class ProgramVariant extends ProgramItem
{
    use HasMediaUsage;

    protected $table = 'program_variants';

    protected $fillable = ['program_id', 'related_program_id', 'title', 'short_description', 'details', 'button_text', 'button_url', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'short_description', 'details', 'button_text'];

    protected function casts(): array
    {
        return [...parent::casts(), 'related_program_id' => 'integer'];
    }

    public function relatedProgram(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'related_program_id');
    }

    /** The button goes to its own link, else to the related Program when that is visible. */
    public function buttonHref(): ?string
    {
        if ($this->button_url) {
            return $this->button_url;
        }

        $related = $this->relatedProgram;

        return $related?->isFrontendVisible() ? $related->url() : null;
    }
}
