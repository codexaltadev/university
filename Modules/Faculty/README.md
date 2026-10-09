# Faculty

Installable module M-01 (SRS Part 2). Academic › Faculties and the faculty microsites.

| | |
|---|---|
| Slug | `faculty` |
| Tables | `faculty_faculties` (B) — DB-03 §2; media `photo`, `cover`, `logo`; Dean = assignment `dean` |
| Permissions | resource `…:Faculty`; Faculty users (scope) edit the profile but not name, short name, address, active (BRL-FAC-02) |
| Depends on | — (top of the hierarchy) |

* **Admin:** name, overview, Dean's message, location per language; short name (unique), web address, email,
  phone, "show in the faculty list", logo / photo / cover, people (Dean, Associate Dean, Officer, Contact), SEO,
  order by drag. A Faculty with active Departments cannot be deleted (BRL-FAC-01 — the kernel counts children
  that other modules registered with `relationTarget(…, parent: Faculty::class)`).
* **Website:** `/faculties` in the layout chosen in Appearance › Layouts (grid, list, card; items per row);
  microsite `/faculty/{slug}` with the theme's microsite template; until the Faculty builds its own homepage,
  `faculty::microsite.home` shows cover, overview, counters, Dean's message and contact (FR-FAC-06).
* **Counters:** every new Faculty gets its own stat set — Departments, Programs, Faculty Members (shown once those
  modules are active) and Students (manual) (FR-FAC-04, AC-FAC-02). Metric `faculty.count` for the main site.
* **Homepage section** "Our Faculties" (`faculty.list`), search (faculty group, "only in this Faculty"), sitemap,
  JSON-LD `CollegeOrUniversity`.

### Views (theme-overridable: `themes/{theme}/views/modules/faculty/…`)

| View | Variables |
|---|---|
| `listing.grid` / `listing.list` / `listing.card` | `$faculties`, `$options` (per_row, sidebar) |
| `partials.card` | `$faculty`, `$style` |
| `sections.our-faculties` | `$items`, `$section`, `$settings` |
| `microsite.home` | `$entity` (Faculty), `$context` |
