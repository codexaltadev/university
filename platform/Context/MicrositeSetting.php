<?php

namespace Platform\Context;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Template and fallback modes of one microsite (core_microsite_settings, P-09, DB-01 §10).
 * Configuration table: no soft delete. The main site uses core_settings instead.
 */
class MicrositeSetting extends Model
{
    public const MODES = ['own', 'inherit', 'main'];

    protected $table = 'core_microsite_settings';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['fallback_modes' => 'array', 'options' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $setting): void {
            $setting->updated_by = Auth::id() ?? $setting->updated_by;

            foreach ((array) $setting->fallback_modes as $resource => $mode) {
                if (! in_array($mode, self::MODES, true)) {
                    throw new \InvalidArgumentException("Fallback mode for {$resource} must be own, inherit or main.");
                }
            }
        });
    }

    /** own / inherit / main for one resource; inherit when not set (SAD Part B §6.2). */
    public function modeFor(string $resource): string
    {
        return $this->fallback_modes[$resource] ?? 'inherit';
    }
}
