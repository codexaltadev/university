# Transport

Bus routes, stops and times on the website — from DIU's **live transport system** (M-31,
DB-06 v1.1 §2). Owner, 2026-10-17: the transport system stays as it is (it is live and works);
the website shows it through its API. So this module has **no tables and no admin screens**.

| | |
|---|---|
| Slug | `transport` |
| Source | `GET https://webbackend.daffodilvarsity.edu.bd/api/v2/public/transport` (Settings › Transport: address, cache minutes — 30, timeout — 8 s) |
| `Support\TransportApi` | fetches, normalises (`regular_road`, `shuttle_road`, `friday_road` → routes with stops, times to / from campus, days off, notes without "Note:" / numbers, map), caches the answer (arrays only); keeps the **last good answer** forever — shown with "as of …" when the API is down; a down API is asked again after 5 minutes, not on every visit |
| Safety | only `https://` map and PDF addresses are used; everything is escaped |
| Legacy | nothing migrated — the data stays in the live system |

Website: `/transport` (main site), in the order of DIU's current page — introduction and
**Transport Pass** (Settings › Transport: text and picture, defaults = the current page); "Regular
Service Schedule @ DSC", semester, **View Rules & Policy**; **Find your bus** (type a stop: the
routes through it); tabs Regular / Shuttle / Friday (`?service=`); route cards — "Departure from
{first stop}" / "Departure from {DSC}" with times (7:00 AM …), days off, stops and notes (open on
click), map (loaded on click); **Transport Fees** table (Settings › Transport — not in the API).
Menus › Module page "Transport". Homepage section `transport.finder` "Find your bus".

Should the transport system move into this application one day, DB-06 §2.1 has the table design.

## Views

| View | Variables |
|---|---|
| `transport::index` | `$timetable` (ok, stale, fetched_at, title, semester, policy_pdf, service, groups), `$group`, `$stops` |
| `transport::partials.finder` | `$stops`, `$routes` (id, name, group, service, stops), `$link`, `$id` |
| `transport::sections.finder` | `$items` (timetable, stops), `$section`, `$settings` |
