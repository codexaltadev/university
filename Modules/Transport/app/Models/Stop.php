<?php

namespace Modules\Transport\Models;

use Illuminate\Validation\ValidationException;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** A bus stop (DB-06 v1.2 §2, FR-TRN-01): name and landmark per language, where it is on the map. */
class Stop extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;

    public static string $titleAttribute = 'name';

    protected $table = 'transport_stops';

    protected $fillable = ['name', 'latitude', 'longitude', 'landmark', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'landmark'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    protected static function booted(): void
    {
        static::saving(function (Stop $stop): void {
            if (($stop->latitude !== null && abs((float) $stop->latitude) > 90) || ($stop->longitude !== null && abs((float) $stop->longitude) > 180)) {
                throw ValidationException::withMessages(['latitude' => __('transport::transport.errors.position')]);
            }
        });
    }

    /** Google Maps address of the pin, when the place is known. */
    public function mapUrl(): ?string
    {
        return $this->latitude !== null && $this->longitude !== null
            ? 'https://www.google.com/maps/search/?api=1&query='.$this->latitude.','.$this->longitude
            : null;
    }
}
