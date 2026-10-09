# Handoff — University Website Management System

Last updated: 2026-10-09. Read this first when continuing on another machine.

## Where we are (SDLC)

| Step | Document | Status |
|---|---|---|
| 1 | [BRD](01-BRD.md) (v1.7) | ✔ Approved |
| 2 | [SRS](02-SRS/00-SRS-Overview.md) (Part 0–5) | ✔ Approved |
| 3 | [SAD](03-SAD/01-SAD-PartA-Foundation.md) (Part A, B) | ✔ Approved |
| 4 | [Database Design](04-DB-Design/01-DB-Core-Kernel.md) (DB-01 to DB-06) | ✔ Approved |
| 5 | [UI/UX](05-UIUX/01-UIUX-PartA-Admin.md) (Part A, B) + prototype v3 | ✔ Approved |
| 6 | [API Specification](06-API/01-API-Specification.md) | ✔ Approved — contract for Release 2 (no API in Release 1) |
| 7 | Module & Theme Development Guide (Part A [Module](07-DevGuide/01-Module-Development-Guide.md), Part B [Theme](07-DevGuide/02-Theme-Development-Guide.md)) | ✔ Approved |
| 8 | [Test Plan](08-Test/01-Test-Plan.md) | ✔ Approved |
| 9 | Deployment & Data Migration Plan ([Deployment](09-Deployment/01-Deployment-Plan.md), [Data Migration](09-Deployment/02-Data-Migration-Plan.md)) | ✔ Approved |
| 10 | User Manual ([A Basics](10-User-Manual/01-Manual-PartA-Basics.md), [B Content & Microsite](10-User-Manual/02-Manual-PartB-Content-Microsite.md), [C Admin](10-User-Manual/03-Manual-PartC-Admin.md)) | ✔ Approved — screenshots added during development |

## Development

| Phase | Scope | Status |
|---|---|---|
| **0** | Project setup + Shield POC-1..4 | ✔ Done 2026-10-09 — 13 Pest tests pass; results in SAD Part A §7.4 "POC ফলাফল" |
| 1A | Data foundation: `BaseModel` / `BasePivot`, traits (SoftDeletes, HasActiveFlag, Blameable, LogsPlatformActivity, GuardsParentDelete, HasPublishDate, HasExpiry, HasSortOrder), `frontendVisible()`, `platformColumns()` + `active_key`, users / permissions / roles / activity_log per DB-01, `core_entity_permissions` | ✔ Done 2026-10-09 — 29 tests pass |
| 1B | Module system: `module.json` `uwms` block + validator, database activator, Extension registrar, `module:make-uwms`, Module Manager (install folder / ZIP, activate, deactivate, uninstall, rollback, operation log), ZIP security checks, conflict checks, kernel resource permissions | ✔ Done 2026-10-09 — 50 feature + 24 lifecycle tests pass; notes in SAD Part A §10.5 |
| 1C | Engines: morph map, hierarchy, Relation (P-01: Show in, main site, highlights, requests, context queries), Assignment + Role Catalog (P-02), `AppliesUserScope`; FAQ uses the Relation engine, Office is a relation target and assignable | ✔ Done 2026-10-09 — 67 feature + 24 lifecycle tests pass; notes in SAD Part B §5.5 |
| 1D | Slugs (`core_slugs`, `HasSlug`), microsite settings, `SiteContext`, `MicrositeResolver` (URL → context, old-slug redirect), `FallbackResolver` (own / inherit / main; stores registered by core modules); Office is a microsite entity | ✔ Done 2026-10-09 — 76 feature tests pass; notes in SAD Part B §6.2 |
| 1E | GitHub Actions (`ci.yml` every push: audit, Pint, build, architecture + feature tests; `nightly.yml`: lifecycle), architecture tests (R-1..R-5, D-1), reference-column index test (IX-2), lifecycle tests 8 min → ~1.5 min | ✔ Done 2026-10-09 — **Phase 1 (Kernel) complete**: 14 architecture + 77 feature + 24 lifecycle = 115 tests |
| 2.1 | Core modules `Modules/User` (C-02: users screen, invitations, scope, effective permissions, login lock 5 / 15 min, inactive sign-out, login / logout / failed log, 2FA with Super Admin requirement setting) and `Modules/Role` (C-03: role editor moved from `platform/`, system roles + default roles seeder) | ✔ Done 2026-10-09 — 144 tests pass (26 architecture, 83 feature, 11 module, 24 lifecycle) |
| 2.2 | Core module `Modules/ModuleManager` (C-01): System › Modules list with tabs, install from ZIP, activate / deactivate, uninstall keep / delete data (slug confirmation), details (README, permissions, dependencies, versions, operation log); Super Admin only, kept out of the role editor | ✔ Done 2026-10-09 — 161 tests pass (32 architecture, 83 feature, 20 module, 26 lifecycle) |
| 2.3 | Core module `Modules/ActivityLog` (C-04): System › Activity log with filters (who, module, event, log, period), detail page with old → new changes, CSV export of the filtered list, read-only for everyone (only View / ViewAny permissions); shared "Activity" tab on record edit pages (`Platform\Filament\RelationManagers\ActivityRelationManager`, on FAQ, Office, User); `uwms:activity:clean` daily 03:00, never below 60 days, logs its own run | ✔ Done 2026-10-09 — 175 tests pass (38 architecture, 83 feature, 28 module, 26 lifecycle) |
| 2.4 | Kernel `core_settings`, `core_languages`, `core_translations`; `settings()` helper, `Platform\Settings\ModuleSettings` (module tabs), `Platform\Localization\Languages` (default-language fallback), interface-text overrides loader, `Numerals`, `TranslatableTabs` form component. Core modules `Modules/Localization` (C-15: Site › Languages, Site › Interface texts, admin language from profile) and `Modules/Settings` (C-05: Site › Settings with General, Contact, Social, Media, Modules (hide on website), Mail + test email, Maintenance, Analytics & code (Super Admin), Admin branding; FAQ tab as module example). Deferred: default images and admin logos / favicon (with Media, 2.5), "copy website branding" (Theme), language switcher and `/bn/` URLs (frontend theme) | ✔ Done 2026-10-09 — 206 tests pass (50 architecture, 83 feature, 47 module, 26 lifecycle) |
| **2.5** | Taxonomy + Media → Show-in / main site / highlight form components → Pages, Menu, Header/Footer, Homepage, Statistics → Theme, SEO, Search, Notification, Backup | **Next** |
| 3–6 | Installable modules (Program, Fees & Waiver, Admission first; FAQ and Office completed here) → Default theme → Migration & system test → UAT & go-live | Pending |

Local dev (WSL Ubuntu 24.04): PHP 8.4, Composer 2.9, MySQL 8.0 (databases `university`, `university_test`), Redis 7, Node **26.11** via nvm (default; `.nvmrc` = 26). System `/usr/bin/node` is still 18 and non-interactive shells pick it up — scripts must load nvm first (`. ~/.nvm/nvm.sh && nvm use`). All Composer and npm packages were at their latest versions on 2026-10-09. Run commands via a script file: `wsl -d Ubuntu-24.04 -- bash script.sh` (inline `$VAR` gets mangled by PowerShell).

Working style agreed with the project owner: one SDLC document at a time, each confirmed before the next; DB design module by module; documents written in Bangla with English technical terms; the owner chats in Banglish.

## Key decisions (summary — details in BRD Section 12 "Decisions Log")

* Public website: **Laravel Blade theme + Livewire** (no Next.js). **No REST API in Release 1**; API Spec is the Release 2 contract; API docs not public (BRD Q-28, SAD ADR-18).
* Stack: Laravel 13, Filament 5, nwidart/laravel-modules, **Filament Shield** (roles / permissions) + separate Platform Scope engine (data scope), PHP 8.4, MySQL 8.
* 58 modules = 20 Core + 38 Installable (M-14 number left empty after merging Administration + Support Staff into **Administration**).
* Every business table: soft delete + `is_active` (+ generated `active_key` for unique indexes). Pivot / log / system tables excluded. Activity log ≥ 60 days.
* Publishing = `is_active` + `published_at` only (future date = scheduled). No status column, no approval workflow.
* Main site publishing is an Admin-only decision (`core_relations` row `site:0`); entity users can only *request* it. Highlight is separate, per context, with expiry.
* Microsites for Faculty / Department / Program / Office / Lab with hierarchical fallback (own → parent → main site) for menu, header, footer, template, homepage, statistics.
* Person Source + Assignment (one record per person, roles via `core_assignments`), with a **Standard Role Catalog** (fixed keys like `dean`, `head`, `officer`; universities only relabel). Reusability for other universities is a design goal — prefer fixed keys + configurable labels.
* Program levels: Undergraduate, Graduate, Diploma, Post Graduate Diploma.
* Multi-language, WordPress-like Media Library, Statistics per entity, Content Update Reminder.
* Admin panel: neutral default theme; each university can set its own logo / colors / login page.
* The legacy DB `project_diu` (MySQL, credentials in `/home/Project/diu/.env`) is **reference only** — do not mirror its structure. Use `SELECT COUNT(*)` for any row counts (information_schema estimates were wrong).

## Branding (DIU, from the live site daffodilvarsity.edu.bd)

Blue `#034EA2`, navy `#002652`, green `#39B54A`, surface `#F3F4F9`; fonts Montserrat (headings) + Manrope (body) + Hind Siliguri (Bangla); blue→navy gradient buttons.

DIU public API used for real prototype data: `https://webbackend.daffodilvarsity.edu.bd/api/v1/public` (`academic/faculties`, `department/{slug}`, `program/{slug}`).

## Prototype

* Live (owner's claude.ai account): https://claude.ai/artifact/LYk8PswwPuktGYkXwFHLh5 — 6 linked screens: Homepage, Faculties, FSIT faculty, CSE department, MBA program, Admin notice form.
* Source copy: [prototype/project/](prototype/project/) (`*.dc.html` + `canvas.json`) and images in [prototype/assets/](prototype/assets/). Image URLs in the HTML (`/_blob/...`) point to assets stored in the artifact.

## Open questions for DIU (needed before data migration)

* DBQ-04: who reviews the person-merge sheet (duplicate staff records)?
* DBQ-05: two `country_id`s used by `international_linkages` are missing from `countries` (81 vs 79).
* DBQ-06: meaning of the 4 `mps_categories`.
* DMQ-01 resolved: legacy uploaded files are public at `https://webbackend.daffodilvarsity.edu.bd/{path}` (DB stores relative paths like `noticeFile/…`, `images/faculty/…`) — migration downloads over HTTPS, no server access needed; **not now** — files are downloaded later (before DR-2), development uses placeholders. DMQ-02 resolved: hosting is **AWS** (proposal: ap-south-1, EC2 + S3/CloudFront + SES, backups in another region). Open: DMQ-03 email (SES proposed), DMQ-07 MySQL on EC2 vs RDS, DMQ-04 SSL, DMQ-05 go-live month (outside admission season), DMQ-06 legacy admin freeze window.
* Test Plan TPQ-03: legacy site Analytics export (top URLs). (TPQ-01 resolved: legacy waiver calculator is the golden-master oracle; TPQ-02 resolved: UAT with FSIT, CSE, BBA, Registrar.)
* Most-visited pages (DIU): Admission, Waiver Calculator, Programs, Scholarship — highest test / performance priority (Test Plan §7.2).
* ~~SAD POC-1 to 4~~ ✔ done 2026-10-09 (Phase 0). Original item: verify Filament Shield integration (per-module permission generation, scoped policies, entity-permission tab, inactive-module permissions).

## Next step

All 10 SDLC documents are approved; Phase 0, Phase 1 (kernel) and Phase 2.1–2.4 are done. Next: **Phase 2.5 — Taxonomy + Media** (see Development table above). The owner wants steps followed in order and each confirmed before the next.

Repository: private GitHub `codexaltadev/university` (branch `main`); commit as `codexalta <codexalta@gmail.com>`, push via SSH alias `git@github-codexalta:codexaltadev/university.git`; no AI attribution lines in commits. Filament resources follow the v5 default layout: `Resources/{PluralModel}/{Model}Resource.php` with `Pages/`, `Schemas/{Model}Form.php`, `Tables/{PluralModel}Table.php`.
