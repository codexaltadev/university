<?php

namespace Modules\Transport\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Platform\Models\BaseModel;

/** One bus leaving (DB-06 v1.2 §2, FR-TRN-04): to or from campus, at a time, regular or shuttle, not on some days. */
class Departure extends BaseModel
{
    public const DIRECTIONS = ['to_campus', 'from_campus'];

    public const TRIP_TYPES = ['regular', 'shuttle'];

    public static string $titleAttribute = 'departs_at';

    protected $table = 'transport_departures';

    protected $fillable = ['variant_id', 'direction', 'departs_at', 'trip_type', 'days_off', 'stop_times', 'sort_order', 'is_active'];

    protected $attributes = ['trip_type' => 'regular'];

    protected function casts(): array
    {
        return ['days_off' => 'array', 'stop_times' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (Departure $departure): void {
            $errors = [];
            if (! in_array($departure->direction, self::DIRECTIONS, true)) {
                $errors['direction'] = __('transport::transport.errors.direction');
            }
            if (! in_array($departure->trip_type, self::TRIP_TYPES, true)) {
                $errors['trip_type'] = __('transport::transport.errors.direction');
            }
            if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $departure->departs_at)) {
                $errors['departs_at'] = __('transport::transport.errors.time');
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
            $departure->departs_at = substr((string) $departure->departs_at, 0, 5).':00';
            $departure->days_off = array_values(array_intersect(Variant::DAYS, (array) $departure->days_off)) ?: null;
        });
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }

    public function time(): string
    {
        return substr((string) $this->departs_at, 0, 5);
    }
}
