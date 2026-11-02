# University Modern

A modern, minimal website theme for any university (UI/UX Part B §11–12, `docs/00-MODERN-THEME.md`). No university's name,
links, pictures or colours are in the theme: the logo, colours, fonts and every text come from Admin. Default theme of new installs.

```bash
npm run build:theme:modern          # or: npx vite build --config themes/modern/vite.config.js
php artisan uwms:theme:sync
php artisan uwms:theme:check modern
php artisan test tests/Feature/Theme/ModernThemeTest.php
```

## Design

- Tokens (theme.json, changed in Admin › Appearance › Design): primary `#1D4ED8`, secondary `#0F172A`, accent `#F59E0B`, surface
  `#F5F7FA`, radius 8 / 12 / 20px, container 1240px. Tailwind's palette is switched off — only token colours exist (`bg-primary`,
  `text-muted`, `border-border` …).
- Fonts bundled (OFL, `resources/fonts`): Inter (Latin), Noto Sans Bengali; `theme.json "fonts"` keeps the kernel from Google Fonts.
- Header variants `modern` (utility row, mega panels, search, drawer) and `minimal`; footer variants `columns` and `simple`.
- Options: `sticky_header`, `header_cta_label` / `header_cta_url` (empty = no button), `footer_tagline`, `animations`.

## Homepage sections (theme.json "sections")

`hero`, `program-finder`, `notices` (notice.board), `events` (events.upcoming), `news` (news.latest), `faculties` (faculty.list),
`programs` (program.featured), `stats` (core.statistics), `achievements` (achievements.recent), `admission-cta`, `testimonials`
(testimonials.list), `gallery` (gallery.photos), `partners` (partners.list). Views get `$section` (title, subheading, content) and
`$items` (the source's records). Core sections drawn by `sections/core-*`.

## Module views it redraws (`views/modules`)

| View | Contract (from the module) |
|---|---|
| `notice/index`, `notice/show` | module data + `$page` (NoticeBoardPage / NoticePage) |
| `news/index`, `news/show` | module data + `$page` (NewsPage) |
| `events/show` | module data + `$page` (EventPage) |
| `program/partials/card`, `program/listing/card`, `program/microsite/home` | `$program`, `$style` / module listing data / `$entity`, `$page` (ProgramPage) |
| `waiver/calculator/page`, `tuition/listing/grouped` | page frames; the Livewire calculator is untouched |
| `search` (theme root) | kernel search results |

Every other module page uses the module's own view with the UI class contract styled here.
