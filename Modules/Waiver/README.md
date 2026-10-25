# Waiver

Installable module, the Waiver Calculator part of M-10 (SRS Part 2): waiver categories, waiver rules, boards and
the public calculator; scholarships follow (3.4c). Tuition fees are the Tuition module's (`Modules/Tuition`).

| | |
|---|---|
| Slug | `waiver` |
| Admin | **Academic › Waiver Calculator** (Filament cluster `Filament/Clusters/WaiverCalculator`): Waiver rules, Waiver categories, Boards |
| Tables | `waiver_boards`, `waiver_categories` (+ `waiver_category_includes`), `waiver_rules` + pivots `waiver_rule_boards` / `_categories` (ordered) / `_genders` / `_faculties` / `_tuitions` — DB-03 §11.2 v1.2 |
| Permissions | resource `…:WaiverRule`, `…:WaiverCategory`, `…:Board` (by default the Waiver Office) |
| Depends on | `tuition` (tuition categories and fees — `tuition_categories`, `tuition_fees` FKs), `program` (levels `program.level`, programs), `faculty` (rule condition). Entered here: only categories, rules and boards; the rest is inherited |

* **Waiver rules**: level, fee category, result ranges (SSC / HSC GPA 0–5 with golden, undergraduate CGPA 0–4;
  min ≤ max), waiver %, a higher % when the HSC GPA beats the SSC GPA, a cap for additive mode, the condition shown
  with the result; applies to boards, waiver categories (in order — the first names the result), genders, faculties,
  tuitions — none chosen = all. A waiver category "also matches the rules of" others (Female → Result Based); a
  diploma category asks the Diploma CGPA in place of HSC.
  **Test the rules** (list header): a sample input shows the matching rules and the percent (FR-FEE-09).
* **Waiver Calculator** (`Modules\Waiver\Support\WaiverCalculator`, page `/waiver-calculator`, Livewire
  `waiver.calculator`): every list comes from the data (fee categories and levels with active rules, the visible
  programs' tuitions, the level's categories, the boards the rules use); the best rule by priority, %, improved %,
  then on a tie the included category's rule, then the older one; waiver = tuition × % (admission and other fees
  are not waived); ranges where credits vary; print; disclaimer from the settings; nothing is stored.
  **Same results as DIU's legacy calculator: golden master, 27,648 cases, 100%** (`tests/Golden`, Test Plan §7.1.1).
* **Settings › Waiver Calculator** (`settings('waiver.waiver_mode')`, `settings('waiver.disclaimer')` …): when several
  rules match — the highest (DIU) or added up to the smallest cap; the page's title, line and picture on top
  (default "Calculate Your Fees"); the "Important Notice" — title, the disclaimer as its text (empty = no notice),
  button label and link (default `/scholarships`).
* **For themes (T-1)**: `Support\CalculatorPage` (`$page` in `calculator.page`: title, text, image, breadcrumbs,
  notice) and `Support\CalculatorForm` (`$calc` in `livewire.waiver-calculator`: programs, waiver category tiles with
  DIU's icon by name and its description, how many show before "Show all", programs grouped by faculty with
  search terms, the chosen program's duration / credits / total, golden GPA allowed, the result panel's figures,
  why there is no waiver). The component's `clear()` is DIU's "Reset".

### Views (theme-overridable: `themes/{theme}/views/modules/waiver/…`)

| View | Variables |
|---|---|
| `calculator.page` | `$page` (CalculatorPage); holds `<livewire:waiver.calculator />` |
| `livewire.waiver-calculator` | see the component: lists, `$fields`, `$diploma`, `$answer`, `$tuition`, `$rule`, `$disclaimer`, `$tuitionId`, `$waiverCategoryId`, `$ssc`, `$hsc`; `$calc` (CalculatorForm) |
