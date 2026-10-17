<?php

namespace Modules\FacultyMember\Metrics;

use Modules\FacultyMember\Models\FacultyMember;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

/** "faculty-member.count": the visible teachers of a place — a department's (primary or also there), a faculty's departments', or all. */
class FacultyMemberCount implements MetricProvider
{
    public function key(): string
    {
        return 'faculty-member.count';
    }

    public function label(): string
    {
        return __('facultymember::facultymember.plural');
    }

    public function count(StatContext $ctx, array $params): int
    {
        $departments = $ctx->idsOf('department.department');

        return FacultyMember::query()->frontendVisible()->when($departments !== null, fn ($q) => $q->inDepartments($departments))->count();
    }
}
