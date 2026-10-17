<?php

namespace Modules\Lab\Metrics;

/** "lab.equipment": a lab's active equipment (by quantity) (FR-LAB-11). */
class LabEquipmentCount extends LabPartCount
{
    protected function type(): string
    {
        return 'equipment';
    }
}
