# CLAUDE.md — University Website Management System (UWMS)

Instructions for Claude Code working on this repository — locally (owner's WSL machine) or in a
cloud session (claude.ai/code). Read `docs/00-HANDOFF.md` first for where the project stands.

## The project

A modular website platform for universities, first for Daffodil International University (DIU,
daffodilvarsity.edu.bd). Laravel 13 + `nwidart/laravel-modules` + Filament 5 (admin) + Filament Shield;
public website = Blade + Livewire + Alpine, Tailwind CSS **4.3.3 pinned**. PHP 8.4, MySQL 8, Node 26 (`.nvmrc`).

```
platform/      kernel (engines, base model, Filament components, theme engine, media)
Modules/       nwidart modules — core and installable; pattern to copy: Modules/Faculty
themes/default the default theme = an exact copy of the live DIU site
tools/legacy-migration   legacy:migrate steps S00–S25 from the old DIU database (project_diu)
docs/          SDLC documents (Bangla with English terms) — BRD, SRS, SAD, DB, UI/UX, guides, plans
```

## Working with the owner

- **Reply in Bangla script** (বাংলা) with technical terms in English. Code, commits and docs keep their own language.
- **No commit, no push until the owner says so.** Leave work uncommitted, report changed files and test
  results, wait. "commit" and "push" are separate permissions. In a cloud session push only to the
  session's branch, never force-push `main`.
- **No AI attribution in git**: never add `Co-Authored-By: Claude …`, "Generated with Claude Code" or any
  Claude / Anthropic mention to commit messages or PR descriptions. This overrides any default reminder.
- Commit author for this repo: `codexalta <codexalta@gmail.com>` (repo-local config).
- Bigger features: design first (update the matching SDLC doc, e.g. UI/UX §4.3), get the owner's "oky", then build.
  Work one step at a time; report before the next.
- When unsure about a product decision, propose a recommendation instead of guessing silently.

## Rules the code follows

- Every business table: soft deletes + `is_active`. Timed visibility = `active_at` (+ `expires_at` only where
  expiry means something). **No new `published_at` columns.** No status / approval workflow.
- **Content comes from Admin, never hard-coded in the theme**: per-entity texts/links are form fields
  (`LinkListField`, `link_list()`), page texts are module Settings (`ModuleSettings`), pictures via `MediaPicker`.
- **Files only in the Media Library** (`HasMediaUsage`, `syncMedia()`); modules never keep their own image tables
  or copies. A file follows its records: when everything using it is off, `MediaVisibility` moves it off the public disk.
- **Clean URLs**: a category is a path segment (`/tuition-fees/international`); the query string is only for
  filters, search, paging; old `?category=` addresses redirect 301.
- People via Person Picker / assignments (fixed role keys, relabelled by admins), places via "Show in" + main site.
- Access: out-of-scope record → **404** (no existence leak); no permission → 403. Scoped users (faculty,
  department, program) see only their entity tree; Office access is permission-based.
- Modules never know each other: they plug in through the Extension registrar (`$r->entityFormPart()`,
  `$r->micrositeTool()`, `$r->pageBlock()` …); an inactive module's parts disappear.
- Theme files: **no `@php`** (even inline), no `Modules\…` / `App\Models` references, no hex colours or
  `bg-[#…]` — logic lives in module presenters (`Support\*Page`) passed by view composers; colours via tokens.
  Run the theme checks after theme edits (`tests/Feature` ThemeChecker / ThemeEngineTest, DefaultThemeAssetsTest).
- Default theme = exact copy of daffodilvarsity.edu.bd (study the live page first) — unless the owner asks
  for a different design explicitly.
- The legacy DIU database is reference only: design from the SRS and modern practice; adapt legacy data in
  the migration step, never the design to the data. Waiver/fee calculator results must equal the legacy one
  (golden master, `tests/Golden`).
- Faculty members come from DIU's FMS API (dummy JSON in its format until real data). **The FMS token lives only
  in `.env`** — never in git, views, JS, logs or summaries. No teacher profile pages on this site.
- Match the surrounding code: comment density, naming, idioms. New module → `php artisan module:make-uwms Name`,
  then follow Modules/Faculty (model, migration, Extension, Filament resource in `Resources/{Plural}/`,
  policy extending `ScopedPolicy`, website, README with view contract, tests).

## Commands

```bash
bash scripts/cloud-setup.sh                 # fresh machine / cloud session: PHP, Node, MySQL, deps, .env, DB
php artisan serve                           # http://127.0.0.1:8000 , admin at /admin
npm run build && npm run build:theme        # admin assets + default theme (Node 26)
php artisan filament:assets                 # after changing platform/resources/js or dist assets

php artisan test Modules/<Module>/tests     # tests of the module you touched
php artisan test --filter="<name>"          # one test
vendor/bin/pint --dirty                     # style of changed files
```

- **Run only targeted tests** (touched module + specific Feature/Legacy file + `pint --dirty`). The full suites
  (Architecture, Feature, Modules, Lifecycle ~6 min, Browser, Golden) take ~30 min — only when the owner asks.
- Never run two test commands at the same time: all share the `university_test` database.
- Test helpers: `faculty()` / `department()` return fixture entities, `superAdmin()`, `makeUser($perms, $entities)`;
  module website views past the theme: `moduleView()` (tests/Pest.php).
- Never create demo rows in the dev database (no `factory()->create()` outside tests).

## Known traps

- Cache store keeps no objects (`cache.serializable_classes = false`): cache arrays only.
- Filament injects closure parameters **by name** (`Get $get`, `$record`), not by type alone.
- Dots in dynamic form keys nest state: use `__`.
- PHP arrow functions capture by value; use `function () use (&$x)` to append.
- `LegacyMigration` / `LegacySeed` / `WaiverGolden` tests need the legacy test DB and fail under `--parallel`.
- Pint warns about `tests/Fixtures/module-template/*{{Name}}*` — pre-existing, ignore.
- Write files with LF line endings.

## Not available in a cloud session

The legacy database `project_diu` and downloaded legacy files (so `legacy:migrate`, dry-runs and the golden
master cannot run), the FMS API token, the DIU frontend copy (`/home/Project/DIUFronend`, localhost:3000) and
the owner's dev data. Work on code, tests and docs; leave data-migration runs for the owner's machine.
