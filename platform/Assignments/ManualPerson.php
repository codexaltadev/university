<?php

namespace Platform\Assignments;

use Platform\Concerns\IsPersonSource;
use Platform\Contracts\PersonPresenter;
use Platform\Contracts\PersonSource;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A person who is in no directory (core_manual_persons, ADR-14) — the kernel's
 * built-in person source, so every assignment always has a person_type / person_id.
 */
class ManualPerson extends BaseModel implements PersonSource
{
    use HasTranslations;
    use IsPersonSource;

    protected $table = 'core_manual_persons';

    protected $fillable = ['name', 'designation', 'organization', 'email', 'phone', 'profile_url', 'is_active'];

    public array $translatable = ['name', 'designation', 'organization'];

    public function personPresenter(): PersonPresenter
    {
        return new ManualPersonPresenter($this);
    }
}
