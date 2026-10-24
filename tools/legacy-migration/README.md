# Legacy migration (DIU)

The data migration tool of the [Data Migration Plan](../../docs/09-Deployment/02-Data-Migration-Plan.md) §4:
reads the old DIU database (`project_diu`) and writes through the modules' models. It is not part of the
product — another university's installation does not use it.

```bash
php artisan legacy:migrate --all                 # every step below, in order
php artisan legacy:migrate --step=S04 --step=S06 # some steps
php artisan legacy:migrate --all --no-report     # without report files
```

**Without the legacy database — the seed files (`seed/`, owner 2026-10-17):**

```bash
php artisan legacy:export-seed                   # where project_diu is at hand: write seed/ (read only)
php artisan db:seed --class=DiuContentSeeder     # anywhere: every step, reading the seed
php artisan legacy:migrate --step=S16E --from-seed
```

`seed/schema.sql` holds each table the steps read as MySQL created it (no foreign keys), `seed/{table}.jsonl.gz`
its rows (first line the columns, then one JSON list per row, primary key order; an unchanged table gives the same
file). `--from-seed` loads them into a MySQL database of their own, `diu_legacy_seed` (dropped and made again each
time, with the application's database user — never `project_diu`), and points the `legacy` connection at it, so the
steps and their results are the same. Personal data stays out of git (`Support\Seed`): `newsletters`,
`admission_subscribers`, `admission_results` are structure only, the alumni's email, phone, student ID and office
address are empty. A new step that reads another table adds it to `Seed::TABLES` and exports again.

Connection `legacy` (`config/database.php`): `LEGACY_DB_HOST`, `LEGACY_DB_PORT`, `LEGACY_DB_DATABASE`
(default `project_diu`), `LEGACY_DB_USERNAME`, `LEGACY_DB_PASSWORD` — use a `SELECT`-only user (M-1).

| Step | What | Notes |
|---|---|---|
| S02 | Program major categories → `program.major-category`; `program_types` sub-types → `program.tag` | Levels are seeded by the Program module (DB-03 §15.1) |
| S04 | `faculties`, `departments` | Only academic faculties: "Office" and "Institutional Programs" (`is_optional`) go to S10; deleted rows are skipped; old addresses kept (`/faculty/fsit`, `/department/cse`) |
| S03 | Files the migrated rows point to → Media Library (groups: faculty, department, program, facilities, accreditation, testimonials) | `--download-files` fetches what the cache (`LEGACY_FILES_PATH`, default `storage/app/legacy-files`) lacks from webbackend (4 at a time, 3 tries, `storage/` tried after a 404); each file once (id map `files`: path and content hash), folder "Legacy import / {table}"; a picture chosen by hand stays; photos over the 10 MB limit become 2400 px JPEGs; `--only=program` limits the groups; run the steps that read the `files` map (S17D, S17G, S17A, S17S, S18T) again after it; then `php artisan queue:work --stop-when-empty` for the image sizes |
| S15A | `articles` (main site) → Pages under a page "article" (`/article/{slug}` kept) | text in an HTML block (old Bootstrap markup kept), relative pictures / file links → webbackend (`Normalizer::legacyHtml`, also S15D); unpublished stay off; safe to run again |
| S06 | `department_program_type` + eligibilities, counters, majors, career paths, industries, facilitators, materials, memberships, variants | Level / tag by §15.1; credits and years parsed from `program_credit_duration` (the full text kept as `credit_duration`); every section intro kept; several eligibility rows are joined and flagged; approved alumni / teachers → manual counters |
| S07 | `tuition_categories` → fee categories (code `local` / `international`, currency BDT / USD); `tuitions` → tuitions + lines (admission, 1st–4th year, per semester, other) | DIU's totals kept as entered (`totals_overridden`); max range only where the legacy calculator shows one (max total, max tuition and max credit); two tuitions of one program and category get the duration as label; `majors` text archived. Waiver part: boards, waiver categories (+ includes from the `waiver_categories` JSON; category 18 → diploma result), waiver rules (international CGPA → `ug_cgpa_*` of International rules; `max_waiver_rate` → improved % only on rules of categories 19 / 21, where the legacy code applies it), pivots in their legacy order; `faculty_waiver` not migrated (DB-03 v1.1 #6) |

**Golden master** (`golden/`): `waiver-golden.php` runs inside the legacy app and records its calculator's answers;
`legacy-snapshot.php` writes the small legacy copy CI needs. Regenerate both from the same `project_diu`.

* **Run again any time (M-3):** `migration_id_map` remembers legacy row → new record; a second run updates. Without a
  map row (a record entered by hand before the first run, a lost map) the record is found by its natural key —
  faculty / department short name, program web address, term name, sub-item program + title — and taken over, never
  duplicated; a record another legacy row already owns is not taken, so two rows with an equal title stay two.
* **Reports (M-7):** `reports/{step}-{time}.md` (not in git): source `COUNT(*)`, created, updated, skipped with
  the reason, manual review list, archived text.
* **Activity log** is off while it runs (M-5).
* **Not yet:** pictures and files (S03 media import — material files link to `webbackend.daffodilvarsity.edu.bd`
  meanwhile), users and scopes (S01), the "Legacy Import" user for `created_by` (M-8), the other steps.
