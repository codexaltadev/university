<?php

namespace Modules\Transport\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A transport service (DB-06 v1.2 §2, FR-TRN-03): Regular, Final Exam, Mid-Term, Semester Break …
 * One is the default (a route without a variant for the running service uses the default one's);
 * which one runs now is Settings › Transport.
 */
class ServiceType extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;

    public static string $titleAttribute = 'name';

    protected $table = 'transport_service_types';

    protected $fillable = ['name', 'code', 'is_default', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (ServiceType $type): void {
            $type->code = strtolower(trim((string) preg_replace('/[^a-z0-9_]+/i', '_', (string) $type->code), '_'));
            if ($type->code === '') {
                throw ValidationException::withMessages(['code' => __('transport::transport.errors.code')]);
            }
        });
        static::saved(function (ServiceType $type): void {
            if ($type->is_default) {   // only one default
                static::query()->whereKeyNot($type->getKey())->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class, 'service_type_id');
    }

    public static function default(): ?self
    {
        return static::query()->where('is_active', true)->where('is_default', true)->first()
            ?? static::query()->where('is_active', true)->ordered()->first();
    }

    /** The service running now (Settings › Transport), else the default. */
    public static function running(): ?self
    {
        $id = settings('transport.running_service_id');

        return ($id ? static::query()->where('is_active', true)->find($id) : null) ?? static::default();
    }
}
