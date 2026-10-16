<?php

namespace Modules\Leadership\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Models\BaseModel;
use Platform\Taxonomy\Term;
use Spatie\Translatable\HasTranslations;

/**
 * A leadership position (M-15, DB-04 §6.1 v1.3): "Vice Chancellor", "Member, Board of
 * Trustees" — in a group (leadership.group), ordered. People hold it through terms; a
 * single-holder position (VC) has one current holder (role "holder", FR-LEAD-07), the others
 * many (role "member").
 */
class Position extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;

    public const GROUP = 'leadership.group';

    public static string $titleAttribute = 'title';

    protected $table = 'leadership_positions';

    protected $fillable = ['title', 'group_term_id', 'is_single_holder', 'sort_order', 'is_active'];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return ['group_term_id' => 'integer', 'is_single_holder' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'group_term_id');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(LeadershipTerm::class);
    }

    /** The catalog role its holders take. */
    public function roleKey(): string
    {
        return $this->is_single_holder ? 'holder' : 'member';
    }

    /** Terms running today, in the people's order. */
    public function currentTerms(): Builder
    {
        return LeadershipTerm::query()->frontendVisible()->where('position_id', $this->id)->current()->inPeopleOrder();
    }
}
