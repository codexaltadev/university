# Legacy migration (DIU)

The data migration tool of the [Data Migration Plan](../../docs/09-Deployment/02-Data-Migration-Plan.md) §4:
reads the old DIU database (`project_diu`) and writes through the modules' models. It is not part of the
product — another university's installation does not use it.

```bash
php artisan legacy:migrate --all                 # every step below, in order
php artisan legacy:migrate --step=S04 --step=S06 # some steps
php artisan legacy:migrate --all --no-report     # without report files
```

Connection `legacy` (`config/database.php`): `LEGACY_DB_HOST`, `LEGACY_DB_PORT`, `LEGACY_DB_DATABASE`
(default `project_diu`), `LEGACY_DB_USERNAME`, `LEGACY_DB_PASSWORD` — use a `SELECT`-only user (M-1).

| Step | What | Notes |
|---|---|---|
| S02 | Program major categories → `program.major-category`; `program_types` sub-types → `program.tag` | Levels are seeded by the Program module (DB-03 §15.1) |
| S04 | `faculties`, `departments` | Only academic faculties: "Office" and "Institutional Programs" (`is_optional`) go to S10; deleted rows are skipped; old addresses kept (`/faculty/fsit`, `/department/cse`) |
| S06 | `department_program_type` + eligibilities, counters, majors, career paths, industries, facilitators, materials, memberships, variants | Level / tag by §15.1; credits and years parsed from `program_credit_duration` (the full text is archived in the report); several eligibility rows are joined and flagged; approved alumni / teachers → manual counters |
| S07 | `tuition_categories` → fee categories (code `local` / `international`, currency BDT / USD); `tuitions` → tuitions + lines (admission, 1st–4th year, per semester, other) | DIU's totals kept as entered (`totals_overridden`); max range only where the legacy calculator shows one (max total, max tuition and max credit); two tuitions of one program and category get the duration as label; `majors` text archived. Waiver rules follow in 3.4b |

* **Run again any time (M-3):** `migration_id_map` remembers legacy row → new record; a second run updates. Without a
  map row (a record entered by hand before the first run, a lost map) the record is found by its natural key —
  faculty / department short name, program web address, term name, sub-item program + title — and taken over, never
  duplicated; a record another legacy row already owns is not taken, so two rows with an equal title stay two.
* **Reports (M-7):** `reports/{step}-{time}.md` (not in git): source `COUNT(*)`, created, updated, skipped with
  the reason, manual review list, archived text.
* **Activity log** is off while it runs (M-5).
* **Not yet:** pictures and files (S03 media import — material files link to `webbackend.daffodilvarsity.edu.bd`
  meanwhile), users and scopes (S01), the "Legacy Import" user for `created_by` (M-8), the other steps.
