<?php

namespace Modules\Lab\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Modules\Lab\Database\Factories\LabFactory;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsRelationTarget;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\HasEntityPermissions;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A lab or research centre (M-32, DB-06 v1.3 §3, SRS Services FR-LAB-01..11): a microsite
 * (/lab/{slug}, or under its Department: /fsit/cse/lab/{slug}) and a relation target — its news,
 * events, gallery, slider, partners come from those modules by "Show in". In-charge = assignment
 * "in_charge"; "Manage:Lab#{id}" for its own people (FR-LAB-03). Type from lab.type.
 */
class Lab extends BaseModel implements HasEntityPermissions
{
    /** @use HasFactory<LabFactory> */
    use HasFactory;

    use HasMediaUsage;      // logo, cover
    use HasSeo;
    use HasSlug;            // microsite /lab/{slug}
    use HasSortOrder;
    use HasTaxonomy;        // lab.type
    use HasTranslations;
    use IsAssignable;
    use IsRelationTarget;
    use IsSearchable;

    public const TYPE = 'lab.type';

    public const LISTING = 'labs';

    public const DEPARTMENT = 'Modules\\Department\\Models\\Department';

    protected $table = 'lab_labs';

    protected $fillable = ['department_id', 'name', 'short_name', 'campus_id', 'location', 'summary', 'body', 'equipment_summary', 'email', 'phone', 'video_url', 'website_url', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'location', 'summary', 'body', 'equipment_summary'];

    protected string $slugSource = 'name';

    protected function casts(): array
    {
        return ['department_id' => 'integer', 'campus_id' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Lab $lab): void {
            foreach (['video_url', 'website_url'] as $field) {
                if (filled($lab->{$field}) && ! preg_match('#^https?://\S+$#i', (string) $lab->{$field})) {
                    throw ValidationException::withMessages([$field => __('lab::lab.errors.url')]);
                }
            }
        });
    }

    public static function entityPermissionSubject(): string
    {
        return 'Lab';
    }

    public static function entityPermissionActions(): array
    {
        return ['Manage'];
    }

    public function entityPermissionLabel(): string
    {
        return $this->name;
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabItem::class)->ordered();
    }

    /** The Department, when that module is installed. */
    public function department(): ?Model
    {
        return $this->department_id && class_exists(self::DEPARTMENT) ? (self::DEPARTMENT)::query()->find($this->department_id) : null;
    }

    public function scopeListed(Builder $query): Builder
    {
        return $query->frontendVisible()->ordered();
    }

    public function videoEmbed(): ?string
    {
        return preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([\w-]{11})~', (string) $this->video_url, $m)
            ? 'https://www.youtube-nocookie.com/embed/'.$m[1] : null;
    }

    public function url(?string $locale = null): ?string
    {
        return app(MicrositeResolver::class)->urlFor($this, $locale);
    }

    /** The lab itself: found by "only in this lab" too (FR-SRCH-06). */
    public function searchOwnerKey(): ?string
    {
        return $this->getMorphClass().':'.$this->getKey();
    }

    protected function searchBodyAttributes(): array
    {
        return ['summary', 'body'];
    }

    protected static function newFactory(): LabFactory
    {
        return LabFactory::new();
    }
}
