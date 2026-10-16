<?php

namespace Modules\Gallery\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\Gallery\Models\Video;
use Platform\Assignments\AssignmentEngine;

/** A video's one speaker (FR-GAL-04): an assignment with role "speaker"; changing the person moves it. */
class Speakers
{
    public function __construct(protected AssignmentEngine $engine) {}

    public function set(Video $video, ?Model $person): void
    {
        $current = $video->assignments()->where('role_key', Video::ROLE)->first();

        if ($person === null) {
            $current?->delete();

            return;
        }
        if ($current === null) {
            $this->engine->assign($person, $video, Video::ROLE, ['is_public' => true]);

            return;
        }
        $current->update(['person_type' => $person->getMorphClass(), 'person_id' => $person->getKey()]);
    }
}
