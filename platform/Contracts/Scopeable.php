<?php

namespace Platform\Contracts;

/**
 * A record that belongs to one or more entities (Faculty, Department, Office ...).
 * The Scope engine compares these keys with the user's scope.
 */
interface Scopeable
{
    /** @return list<string> entity keys like "department:12" */
    public function scopeKeys(): array;
}
