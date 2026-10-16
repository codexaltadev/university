# Achievements

Awards and competition wins of students, teams and teachers (M-24, DB-05 §9 v1.8, SRS Content
FR-ACH-01..04).

| | |
|---|---|
| Slug | `achievements` |
| Table | `achievements_achievements` (model `Achievement`, attachable, assignable) |
| Achievers | assignments, role `achiever` — Person Picker (Faculty Members, Alumni, Administration or a manual person), a note each ("Team lead"); `team_name` for the team |
| Fields | title, summary, story, competition, issuer, position, team name per language; `achieved_on`, `level` (international / national / regional / institutional) |
| Pictures | Media Library uses `image` (cover) and `gallery` — never copies |
| Related news | `related_post_id` — a News post when News is installed (FR-ACH-04) |
| Time | `is_active`, `active_at`; no publish date. Lists go by `achieved_on`, newest first |
| Where | "Show in" entities; the main site by `PublishMain:Achievement` (Admin); highlight |
| Vocabulary | `achievements.category` (hierarchical) |
| Layouts | Appearance › Layouts `achievements.listing` = cards / timeline / grid |
| Metric | `achievements.count`, params `{"level": "international"}` — "100+ International Awards" |
| Legacy | none — DIU's old site had no achievements table |

Website:

| Address | What |
|---|---|
| `/achievements`, `/{entity}/achievements` | newest first; `?year=`, `?level=`, `?category=`, `?view=` |
| `/achievements/{slug}` (also under a microsite) | details, achievers, pictures (Gallery's lightbox when installed), related news, JSON-LD |

Homepage section `achievements.recent` "Recent Achievements": the builder's source mode ("latest"
= by date achieved), one level optionally, cards or a slider.

## Views

| View | Variables |
|---|---|
| `achievements::index` | `$achievements` (paginator), `$categories`, `$category`, `$level`, `$year`, `$years`, `$layout`, `$base` |
| `achievements::show` | `$achievement`, `$achievers` (PersonView list), `$gallery`, `$post`, `$base` |
| `achievements::partials.card` | `$achievement`, `$base`, `$layout` |
| `achievements::sections.recent` | `$items`, `$section`, `$settings`; `$context` from the page |
