# Tuition

Installable module, the tuition part of M-10 (SRS Part 2): tuition categories and the programs' tuition fees.
Its own team enters the data (by default the Admission Office). The Waiver module reads these figures.

| | |
|---|---|
| Slug | `tuition` |
| Admin | **Academic › Tuition Fees** (Filament cluster `Filament/Clusters/TuitionFees`): Tuition fees, Tuition categories; Program Hub › Fees › Tuition Fees |
| Tables | `tuition_categories`, `tuition_fees`, `tuition_fee_lines` (B) — DB-03 §11.1 v1.3 |
| Permissions | resource `…:Tuition`, `…:FeeCategory`; tuitions are scoped through their program (Program / Department / Faculty users read theirs) |
| Depends on | `program` (hard: `program_id` FK; level = program type), `department`, `faculty` (page filters) |

* **Tuition categories**: name per language ("Local", "International"), code (used in `/tuition-fees?category=local`),
  currency (symbol on the website), order by drag.
* **Tuition fees**: program, category, label (a program may have **several** tuition fees — one per category, and more
  when the label differs, e.g. MBA International "1 Year" / "2 Years"), credits (a range when "up to" is set),
  semesters, duration, prospectus link, and the fees as lines — admission, per year, per semester, per credit, other
  (each with an optional "up to"). **Totals** are worked out on every change (AC-FEE-03):
  `tuition = per year + per semester × semesters + per credit × credits` (the waiver base) and
  `total = tuition + admission + other`; "Enter the totals by hand" keeps typed figures (all migrated DIU tuitions).
* **Program Hub › Fees › Tuition Fees** (`$r->entityPage(Program::class, ProgramTuitions::class)`): the program's
  tuitions — edited with the tuition permissions, read by entity users in their scope; a new one has the program fixed.
* **Website:** `/tuition-fees` — one category at a time, filters faculty / department (short name) and level (slug),
  grouped by faculty (layout "grouped") or one table ("compact"); print button. Program page: tab "Tuition Fees"
  (`$r->entityTab(Program::class, TuitionTab::class)`), only where fees exist. Fees of an inactive program,
  department or category are not shown.

### Views (theme-overridable: `themes/{theme}/views/modules/tuition/…`)

| View | Variables |
|---|---|
| `listing.grouped` / `listing.compact` | `$category`, `$categories`, `$groups` (list of `faculty`, `tuitions`), `$choices`, `$selected`, `$options` |
| `listing.table` | `$tuitions`, `$withFaculty` |
| `partials.filters` | `$category`, `$categories`, `$choices`, `$selected` |
| `partials.summary` | `$tuition`, `$detailed` |
| `tab` | `$entity` (Program), `$tuitions` |
