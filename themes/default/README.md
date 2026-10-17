# University Default theme

The bundled website theme (BRL-THEME-01: it can never be uninstalled). **It is a copy of the live
daffodilvarsity.edu.bd** (owner decision 2026-10-17): every part is built from the live page's markup
and measurements; other themes get their own designs. It follows the
[Theme Development Guide](../../docs/07-DevGuide/02-Theme-Development-Guide.md).

| Offers | |
|---|---|
| Layouts | `layouts/app`, `layouts/microsite` with every slot (no top bar and no microsite bar: DIU has neither) |
| Page templates | default, full-width, with-sidebar, landing |
| Microsite templates | standard |
| Header variants | classic (= DIU), centered, minimal |
| Footer variants | columns (= DIU), compact |
| Menu styles | mega (DIU main menu), dropdown / multilevel (DIU department menu) |
| Menu locations | primary, topbar = utility links above the menu, quick-links-icon = Services grid (item icon = an icon name), footer-1…4 |
| Options | `sticky_header`, `hero_height`, `header_cta_url` / `header_cta_label` (Apply button), `footer_band_1/2_label/url`, `footer_cta_title` (`\|` = line break) / `_text` / `_image` / `_info_label` / `_info_url` |
| Components | `<x-theme::icon name="search" />` — outline icons (lucide shapes) and the social platforms |

Overrides: `views/livewire/instant-search.blade.php` (search window),
`views/modules/newsletter/livewire/subscribe-form.blade.php` (footer Subscribe Us).

Header parts live in `views/partials/header-parts/` (brand, utility, actions, mobile, search window,
mobile menu) and are shared by the three header variants; footer parts in `views/partials/footer-parts/`.
Alpine component `siteHeader` (`resources/js/app.js`) opens the search window and mobile menu, folds the
utility links on scroll and measures the header into `--header-h`.

## Build

Sources are `resources/css/app.css` + `chrome.css` (Tailwind CSS 4.3.3, pinned in the root `package.json`),
`resources/js/app.js` and `resources/fonts` (Mina, for the microsite name); the prebuilt result in
`public/build` is committed (T-3: no build on the server). `public/images/footer-cta.jpg` is the default
campus photo.

```bash
npm run build:theme   # from the repository root (Node from .nvmrc)
npm run dev:theme     # Vite dev server; @themeVite uses it while public/hot exists
```

Tailwind's palette, type scale, radii and container are kept so DIU's classes copy over as they are
(`text-gray-600`, `text-sm`, `rounded-lg` …). Brand colours come only from the kernel's design tokens:
`bg-primary` = DIU blue, `bg-secondary` = DIU navy (DIU's own CSS names them the other way round).
No hex colour in the theme (T-2, checked by the test).

The theme styles the **module UI contract** (UI/UX Part B §9.1): `btn` (`--primary`, `--secondary`,
`--outline`, `--sm`), `badge` (`--success`, `--muted`, `--warning`, `--danger`), `card`, `prose`, `lead`,
`table`, `accordion__item` / `__title` / `__body`, `empty-state`, `form-error`, `no-print`, `page`,
`page__title`, `page-grid`, `home-section` (`--alt`, `--dark`, `__title`, `__sub`).

Alpine.js (bundled with Livewire) is used wherever it is the best fit for an interaction (Theme Guide §12);
components are registered in `resources/js/app.js` on `alpine:init`.
