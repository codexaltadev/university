<?php

namespace Modules\Testimonials\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\Testimonials\Models\Testimonial;
use Platform\Assignments\Assignment;
use Platform\Assignments\AssignmentEngine;

/**
 * The one person of a testimonial (FR-TEST-01): an assignment with role "author" and its own
 * title per language ("Parent of a CSE student"). Changing the person moves the assignment.
 */
class Authors
{
    public function __construct(protected AssignmentEngine $engine) {}

    /** @param  array<string, string>|null  $title */
    public function set(Testimonial $testimonial, ?Model $person, ?array $title = null): ?Assignment
    {
        $title = array_filter((array) $title) ?: null;
        $current = $testimonial->author();

        if ($person === null) {
            $current?->delete();

            return null;
        }

        if ($current === null) {
            return $this->engine->assign($person, $testimonial, Testimonial::ROLE, ['role_label' => $title, 'is_public' => true])['assignment'];
        }

        $current->update(['person_type' => $person->getMorphClass(), 'person_id' => $person->getKey(), 'role_label' => $title]);

        return $current;
    }
}
