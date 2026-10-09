<?php

namespace Platform\Taxonomy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Platform\Concerns\HasSlug;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A category or tag (core_terms, DB-02 §4.2). Slug per language in core_slugs
 * for archive pages (FR-TAX-05). A parent with active children cannot be deleted;
 * deleting a used term takes it off its content (BRL-TAX-01) — restoring brings it back.
 */
class Term extends BaseModel
{
    use HasSlug;
    use HasTranslations;

    public static string $titleAttribute = 'name';

    protected $table = 'core_terms';

    protected $fillable = ['vocabulary_id', 'parent_id', 'name', 'description', 'color', 'icon', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'description'];

    protected string $slugSource = 'name';

    protected array $guardedChildren = ['children'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (Term $term): void {
            $term->sort_order ??= (int) static::withTrashed()->where('vocabulary_id', $term->vocabulary_id)->max('sort_order') + 1;
        });

        static::forceDeleting(fn (Term $term) => DB::table('core_termables')->where('term_id', $term->id)->delete());
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(Vocabulary::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** How many records carry this term (delete warning, BRL-TAX-01). */
    public function usageCount(): int
    {
        return DB::table('core_termables')->where('term_id', $this->id)->count();
    }

    /** @return list<int> this term and every term below it */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $level = [$this->id];

        while ($level !== []) {
            $level = static::withTrashed()->whereIn('parent_id', $level)->pluck('id')->all();
            $ids = [...$ids, ...$level];
        }

        return $ids;
    }
}
