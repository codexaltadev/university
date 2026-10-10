# Waiver

Installable module, the Waiver Calculator part of M-10 (SRS Part 2): waiver categories, waiver rules, boards and
the public calculator; scholarships follow (3.4c). Tuition fees are the Program module's (`Modules/Program`, see its README).

| | |
|---|---|
| Slug | `waiver` |
| Admin | **Academic › Waiver Calculator** (Filament cluster `Filament/Clusters/WaiverCalculator`): Waiver rules, Waiver categories, Boards |
| Tables | `waiver_boards`, `waiver_categories` (+ `waiver_category_includes`), `waiver_rules` + pivots `waiver_rule_boards` / `_categories` (ordered) / `_genders` / `_faculties` / `_tuitions` — DB-03 §11.2 v1.2 |
| Permissions | resource `…:WaiverRule`, `…:WaiverCategory`, `…:Board` (by default the Waiver Office) |
| Depends on | `program` (levels `program.level`, programs, fee categories and tuitions — `program_fee_categories`, `program_tuitions` FKs), `faculty` (rule condition). Entered here: only categories, rules and boards; the rest is inherited |

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
* **Settings › Waiver Calculator** (`settings('waiver.waiver_mode')`, `settings('waiver.disclaimer')`): when several
  rules match — the highest (DIU) or added up to the smallest cap; disclaimer.

### Views (theme-overridable: `themes/{theme}/views/modules/waiver/…`)

| View | Variables |
|---|---|
| `calculator.page` | — (holds `<livewire:waiver.calculator />`) |
| `livewire.waiver-calculator` | see the component: lists, `$fields`, `$diploma`, `$answer`, `$tuition`, `$rule`, `$disclaimer` |
