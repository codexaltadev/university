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

## Build

Sources are `resources/css/app.css` (Tailwind CSS 4.3.3, pinned in the root `package.json`) and
`resources/js/app.js`; the prebuilt result in `public/build` is committed (T-3: no build on the server).

```bash
npm run build:theme   # from the repository root (Node from .nvmrc)
npm run dev:theme     # Vite dev server; @themeVite uses it while public/hot exists
```

Every colour, font, size, radius and shadow utility comes from the kernel's design tokens
(`bg-primary`, `text-muted`, `font-heading`, `rounded-lg`, `shadow-card`, `max-w-container`);
the default Tailwind palette is removed, so hard-coded colours do not build (T-2).

The theme styles the **module UI contract** (UI/UX Part B §9.1): `btn` (`--primary`, `--secondary`,
`--outline`, `--sm`), `badge` (`--success`, `--muted`, `--warning`, `--danger`), `card`, `prose`, `lead`,
`table`, `accordion__item` / `__title` / `__body`, `empty-state`, `form-error`, `no-print`, `page`,
`page__title`, `page-grid`, `home-section` (`--alt`, `--dark`, `__title`, `__sub`).

Alpine.js (bundled with Livewire) is used wherever it is the best fit for an interaction (Theme Guide §12);
components are registered in `resources/js/app.js` on `alpine:init`.
