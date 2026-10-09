<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasOwner;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;

/**
 * One menu of one owner at one location (menu_menus, P-04): the main site's
 * Primary menu, CSE's Footer 1 … (BRL-MENU-03: one per owner and location).
 * Owners without their own menu use their parent's, then the main site's (BRL-MENU-01).
 */
class Menu extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // entity users: menus of their entities only
    use HasOwner;

    protected $table = 'menu_menus';

    /** How the menu opens on the website; the theme draws it. */
    public const STYLES = ['dropdown', 'multilevel', 'mega'];

    protected $fillable = ['name', 'location', 'style', 'is_active'];

    protected $attributes = ['style' => 'dropdown'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function topItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }
}
