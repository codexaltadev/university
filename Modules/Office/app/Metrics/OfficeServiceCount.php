<?php

namespace Modules\Office\Metrics;

/** "office.services": an office's active services (FR-OFF-14). */
class OfficeServiceCount extends OfficePartCount
{
    protected function part(): string
    {
        return 'services';
    }
}
