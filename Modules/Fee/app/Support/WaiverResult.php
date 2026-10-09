<?php

namespace Modules\Fee\Support;

use Modules\Fee\Models\Tuition;
use Modules\Fee\Models\WaiverRule;

/**
 * What the Waiver Calculator answers (FR-FEE-06): a waiver with the costs before and
 * after it, or why there is none. Ranges (min / max) where the program's credits vary.
 */
final class WaiverResult
{
    public const OK = 'ok';

    public const NO_WAIVER = 'no_waiver';           // AC-FEE-02

    public const NO_TUITION = 'no_tuition';         // the tuition has no tuition amount (DIU: "only for local students")

    /**
     * @param  array{total: float, tuition: float, other: ?float, admission: ?float, waiver: float, final: float}|null  $min
     * @param  array{total: float, tuition: float, other: float, waiver: float, final: float}|null  $max
     * @param  list<WaiverRule>  $rules  every rule that matched, best first
     */
    public function __construct(
        public readonly string $status,
        public readonly ?Tuition $tuition = null,
        public readonly ?WaiverRule $rule = null,
        public readonly float $percent = 0.0,
        public readonly ?string $categoryName = null,
        public readonly ?array $min = null,
        public readonly ?array $max = null,
        public readonly array $rules = [],
    ) {}

    public function ok(): bool
    {
        return $this->status === self::OK;
    }

    public function hasRange(): bool
    {
        return $this->max !== null;
    }
}
