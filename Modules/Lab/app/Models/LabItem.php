<?php

namespace Modules\Lab\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Modules\Lab\Database\Factories\LabItemFactory;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A lab's equipment, scope / service, student project or product (DB-06 v1.3 §3.2, FR-LAB-04/10):
 * category from lab.{type}-category (shared by all labs), picture from the Media Library; a
 * project or product has its own page /lab/{slug}/projects/{item}.
 */
class LabItem extends BaseModel
{
    /** @use HasFactory<LabItemFactory> */
    use HasFactory;

    use HasMediaUsage;      // image
    use HasSeo;
    use HasSlug;
    use HasSortOrder;
    use HasTaxonomy;        // lab.{type}-category
    use HasTranslations;

    public const TYPES = ['equipment', 'scope', 'project', 'product'];

    /** URL segment of each type on the lab's microsite */
    public const SEGMENTS = ['equipment' => 'equipment', 'scope' => 'scopes', 'project' => 'projects', 'product' => 'products'];

    /** Types with their own details page (FR-LAB-10) */
    public const WITH_PAGE = ['project', 'product'];

    protected $table = 'lab_items';

    protected $fillable = ['lab_id', 'item_type', 'title', 'description', 'quantity', 'student_names', 'student_ids', 'options', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'description', 'student_names'];

    protected string $slugSource = 'title';

    protected function casts(): array
    {
        return ['lab_id' => 'integer', 'quantity' => 'integer', 'options' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (LabItem $item): void {
            if (! in_array($item->item_type, self::TYPES, true)) {
                throw ValidationException::withMessages(['item_type' => __('lab::lab.errors.item_type')]);
            }
        });
    }

    public static function vocabulary(string $type): string
    {
        return "lab.{$type}-category";
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where($this->qualifyColumn('item_type'), $type);
    }

    public function hasPage(): bool
    {
        return in_array($this->item_type, self::WITH_PAGE, true);
    }

    public function url(?string $locale = null): ?string
    {
        $base = $this->hasPage() ? $this->lab?->url($locale) : null;

        return $base ? rtrim($base, '/').'/'.self::SEGMENTS[$this->item_type].'/'.$this->slug($locale) : null;
    }

    protected static function newFactory(): LabItemFactory
    {
        return LabItemFactory::new();
    }
}
