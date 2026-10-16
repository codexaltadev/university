<?php

namespace Modules\Links\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Links\Database\Factories\LinkFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAttachable;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * An important link (M-29, DB-05 §14 v1.10, SRS Content FR-LINK-01..03): UGC, Student Portal,
 * Library … with an icon from the Media Library and a group; each place its own ("Show in" and
 * the main site); dragged order; a weekly check marks broken ones.
 */
class Link extends BaseModel implements Scopeable
{
    use AppliesUserScope;

    /** @use HasFactory<LinkFactory> */
    use HasFactory;

    use HasMediaUsage;      // icon
    use HasSortOrder;
    use HasTaxonomy;        // links.group
    use HasTranslations;
    use IsAttachable;

    public const PATH = 'links';

    public const GROUP = 'links.group';

    public static string $titleAttribute = 'title';

    protected $table = 'links_links';

    protected $fillable = ['title', 'url', 'description', 'open_in_new_tab', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'description'];

    protected $attributes = ['open_in_new_tab' => true];

    protected function casts(): array
    {
        return ['open_in_new_tab' => 'boolean', 'last_checked_at' => 'datetime', 'last_status_code' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Link $link): void {
            $link->url = trim((string) $link->url);
            if (! preg_match('#^(https?://[^\s]+|/[^\s]*|mailto:[^\s]+|tel:[^\s]+)$#i', $link->url)) {
                throw ValidationException::withMessages(['url' => __('links::links.errors.url')]);
            }
        });
        static::updated(function (Link $link): void {
            if ($link->wasChanged('url')) {        // a new address is checked again (the check writes past Eloquent)
                DB::table($link->getTable())->where('id', $link->id)->update(['last_checked_at' => null, 'last_status_code' => null]);
            }
        });
    }

    /** Outside addresses the weekly check looks at. */
    public function isExternal(): bool
    {
        return (bool) preg_match('#^https?://#i', (string) $this->url);
    }

    /** No answer, or an error answer, at the last check. */
    public function isBroken(): bool
    {
        return $this->last_checked_at !== null && ($this->last_status_code === 0 || $this->last_status_code >= 400);
    }

    public function scopeBroken(Builder $query): Builder
    {
        $code = $this->qualifyColumn('last_status_code');

        return $query->whereNotNull($this->qualifyColumn('last_checked_at'))->where(fn (Builder $q) => $q->where($code, 0)->orWhere($code, '>=', 400));
    }

    public function href(): string
    {
        return str_starts_with((string) $this->url, '/') ? url($this->url) : (string) $this->url;
    }

    protected static function newFactory(): LinkFactory
    {
        return LinkFactory::new();
    }
}
