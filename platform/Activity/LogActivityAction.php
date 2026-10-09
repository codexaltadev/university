<?php

namespace Platform\Activity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Platform\Modules\ModuleRegistry;
use Spatie\Activitylog\Actions\LogActivityAction as SpatieLogActivityAction;

/**
 * Adds module_slug, ip_address and user_agent to every activity (BR-LOG-04).
 */
class LogActivityAction extends SpatieLogActivityAction
{
    protected function beforeActivityLogged(Model $activity): void
    {
        parent::beforeActivityLogged($activity);

        $activity->module_slug ??= $activity->subject_type
            ? app(ModuleRegistry::class)->slugForClass($this->subjectClass($activity))
            : null;

        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            $request = request();
            $activity->ip_address ??= $request->ip();
            $activity->user_agent ??= Str::limit((string) $request->userAgent(), 509);
        }
    }

    protected function subjectClass(Model $activity): string
    {
        $type = $activity->subject_type;

        return Model::getActualClassNameForMorph($type);
    }
}
