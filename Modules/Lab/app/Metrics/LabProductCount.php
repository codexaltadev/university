<?php

namespace Modules\Lab\Metrics;

/** "lab.products": a lab's active products (FR-LAB-11). */
class LabProductCount extends LabPartCount
{
    protected function type(): string
    {
        return 'product';
    }
}
