<?php

namespace Modules\Leadership\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Leadership\Models\LeadershipTerm;
use Modules\Leadership\Models\Position;
use Platform\Assignments\AssignmentEngine;

/**
 * Appointing someone to a position (FR-LEAD-01/07, AC-LEAD-01): the core assignment (person,
 * dates, public, order) and the term (message, biography) in one go. For a single-holder
 * position the current holder's term can end the day before the new one starts — they move to
 * "Former" and the homepage shows the new holder's message.
 */
class Appointments
{
    public function __construct(protected AssignmentEngine $engine) {}

    /**
     * @param  array{starts_on?: ?string, ends_on?: ?string, is_public?: bool, sort_order?: int, message?: array, biography?: array, is_active?: bool}  $data
     */
    public function appoint(Position $position, Model $person, array $data = [], bool $endPrevious = true): LeadershipTerm
    {
        return DB::transaction(function () use ($position, $person, $data, $endPrevious): LeadershipTerm {
            ['assignment' => $assignment] = $this->engine->assign($person, $position, $position->roleKey(), array_filter([
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'is_public' => $data['is_public'] ?? true,
                'sort_order' => $data['sort_order'] ?? null,
            ], fn ($v) => $v !== null), endPrevious: $endPrevious && $position->is_single_holder);

            return LeadershipTerm::query()->create([
                'position_id' => $position->id,
                'assignment_id' => $assignment->id,
                'message' => $data['message'] ?? null,
                'biography' => $data['biography'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /** Edit a term: the person, dates and public flag on the assignment, the texts on the term. */
    public function update(LeadershipTerm $term, ?Model $person, array $data): LeadershipTerm
    {
        return DB::transaction(function () use ($term, $person, $data): LeadershipTerm {
            $term->assignment->update(array_filter([
                'person_type' => $person?->getMorphClass(),
                'person_id' => $person?->getKey(),
                'is_public' => $data['is_public'] ?? null,
            ], fn ($v) => $v !== null) + [
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
            ]);
            $term->update(array_intersect_key($data, array_flip(['message', 'biography', 'is_active'])));

            return $term;
        });
    }

    /** Current holders of a single-holder position — the form asks whether to end their term. */
    public function currentHolders(Position $position): array
    {
        return $position->is_single_holder
            ? $position->terms()->current()->get()->map(fn (LeadershipTerm $t): string => $t->name())->filter()->values()->all()
            : [];
    }
}
