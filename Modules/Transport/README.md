# Transport

Bus routes, stops and times (M-31, DB-06 v1.2 §2, SRS Services FR-TRN-01..11). Owner,
2026-10-17: DIU's old transport system goes, so the data lives here — with the old system's public
API kept, field for field, for the current frontend.

| | |
|---|---|
| Slug | `transport` |
| Tables | `transport_service_types` (Regular, Final Exam … one default), `transport_stops` (name, landmark, latitude / longitude), `transport_routes` (`route_group` regular / shuttle / friday, map, message, order), `transport_route_variants` (route × service: `days_off`, message), `transport_variant_stops` (ordered stops, at least two), `transport_departures` (to / from campus, time, regular / shuttle, `days_off`) |
| Running service | Settings › Transport — each route shows its variant for that service, else the default service's; a route with neither is not shown |
| Settings › Transport | running service, schedule title, semester, rules PDF (Media Library); page text — introduction, Transport Pass (with picture), fees table |
| Admin | Transport › Routes (variants: service, days off, message, stops dragged into order, times added many at once; **Copy to another service**), Stops (position opens in Google Maps), Service types |
| `Support\Timetable` | `website()` for /transport and the homepage section; `api()` — the old system's JSON |
| Legacy | `php artisan legacy:migrate --step=S18T` — services (the running one → settings), stops, roads → routes + default-service variants, `road_services` → other services' variants; start / end stop from the road (DSC was never in the pivot); times copied per variant; rules PDF after S03. Safe to run again |

Website: `/transport` (main site) — introduction and Transport Pass; "{service} Schedule @ DSC",
schedule title, semester, **View Rules & Policy**; **Find your bus** (stop search); tabs Regular /
Shuttle / Friday (`?service=`); route cards — "Departure from {first stop}" / "Departure from
{last stop}" with times, days off, stops and notes, map on request; **Transport Fees**. Menus ›
Module page "Transport". Homepage section `transport.finder`.

Public API: **`GET /api/v2/public/transport`** — `status`, `title`, `semester`, `organization`,
`rules_policy_pdf`, `active_service_name` / `_code`, `total_roads`, `data.regular_road` /
`shuttle_road` / `friday_road` (`id`, `name`, `start_stop`, `end_stop`, `order`, `map_url`, `tag`,
`day_off`, `message`, `service_name`, `service_code`, `stops[]`, `schedules.from_home[]` /
`from_campus[]` with `time` "07:00:00", `order`, `direction`, `schedule_type`, `off_day`). No
session or cookies; `Access-Control-Allow-Origin: *`; cached 5 minutes by browsers.

## Views

| View | Variables |
|---|---|
| `transport::index` | `$timetable` (ok, title, semester, policy_pdf, service, groups), `$group`, `$stops`, `$page` |
| `transport::partials.finder` | `$stops`, `$routes`, `$link`, `$id` |
| `transport::sections.finder` | `$items` (timetable, stops), `$section`, `$settings` |
