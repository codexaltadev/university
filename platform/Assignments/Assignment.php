<?php

namespace Platform\Assignments;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A person in a role somewhere (core_assignments, P-02, DB-01 §8.2):
 * e.g. Dr. X is "head" of department.department:12 since 2021.
 */
class Assignment extends BaseModel
{
    use HasTranslations;

    public static string $titleAttribute = 'role_key';

    protected $table = 'core_assignments';

    protected $guarded = [];

    public array $translatable = ['role_label', 'note'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_public' => 'boolean',
            'overrides' => 'array',
        ];
    }

    public function person(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    /** In office today (BRL-REL-06). */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $today))
            ->where(fn (Builder $q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', $today));
    }

    /** "Former" list: the term has ended. */
    public function scopeFormer(Builder $query): Builder
    {
        return $query->where('ends_on', '<', now()->toDateString());
    }
}
