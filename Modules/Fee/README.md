# Fee

Installable module M-10 (SRS Part 2): tuition fees, waiver rules with the Waiver Calculator, scholarships.
Built in parts — 3.4a fee categories and tuition fees; **3.4b waiver rules and the Waiver Calculator**; 3.4c scholarships.

| | |
|---|---|
| Slug | `fee` |
| Tables | `fee_categories`, `fee_tuitions`, `fee_tuition_lines` (B) — DB-03 §11.1 v1.1; `fee_boards`, `fee_waiver_categories` (+ `fee_waiver_category_includes`), `fee_waiver_rules` + pivots boards / categories (ordered) / genders / faculties / tuitions — §11.2 v1.1 |
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

* **Waiver rules** (Academic › Waiver rules, Waiver categories, Boards): level, fee category, result ranges (SSC /
  HSC GPA 0–5 with golden, undergraduate CGPA 0–4; min ≤ max), waiver %, a higher % when the HSC GPA beats the SSC
  GPA, a cap for additive mode, the condition shown with the result; applies to boards, waiver categories (in order —
  the first names the result), genders, faculties, tuitions — none chosen = all. A waiver category "also matches
  the rules of" others (Female → Result Based); a diploma category asks the Diploma CGPA in place of HSC.
  **Test the rules** (list header): a sample input shows the matching rules and the percent (FR-FEE-09).
* **Waiver Calculator** (`Modules\Fee\Support\WaiverCalculator`, page `/waiver-calculator`, Livewire
  `fee.waiver-calculator`): every list comes from the data (fee categories and levels with active rules, the visible
  programs' tuitions, the level's categories, the boards the rules use); the best rule by priority, %, improved %,
  then on a tie the included category's rule, then the older one; waiver = tuition × % (admission and other fees
  are not waived); ranges where credits vary; print; disclaimer from Settings › Fees; nothing is stored.
  **Same results as DIU's legacy calculator: golden master, 27,648 cases, 100%** (`tests/Golden`, Test Plan §7.1.1).
* **Settings › Fees:** when several rules match — the highest (DIU) or added up to the smallest cap; disclaimer.

### Views (theme-overridable: `themes/{theme}/views/modules/fee/…`)

| View | Variables |
|---|---|
| `tuition.grouped` / `tuition.compact` | `$category`, `$categories`, `$groups` (list of `faculty`, `tuitions`), `$choices`, `$selected`, `$options` |
| `tuition.table` | `$tuitions`, `$withFaculty` |
| `partials.filters` | `$category`, `$categories`, `$choices`, `$selected` |
| `partials.tuition-summary` | `$tuition`, `$detailed` |
| `tabs.tuition` | `$entity` (Program), `$tuitions` |
| `calculator.page` | — (holds `<livewire:fee.waiver-calculator />`) |
| `livewire.waiver-calculator` | see the component: lists, `$fields`, `$diploma`, `$answer`, `$tuition`, `$rule`, `$disclaimer` |
