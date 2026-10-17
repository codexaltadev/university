<?php

namespace Modules\Transport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Modules\Transport\Database\Factories\RouteFactory;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A bus route (DB-06 v1.2 §2, FR-TRN-02): name, group on the website (Regular / Shuttle /
 * Friday), map, message; its stops and times are per service type (variants).
 */
class Route extends BaseModel
{
    /** @use HasFactory<RouteFactory> */
    use HasFactory;

    use HasSortOrder;
    use HasTranslations;

    public const GROUPS = ['regular', 'shuttle', 'friday'];

    public static string $titleAttribute = 'name';

    protected $table = 'transport_routes';

    protected $fillable = ['name', 'code', 'route_group', 'campus_id', 'map_url', 'message', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'message'];

    protected $attributes = ['route_group' => 'regular'];

    protected static function booted(): void
    {
        static::saving(function (Route $route): void {
            if (! in_array($route->route_group, self::GROUPS, true)) {
                throw ValidationException::withMessages(['route_group' => __('transport::transport.errors.group')]);
            }
            if (filled($route->map_url) && ! preg_match('#^https://\S+$#i', (string) $route->map_url)) {
                throw ValidationException::withMessages(['map_url' => __('transport::transport.errors.map')]);
            }
            $route->code = filled($route->code) ? trim((string) $route->code) : null;
        });
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class, 'route_id');
    }

    /** The variant shown now: the running service's, else the default service's (DB-06 v1.2 §2). */
    public function variantFor(?ServiceType $running, ?ServiceType $default): ?Variant
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
        $active = $variants->where('is_active', true);

        return ($running ? $active->firstWhere('service_type_id', $running->id) : null)
            ?? ($default ? $active->firstWhere('service_type_id', $default->id) : null);
    }

    protected static function newFactory(): RouteFactory
    {
        return RouteFactory::new();
    }
}
