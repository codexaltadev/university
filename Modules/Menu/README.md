# Menu

Core module C-08 (SRS Part 1). Appearance › Menus.

| | |
|---|---|
| Slug | `menu` |
| Tables | `menu_menus`, `menu_items` (DB-02 §5) |
| Permissions | custom: `Manage:Menu` (menus of the user's entities), `ManageMain:Menu` (main site) |
| Settings | none; the menu source of a microsite is in `core_microsite_settings` |

* One menu per owner (main site or microsite entity) and location: primary, top bar, footer 1–4, quick links
  (text / icons), sidebar (`config('menu.locations')`).
* Fallback per location (BRL-MENU-01): own → parent … → main site; a microsite may choose "parent first",
  "main site" or "own only". Without an own menu the screen shows where the menu comes from, with
  "create own" and "copy … and edit".
* Items up to three levels: page or record (`$r->menuItemType(Model::class)`; the model needs `url($locale)`
  or a microsite), module page (routes named `site.{module}.index`), category, custom link, file, heading.
  Titles per language; empty = the linked record's title.
* The website hides items whose target is off, unpublished, deleted or in an inactive module (⚠ in admin).
* Website: `app(MenuTree::class)->forLocation('primary', $context)` — nested, cached, refreshed on any change.
