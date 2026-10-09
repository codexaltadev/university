# Fee

Installable module M-10 (SRS Part 2): tuition fees, waiver rules with the Waiver Calculator, scholarships.
Built in parts — **3.4a (this): fee categories and tuition fees**; 3.4b waiver rules and calculator; 3.4c scholarships.

| | |
|---|---|
| Slug | `fee` |
| Tables | `fee_categories`, `fee_tuitions`, `fee_tuition_lines` (B) — DB-03 §11.1 v1.1 |
| Permissions | resource `…:FeeCategory`, `…:Tuition`; tuitions are scoped through their program (Program / Department / Faculty users) |
| Depends on | `program` (hard: `program_id` FK), `department`, `faculty` (page filters) |

* **Fee categories** (Academic › Fee categories): name per language, code (used in `/tuition-fees?category=local`),
  currency (symbol on the website), order by drag.
* **Tuition fees** (Academic › Tuition fees): program, category, label (when one program has several, e.g. MBA
  International "1 Year" / "2 Years"), credits (a range when "up to" is set), semesters, duration, prospectus link,
  and the fees as lines — admission, per year, per semester, per credit, other (each with an optional "up to").
  **Totals** are worked out on every change (AC-FEE-03):
  `tuition = per year + per semester × semesters + per credit × credits` (the waiver base) and
  `total = tuition + admission + other`; "Enter the totals by hand" keeps typed figures (all migrated DIU tuitions,
  whose figures do not follow from their lines).
* **Website:** `/tuition-fees` — one category at a time, filters faculty / department (short name) and level (slug),
  grouped by faculty in the faculties' order (layout "grouped") or one table ("compact"); print button, the filters
  are not printed. Program page: tab "Tuition Fees" (`$r->entityTab(Program::class, TuitionTab::class)`) with every
  line, tuition, total and prospectus — only where fees exist. Fees of an inactive program, department or category
  are not shown.

### Views (theme-overridable: `themes/{theme}/views/modules/fee/…`)

| View | Variables |
|---|---|
| `tuition.grouped` / `tuition.compact` | `$category`, `$categories`, `$groups` (list of `faculty`, `tuitions`), `$choices`, `$selected`, `$options` |
| `tuition.table` | `$tuitions`, `$withFaculty` |
| `partials.filters` | `$category`, `$categories`, `$choices`, `$selected` |
| `partials.tuition-summary` | `$tuition`, `$detailed` |
| `tabs.tuition` | `$entity` (Program), `$tuitions` |
