<?php

namespace Modules\Office\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsRelationTarget;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Platform\Taxonomy\Term;
use Spatie\Translatable\HasTranslations;

/**
 * An office / administrative unit (M-12, DB-04 §3): VC Office, Registrar, Exam Office,
 * Library … with its sections, services and support contacts; Head = assignment "head".
 * Office users work through data scope like Department users ("Works on", BRD Q-13 v1.9): only
 * their office and what is shown in it. Without its own microsite (has_microsite off, FR-OFF-11)
 * it is a contact card on /offices only.
 */
class Office extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // Office users: their own office
    use HasMediaUsage;      // logo, cover (Media Library)
    use HasSeo;
    use HasSlug;            // microsite /office/{slug}
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;
    use IsRelationTarget;
    use IsSearchable;       // website search: the office's microsite (FR-SRCH-01)

    public const TYPE = 'office.type';

    public const LISTING = 'offices';

    protected $table = 'office_offices';

    protected $fillable = [
        'parent_id', 'type_term_id', 'name', 'short_name', 'overview', 'head_message', 'email', 'phone', 'extension', 'quick_links',
        'highlights', 'quick_services', 'location', 'campus_id', 'office_hours', 'has_microsite', 'sort_order', 'is_active',
    ];

    public array $translatable = ['name', 'overview', 'head_message', 'location', 'office_hours'];

    protected string $slugSource = 'name';

    protected $attributes = ['has_microsite' => true];

    protected function casts(): array
    {
        return ['quick_links' => 'array', 'highlights' => 'array', 'quick_services' => 'array', 'parent_id' => 'integer', 'type_term_id' => 'integer', 'campus_id' => 'integer', 'has_microsite' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'type_term_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(OfficeSection::class)->ordered();
    }

    public function services(): HasMany
    {
        return $this->hasMany(OfficeService::class)->ordered();
    }

    public function supportContacts(): HasMany
    {
        return $this->hasMany(OfficeSupportContact::class)->ordered();
    }

    /** FR-OFF-11: the kernel opens a microsite only when this is true (MicrositeResolver). */
    public function hasMicrosite(): bool
    {
        return (bool) $this->has_microsite;
    }

    public function scopeListed(Builder $query): Builder
    {
        return $query->frontendVisible()->ordered();
    }

    /** The office's microsite, e.g. /office/exam-office; none without its own page. */
    public function url(?string $locale = null): ?string
    {
        return $this->hasMicrosite() ? app(MicrositeResolver::class)->urlFor($this, $locale) : null;
    }

    /** The office itself: found by "only in this office" too (FR-SRCH-06). */
    public function searchOwnerKey(): ?string
    {
        return $this->getMorphClass().':'.$this->getKey();
    }

    protected function searchBodyAttributes(): array
    {
        return ['overview'];
    }
}
