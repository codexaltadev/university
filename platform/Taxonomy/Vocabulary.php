<?php

namespace Platform\Taxonomy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Concerns\LogsPlatformActivity;
use Spatie\Translatable\HasTranslations;

/**
 * A set of terms (core_vocabularies, DB-02 §4.1): "news.category", "shared.tags".
 * Created from the modules' registrations ($r->vocabulary(...)); admins may only rename it.
 */
class Vocabulary extends Model
{
    use HasTranslations;
    use LogsPlatformActivity;

    protected $table = 'core_vocabularies';

    protected $fillable = ['key', 'module_slug', 'name', 'is_hierarchical', 'allows_multiple', 'is_ordered'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['is_hierarchical' => 'boolean', 'allows_multiple' => 'boolean', 'is_ordered' => 'boolean'];
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }
}
