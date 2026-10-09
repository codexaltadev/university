<?php

namespace Platform\Contracts;

/**
 * A person directory (Faculty Member, Administration, Alumni, manual persons).
 * Register with $r->personSource(Model::class) (FR-REL-09).
 */
interface PersonSource
{
    public function personPresenter(): PersonPresenter;
}
