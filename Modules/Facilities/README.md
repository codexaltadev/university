# Facilities

Campus facilities — library, hostel, medical centre … (M-33, DB-06 v1.4 §4, SRS Services FR-FCL-01..05).

| | |
|---|---|
| Slug | `facilities` |
| Tables | `facilities_facilities` (attachable: title, summary, body, outside link + new tab, dragged order) |
| Media | `icon`, `image`, `gallery` (Media Library uses) |
| Taxonomy | `facilities.category` |
| Admin | Services › Facilities |
| Program tab | "Facilities" on a program's page (`Tabs\ProgramFacilitiesTab`, key `facilities.program`, view `facilities::tabs.program` with `$entity`, `$items`): the facilities shown in that program; none → no tab. Program is optional. |
| Legacy | `php artisan legacy:migrate --step=S18F` — main-site rows → main site, a program's → shown there, a program's use of a main facility (`reference_id`) → that facility shown in the program too; `is_home` → highlight |

Website: `/facilities` and `/{entity}/facilities` (`?category=`; icons / cards / list — Appearance › Layouts
`facilities.listing`), `/facilities/{slug}` only when there is text (else the card goes to the link).
Homepage section `facilities.list` "Campus Facilities" (highlights first; icons or cards).

## Views

| View | Variables |
|---|---|
| `facilities::index` | `$facilities`, `$categories`, `$category`, `$layout`, `$base` |
| `facilities::show` | `$facility`, `$gallery`, `$base` |
| `facilities::partials.card` | `$facility`, `$base`, `$layout` |
| `facilities::sections.list` | `$items`, `$section`, `$settings` (display) |
