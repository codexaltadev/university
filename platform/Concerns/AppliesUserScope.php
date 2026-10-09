<?php

namespace Platform\Concerns;

use Platform\Scope\UserScopeFilter;

/**
 * Admin lists show only records inside the user's scope (FR-GEN-52).
 * The path is chosen automatically: IsRelationTarget → "self",
 * IsAttachable → "relation", or declare columns:
 *
 *     public array $scopeColumns = ['department_id' => 'department.department'];
 */
trait AppliesUserScope
{
    public static function bootAppliesUserScope(): void
    {
        static::addGlobalScope(new UserScopeFilter);
    }
}
