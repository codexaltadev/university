<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Platform\Concerns\HasMediaUsage;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * One entry of a menu (menu_items, P-11): a page or other record, a module page,
 * a category, a custom link, a file or a heading; nested up to three levels.
 * Hidden on the website when its target is (FR-MENU-10/11) — decided at render time.
 */
class MenuItem extends BaseModel
{
    use HasMediaUsage;      // the mega panel's picture
    use HasTranslations;

    public const TYPES = ['record', 'module_page', 'taxonomy', 'url', 'file', 'heading'];

    /** Multi-level dropdowns go four deep; mega menus use three (item → column → link). */
    public const MAX_DEPTH = 4;

    /** Top item display: follow the menu's style, or force mega / plain dropdown. */
    public const DISPLAYS = ['auto', 'mega', 'dropdown'];

    public static string $titleAttribute = 'title';

    protected $table = 'menu_items';

    protected $fillable = [
        'menu_id', 'parent_id', 'type', 'linkable_type', 'linkable_id', 'route_key', 'url',
        'title', 'icon', 'open_in_new_tab', 'css_class', 'options', 'sort_order', 'is_active',
    ];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return ['open_in_new_tab' => 'boolean', 'sort_order' => 'integer', 'linkable_id' => 'integer', 'options' => 'array'];
    }

    /** Mega panel for this top item: its own choice, else the menu's style. */
    public function isMega(?string $menuStyle = null): bool
    {
        return match ($this->options['display'] ?? 'auto') {
            'mega' => true,
            'dropdown' => false,
            default => ($menuStyle ?? $this->menu?->style) === 'mega',
        };
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
