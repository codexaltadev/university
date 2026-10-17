<?php

namespace Modules\Transport\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A route under one service type (DB-06 v1.2 §2, FR-TRN-02/04/09): its stops in order (at least
 * two — BRL-TRN-01), the days it does not run, a message, and its departures.
 */
class Variant extends BaseModel
{
    use HasTranslations;

    public const DAYS = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public static string $titleAttribute = 'id';

    protected $table = 'transport_route_variants';

    protected $fillable = ['route_id', 'service_type_id', 'days_off', 'message', 'is_active'];

    public array $translatable = ['message'];

    protected function casts(): array
    {
        return ['days_off' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (Variant $variant): void {
            $variant->days_off = array_values(array_intersect(self::DAYS, (array) $variant->days_off)) ?: null;
            $taken = static::query()->where('route_id', $variant->route_id)->where('service_type_id', $variant->service_type_id)
                ->when($variant->exists, fn ($q) => $q->whereKeyNot($variant->getKey()))->exists();
            if ($taken) {
                throw ValidationException::withMessages(['service_type_id' => __('transport::transport.errors.variant')]);
            }
        });
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function stops(): BelongsToMany
    {
        return $this->belongsToMany(Stop::class, 'transport_variant_stops', 'variant_id', 'stop_id')
            ->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
    }

    public function departures(): HasMany
    {
        return $this->hasMany(Departure::class, 'variant_id')->orderBy('departs_at')->orderBy('sort_order');
    }

    /**
     * The stops in this order (BRL-TRN-01: at least two).
     *
     * @param  list<int>  $stopIds
     */
    public function syncStops(array $stopIds): void
    {
        $stopIds = array_values(array_unique(array_map('intval', array_filter($stopIds))));
        if (count($stopIds) < 2) {
            throw ValidationException::withMessages(['stops' => __('transport::transport.errors.stops')]);
        }
        $this->stops()->sync(collect($stopIds)->mapWithKeys(fn (int $id, int $i): array => [$id => ['sort_order' => $i + 1]])->all());
    }
}
