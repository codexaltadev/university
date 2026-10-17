<?php

namespace Modules\Lab\Metrics;

/** "lab.projects": a lab's active student projects (FR-LAB-11). */
class LabProjectCount extends LabPartCount
{
    protected function type(): string
    {
        return 'project';
    }
}
