# Events

Seminars, workshops, conferences, cultural programs, convocations (M-19, DB-05 §4 v1.3, SRS
Content FR-EVT-01..10).

| | |
|---|---|
| Slug | `events` |
| Table | `events_events` (model `Event`, attachable, assignable) |
| Dates | `starts_at` required, `ends_at` after it, `is_all_day`; upcoming / ongoing / past come from them (`Event::state()`, scopes `upcoming()`, `past()` — AC-EVT-01) |
| Where | "Show in" entities; the main site by `PublishMain:Event` (Admin); an entity's list includes the entities below it |
| People | speakers and moderators (`core_assignments`, roles `speaker`, `moderator`) — kernel `PeopleManager` |
| Scheduling | `active_at`: when it starts to show on the website (not the event's date) |
| Venue / photos | venue as words until M-08 Campus; photos as a Media gallery until M-22 Gallery |
| Vocabulary | `events.category` (seeded once) |
| Settings | Appearance › Layouts `events.listing` = list / cards / timeline / calendar |
| Legacy | `php artisan legacy:migrate --step=S16E` (after S04) |

Website:

| Address | What |
|---|---|
| `/events`, `/{entity}/events` | tab Upcoming (with ongoing, by start) or `?tab=past` (newest first); `?view=list\|cards\|timeline\|calendar` (`&month=2026-11`); `?category=` |
| `/events/{slug}` (also under a microsite) | when, venue, online link (until it is over), organizer, registration (closed after `registration_ends_at`), speakers, gallery, `.ics` + Google Calendar (FR-EVT-07), JSON-LD `Event`; 404 where it is not shown |
| `/events/{slug}.ics` | one VEVENT — all day as dates, timed in UTC |

Homepage sections (FR-EVT-08): `events.upcoming` (count, optional countdown — Alpine) and
`events.past` (the place's highlighted past events, else the latest past). Metric `events.count`,
Content Update Reminder source.

## Views

| View | Variables |
|---|---|
| `events::index` | `$events` (paginator, or the month's list for calendar), `$categories`, `$category`, `$layout`, `$tab`, `$month`, `$weeks`, `$base` |
| `events::show` | `$event`, `$speakers` (PersonView list), `$gallery`, `$base` |
| `events::partials.card` | `$event`, `$layout`, `$base` |
| `events::sections.upcoming` / `past` | `$items`, `$section`, `$settings`; `$context` from the page |
