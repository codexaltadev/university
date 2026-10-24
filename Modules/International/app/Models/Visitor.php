<?php

namespace Modules\International\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\International\Database\Factories\VisitorFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\IsAttachable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * An international visitor or delegation (M-35, DB-06 v1.3 §6, FR-INTL-06): name, designation and
 * organisation as free text (guests are not in the directory), country, day, photo from the Media
 * Library, a Gallery album when Gallery is installed.
 */
class Visitor extends BaseModel implements Scopeable
{
    use AppliesUserScope;

    /** @use HasFactory<VisitorFactory> */
    use HasFactory;

    use HasMediaUsage;      // photo
    use HasTranslations;
    use IsAttachable;

    public const PATH = 'visitors';

    public const ALBUM = 'Modules\\Gallery\\Models\\Album';

    public static string $titleAttribute = 'name';

    protected $table = 'international_visitors';

    protected $fillable = ['name', 'designation', 'organization', 'country_id', 'visited_on', 'body', 'is_external', 'album_id', 'is_active'];

    protected $attributes = ['is_external' => true];

    public array $translatable = ['name', 'designation', 'organization', 'body'];

    protected function casts(): array
    {
        return ['visited_on' => 'date', 'is_external' => 'boolean'];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /** The Gallery album, when Gallery is installed and the album is still there. */
    public function album(): ?Model
    {
        return $this->album_id && class_exists(self::ALBUM) ? (self::ALBUM)::query()->find($this->album_id) : null;
    }

    public function scopeNewest(Builder $query): Builder
    {
        return $query->reorder()->orderByDesc($this->qualifyColumn('visited_on'))->orderByDesc($this->getQualifiedKeyName());
    }

    public function url(?string $locale = null): string
    {
        return '/'.self::PATH.'#visitor-'.$this->getKey();
    }

    protected static function newFactory(): VisitorFactory
    {
        return VisitorFactory::new();
    }
}
