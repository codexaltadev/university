# Handoff — University Website Management System

Last updated: 2026-10-08. Read this first when continuing on another machine.

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
| 9 | **Deployment & Data Migration Plan** ([Deployment](09-Deployment/01-Deployment-Plan.md), [Data Migration](09-Deployment/02-Data-Migration-Plan.md)) | Drafted — awaiting confirmation |
| 10 | User Manual | Pending |

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
* SAD POC-1 to 4 (Test Plan §5, Phase 0): verify Filament Shield integration (per-module permission generation, scoped policies, entity-permission tab, inactive-module permissions).

## Next step

Get the owner's confirmation on step 9 (**[Deployment](09-Deployment/01-Deployment-Plan.md)** + **[Data Migration](09-Deployment/02-Data-Migration-Plan.md)**; open questions DMQ-01..06). Then step 10 **User Manual (Admin)**.

Repository: private GitHub `codexaltadev/university` (branch `main`); commit as `codexalta <codexalta@gmail.com>`, push via SSH alias `git@github-codexalta:codexaltadev/university.git`; no AI attribution lines in commits. Filament resources follow the v5 default layout: `Resources/{PluralModel}/{Model}Resource.php` with `Pages/`, `Schemas/{Model}Form.php`, `Tables/{PluralModel}Table.php`.
