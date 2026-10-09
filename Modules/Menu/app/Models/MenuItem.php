<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * One entry of a menu (menu_items, P-11): a page or other record, a module page,
 * a category, a custom link, a file or a heading; nested up to three levels.
 * Hidden on the website when its target is (FR-MENU-10/11) — decided at render time.
 */
class MenuItem extends BaseModel
{
    use HasTranslations;

    public const TYPES = ['record', 'module_page', 'taxonomy', 'url', 'file', 'heading'];

    public const MAX_DEPTH = 3;

    public static string $titleAttribute = 'title';

    protected $table = 'menu_items';

    protected $fillable = [
        'menu_id', 'parent_id', 'type', 'linkable_type', 'linkable_id', 'route_key', 'url',
        'title', 'icon', 'open_in_new_tab', 'css_class', 'sort_order', 'is_active',
    ];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return ['open_in_new_tab' => 'boolean', 'sort_order' => 'integer', 'linkable_id' => 'integer'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
