<?php

namespace Modules\Office\Metrics;

/** "office.team": the people shown in an office's team (FR-OFF-14). */
class OfficeTeamCount extends OfficePartCount
{
    protected function part(): string
    {
        return 'team';
    }
}
