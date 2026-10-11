# University Default theme

The bundled website theme (BRL-THEME-01: it can never be uninstalled). It follows the
[Theme Development Guide](../../docs/07-DevGuide/02-Theme-Development-Guide.md).

| Offers | |
|---|---|
| Layouts | `layouts/app`, `layouts/microsite` with every slot |
| Page templates | default, full-width, with-sidebar, landing |
| Microsite templates | standard |
| Header variants | classic, centered, minimal |
| Footer variants | columns, compact |
| Menu styles | dropdown, multilevel, mega (`partials/menus/*`) |
| Options | `sticky_header`, `hero_height` |

Module view overrides: none yet.

Assets in `public/build` are plain CSS / JS that use only the design tokens (T-2).
The full design (Tailwind CSS 4.3.3 build, sections, listing layouts) comes in Phase 4.
Alpine.js (bundled with Livewire) is used wherever it is the best fit for an interaction (Theme Guide §12).
