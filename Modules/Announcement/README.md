# Announcement

Greeting popup, news ticker, countdown and announcement bar for any site (M-30 Announcement
Widgets, DB-05 §15 v1.11, SRS Content FR-ANW-01..07).

| | |
|---|---|
| Slug | `announcement` |
| Table | `announcement_widgets` (model `Widget`, attachable) |
| Types | `popup` (picture from the Media Library, rich text, button, how often: each visit / once a day / once, priority), `ticker` (a line, link, optionally the place's latest board notices), `countdown` (target, after the end: hide / message), `bar` (colour token, can be closed) |
| Where | "Show in" entities, the main site by `PublishMain:Widget` (Admin) — only on that place's own site |
| Pages | `home` (the site's homepage), `all`, `selected` — module website page keys (`notice.index` …) or addresses of the site (`/admission`, with the pages below) |
| Time | `is_active`, `active_at` (from), `expires_at` (until) |
| Website | the theme layout slots `announcement` (bars, countdowns), `ticker` (all lines, moving, pauses on hover / focus, still with reduced motion), `popup` (one, highest priority; `role="dialog"`, Esc, focus trap `x-trap`, remembered in the browser) — through the kernel slot: no theme partial → SiteData `slot:{name}` (`Support\Announcements`, loaded once per request) |
| Legacy | `php artisan legacy:migrate --step=S17A` (greetings, news tickers, countdown); popup pictures after S03 |

## Views

| View | Variables |
|---|---|
| `announcement::slots.announcement` | `$widgets` (bars and countdowns) |
| `announcement::slots.ticker` | `$items` (text, url) |
| `announcement::slots.popup` | `$widget` |
