# Test Plan ও Test Cases

| বিষয় | তথ্য |
|---|---|
| Document | Test Plan (SDLC ধাপ ৮) |
| Version | 1.0 |
| Date | 2026-10-08 |
| Status | ✔ Approved |
| Based on | BRD v1.8 (§11 Success Criteria), SRS (FR-GEN, NFR, প্রতিটি module-এর AC), SAD Part A §7.4 (POC), SAD Part B §14.4 (CI), DB-01 থেকে DB-06, UI/UX Part B §8, Development Guide Part A §14 / Part B §13 |
| পাঠক | Developer, QA, Project Owner (UAT sign-off) |

## সূচি

1. [উদ্দেশ্য ও পরিধি](#1-উদ্দেশ্য-ও-পরিধি)
2. [Test স্তর ও ধরন](#2-test-স্তর-ও-ধরন)
3. [Environment ও test data](#3-environment-ও-test-data)
4. [Entry / exit criteria ও defect severity](#4-entry--exit-criteria-ও-defect-severity)
5. [Phase 0 — Architecture POC](#5-phase-0--architecture-poc)
6. [Cross-cutting test case](#6-cross-cutting-test-case)
7. [Module test — SRS acceptance criteria](#7-module-test--srs-acceptance-criteria)
8. [Non-functional test](#8-non-functional-test)
9. [Data migration test](#9-data-migration-test)
10. [User Acceptance Test (UAT)](#10-user-acceptance-test-uat)
11. [BRD Success Criteria → Test](#11-brd-success-criteria--test)
12. [সময়সূচি](#12-সময়সূচি)
13. [Tool](#13-tool)
14. [ঝুঁকি](#14-ঝুঁকি)

---

## 1. উদ্দেশ্য ও পরিধি

**উদ্দেশ্য:** যাচাই করা যে system SRS-এর requirement পূরণ করে, module install / deactivate করলে কিছু ভাঙে না, scoped user নিজের সীমার বাইরে যেতে পারে না, এবং `project_diu` থেকে data সঠিকভাবে আসে।

| পরিধির মধ্যে | পরিধির বাইরে (Release 1) |
|---|---|
| Kernel, 20 Core module, 38 installable module (default inactive Publications ও Research সহ) | REST API (Release 2 — API Spec-এর contract test তখন) |
| Admin Panel (Filament), public website (Blade + Livewire), default theme | Third-party module / theme (তারা নিজেরা Dev Guide-এর checklist দিয়ে যাচাই করবে; আমরা install validator test করি) |
| Module ও theme lifecycle, ZIP validator | Hosting provider-এর uptime (NFR-REL-01) — monitoring দিয়ে, test দিয়ে নয় |
| `project_diu` → নতুন system data migration | Legacy webbackend API-র consumer (APIQ-04, পরে) |

---

## 2. Test স্তর ও ধরন

| স্তর | কী যাচাই | Tool | কে লেখে | কখন চলে |
|---|---|---|---|---|
| **Unit** | Trait, service, resolver, metric, helper — DB ছাড়া বা in-memory | Pest | Developer | প্রতিটি push |
| **Feature** | HTTP / Livewire / Filament action, policy, query — test DB সহ | Pest + Livewire / Filament testing helper | Developer | প্রতিটি push |
| **Architecture** | D-1 থেকে D-6 dependency rule, R-1 থেকে R-5 (Module Guide), naming | Pest `arch()` | Kernel team | প্রতিটি push |
| **Schema** | প্রতিটি business table-এ `platformColumns()`; FK না থাকা reference column-এ index (IX-1 থেকে IX-11); unique-এ `active_key` | Custom Pest test (`information_schema` পড়ে) | Kernel team | প্রতিটি push |
| **Module lifecycle** | প্রতিটি bundled module: install → activate → deactivate → reactivate → uninstall (keep / drop data) | `ModuleLifecycle` helper | Module owner | প্রতিটি push |
| **Theme check** | Theme Guide T-1 থেকে T-6 + render matrix | `uwms:theme:check` | Theme owner | প্রতিটি push (default theme) |
| **Browser (E2E)** | মূল user journey, JS / Alpine interaction, mobile menu | Pest Browser plugin (Playwright) | QA | Nightly + release candidate |
| **Accessibility** | WCAG 2.1 AA | axe-core (Browser test-এর ভেতরে) | QA | Nightly |
| **Performance** | TTFB, Lighthouse, load | Lighthouse CI, k6 | QA / DevOps | Release candidate (staging) |
| **Security** | Dependency, header, OWASP baseline, permission matrix | `composer audit`, `npm audit`, OWASP ZAP baseline, Feature test | DevOps / QA | প্রতিটি push (audit), RC (ZAP) |
| **Migration** | Row count, field mapping, URL redirect | Migration verification command + manual sample | Migration owner | প্রতিটি dry-run |
| **UAT** | Business scenario, ব্যবহারযোগ্যতা | Manual, scripted scenario | DIU stakeholder | Go-live-এর আগে |

**Coverage লক্ষ্য:** Kernel ও Core module ≥ ৭০% line coverage (NFR-MNT-03); installable module-এর জন্য সংখ্যা নয়, বরং তিন বাধ্যতামূলক test (lifecycle, visibility, scope — Module Guide §14) + প্রতিটি SRS AC।

---

## 3. Environment ও test data

| Environment | Data | ব্যবহার |
|---|---|---|
| Local / CI | Factory + `TestUniversitySeeder` (কাল্পনিক: ৩ faculty, ৬ department, ১২ program, ২ office, ১ lab, প্রতিটি role-এর user) | Unit, feature, lifecycle, browser |
| Staging | Migration dry-run-এর data (`project_diu`-এর কপি থেকে) — **ব্যক্তিগত তথ্য mask করা** (email, phone, applicant serial) | Performance, UAT, migration verification |
| Production | — | শুধু go-live smoke test (Section 10.4) |

**Standard test user (`TestUniversitySeeder`):**

| User | Role | Scope |
|---|---|---|
| `super@test` | Super Admin | — |
| `admin@test` | Admin | — (PublishMain সহ) |
| `fsit@test` | Faculty Editor | `faculty:FSIT` |
| `cse@test` | Department Editor | `department:CSE` |
| `eee@test` | Department Editor | `department:EEE` |
| `bsc-cse@test` | Program Editor | `program:BSC-CSE` |
| `registrar@test` | Office Editor | `Manage:Office#registrar` (dynamic permission) |
| `viewer@test` | Viewer | শুধু `ViewAny:*` |

---

## 4. Entry / exit criteria ও defect severity

### 4.1 Defect severity

| Severity | সংজ্ঞা | উদাহরণ | Go-live-এ অনুমোদিত? |
|---|---|---|---|
| **S1 Critical** | Data হারানো, security breach, site down | Scoped user অন্য department-এর record মুছতে পারে; uninstall-এ অন্য module-এর table drop | না |
| **S2 Major** | মূল feature কাজ করে না, workaround নেই | Notice publish হয় না; menu fallback ভুল | না |
| **S3 Minor** | Feature কাজ করে, কিন্তু ভুল / অসুবিধাজনক; workaround আছে | Sort order ভুল; বাংলা label অনুপস্থিত | সংখ্যা ≤ ১০, প্রতিটির পরিকল্পনা সহ |
| **S4 Cosmetic** | চেহারা | Spacing, icon | হ্যাঁ |

### 4.2 Entry / exit

| Phase | Entry | Exit |
|---|---|---|
| Phase 0 POC | SAD approved | POC-1 থেকে 4 pass, বা বিকল্প নকশা approved |
| প্রতিটি development phase | আগের phase-এর exit | CI সবুজ; phase-এর সব module-এর AC test pass; S1 / S2 শূন্য |
| System test (staging) | সব module feature-complete; migration dry-run ১ সম্পন্ন | Section 6, 8, 9-এর সব test pass |
| UAT | System test exit | Section 10-এর scenario pass; Project Owner sign-off |
| Go-live | UAT sign-off; final migration verification pass; backup restore পরীক্ষিত | Production smoke pass |

---

## 5. Phase 0 — Architecture POC

Development শুরুর আগে Shield + Scope সংযোগের ঝুঁকি যাচাই (SAD Part A §7.4)। একটি ছোট sandbox app-এ Kernel-এর ন্যূনতম অংশ + দুটি sample module (FAQ, Office)।

| ID | যাচাই | ধাপ | প্রত্যাশিত ফল |
|---|---|---|---|
| POC-1 | Module activate-এ শুধু সেই module-এর Shield permission generate | FAQ activate → `permissions` table দেখা | শুধু `*:Faq` যুক্ত; অন্য module-এর permission-এর সংখ্যা ও id অপরিবর্তিত |
| POC-2 | Generate করা policy `ScopedPolicy` extend করে | `cse@test` দিয়ে EEE-এর FAQ edit URL | 403; নিজের CSE FAQ-এ 200 |
| POC-3 | Dynamic entity permission Role UI-তে আলাদা tab | Office "Registrar" তৈরি → Role edit | "Entities" tab-এ "Manage: Registrar Office" checkbox; save-এ `Manage:Office#…` assign |
| POC-4 | Inactive module-এর permission Role UI-তে আলাদা | FAQ deactivate → Role edit | FAQ permission ধূসর / "Inactive modules" অংশে; assign থাকা permission মুছে যায় না |

**ফলাফল (2026-10-09): ✔ চারটিই pass** — `tests/Feature/Poc/ShieldScopePocTest.php`, ১৩টি test (POC-4-এর একটি control test সহ)। বিস্তারিত ও POC-4-এ পাওয়া ঝুঁকির সমাধান: SAD Part A §7.4 "POC ফলাফল"।

POC fail হলে: SAD ADR-04-এর বিকল্প (নিজস্ব role editor) মূল্যায়ন করে SAD update, তারপর development।

---

## 6. Cross-cutting test case

সব module-এ প্রযোজ্য আচরণ। প্রতিটি **automated** (Pest feature বা browser), যদি না "Manual" লেখা থাকে। Kernel-এর test suite-এ থাকে; FAQ ও News module "reference module" হিসেবে ব্যবহার হয়।

### 6.1 Module lifecycle ও validator (TC-MOD)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-MOD-01 | বৈধ module ZIP install | Backup, table তৈরি, permission register, seeder চলে; status সবসময় "Installed (Inactive)" (bundled module-এ `default_active` অনুযায়ী) | FR-MODM-02, 05, 16 |
| TC-MOD-02 | ZIP-এ `module.json` নেই / ভুল schema | Install বাতিল, কোন rule fail করেছে তার তালিকা, কোনো file লেখা হয়নি | FR-MODM-03, 04 |
| TC-MOD-03 | ZIP-এ path traversal (`../`), symlink, `.env`, `vendor/` | বাতিল | FR-MODM-03, NFR-SEC |
| TC-MOD-04 | Slug, table prefix, morph alias বা Resource class নাম অন্য module-এর সাথে মেলে | বাতিল, সংঘর্ষের নাম দেখায় | R-1, SAD §7.4 |
| TC-MOD-05 | `requires_core` বর্তমান core-এর সাথে মেলে না | বাতিল | FR-MODM-03 |
| TC-MOD-06 | Hard dependency inactive / নেই | Activate বাতিল, কোন module লাগবে তা দেখায় | FR-MODM-07 |
| TC-MOD-07 | যে module-এর উপর অন্য active module নির্ভর করে, তাকে deactivate | বাতিল, নির্ভরশীল module-এর তালিকা | FR-MODM-08 |
| TC-MOD-08 | Migration মাঝপথে fail | আগের অবস্থায় rollback; table / file অবশিষ্ট নেই | FR-MODM-06, NFR-REL-03 |
| TC-MOD-09 | Deactivate | Admin menu, route, frontend section, menu item, homepage section, stat item, search result, sitemap থেকে উধাও; **data, permission, settings অক্ষত** | FR-MODM-09, FR-MENU-11, FR-HOME-09, FR-STAT-04 |
| TC-MOD-09a | Optional dependency inactive (যেমন Alumni ছাড়া Program) | Module চলে, শুধু সংশ্লিষ্ট feature লুকানো | FR-MODM-13 |
| TC-MOD-10 | Reactivate | সব data, relation, highlight, menu item আগের মতো ফিরে আসে | BRD §11 |
| TC-MOD-11 | Uninstall (keep data) / (drop data, slug টাইপ করে confirm); active module uninstall চেষ্টা | Active হলে বাতিল। Keep: table থাকে, code যায়; Drop: শুধু নিজের prefix-এর table, নিজের permission ও নিজের `core_*` row (relation, slug, media usage) মুছে যায় — অন্য কিছু নয় | FR-MODM-11, FR-ROLE-02 |
| TC-MOD-12 | Update ZIP (নতুন version) | নতুন migration চলে, data অক্ষত; downgrade বাতিল | FR-MODM-10 |
| TC-MOD-13 | Inactive module-এর permission | Shield-এ থাকে, Role UI-তে আলাদা (POC-4) | SAD §7.4 |
| TC-MOD-14 | Module activate-এর পর একাধিক server | সব server-এ route পাওয়া যায় (OPcache reset broadcast) — *Manual, staging scaled topology থাকলে* | ADR-12 |

### 6.2 Permission ও scope (TC-PERM)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-PERM-01 | `cse@test` Notice list | শুধু CSE ও CSE-এর program-এর notice | BR-SEC-04, FR-GEN-52 |
| TC-PERM-02 | `fsit@test` Notice list | FSIT, তার সব department ও program-এর notice (hierarchical) | BRD Q-12 |
| TC-PERM-03 | `cse@test` EEE-এর record-এর edit / delete / restore URL সরাসরি | Panel-এ 404 (`AppliesUserScope` record-টি query থেকেই বাদ দেয়, অস্তিত্বও প্রকাশ পায় না); policy-স্তরে `can()` = false (UI-তে লুকানো যথেষ্ট নয়) | FR-GEN-52, BRD §11 |
| TC-PERM-04 | `cse@test` নতুন record তৈরি | "Show in"-এ শুধু নিজের scope-এর entity, default নিজের entity; scope-এর বাইরের id POST করলে validation error | FR-GEN-51, SAD Part B §5.4 |
| TC-PERM-05 | `cse@test` "Main site"-এ publish | Switch নেই; শুধু "Request main site" (P-23) | Q-26, FR-CNT-H09 |
| TC-PERM-06 | `admin@test` request approve | `site:0` relation তৈরি, request "approved", requester notification | FR-CNT-H09 |
| TC-PERM-07 | `registrar@test` | শুধু Registrar Office ও তার content; অন্য office-এ 403; নতুন content automatically Registrar-এ যুক্ত | BR-SEC-05, FR-OFF-12 |
| TC-PERM-08 | নতুন Office তৈরি | Code ছাড়া `Manage:Office#{id}` permission তৈরি | BRD §11 |
| TC-PERM-09 | Admin global search, Media Library (P-22), dashboard widget, export, bulk action, notification recipient | Scope-এর বাইরের record কোথাও নেই; CSE-এর inquiry শুধু CSE user পায় | FR-SRCH-07, FR-NOTI-07, FR-INQ-08 |
| TC-PERM-11 | Permission নেই এমন action | Button ও menu দেখায় না | FR-GEN-82 |
| TC-PERM-12 | User deactivate | Login বাতিল; চলমান session সাথে সাথে শেষ | FR-USER-05 |
| TC-PERM-10 | **Permission matrix** — প্রতিটি resource × প্রতিটি standard user × {view, create, update, delete, restore, force delete} | প্রত্যাশিত টেবিলের সাথে মেলে (data-driven test) | SAD §7.4 |

### 6.3 Publishing ও visibility (TC-PUB)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-PUB-01 | `is_active = false` | Frontend-এ নেই (list, detail 404, search, sitemap, homepage, stat) | FR-GEN-12, FR-SRCH-03 |
| TC-PUB-02 | `published_at` ভবিষ্যতে | ঐ সময়ের আগে নেই, পরে automatically দেখায় (cache সহ) | BR-DATA-04, Q-27 |
| TC-PUB-03 | `expires_at` পেরিয়েছে (Notice / Slider / Announcement) | উধাও | FR-GEN-12 |
| TC-PUB-04 | Soft delete | Frontend-এ নেই; Trash-এ আছে; restore (bulk সহ) করলে ফিরে আসে | FR-GEN-01, 02 |
| TC-PUB-05 | Parent delete যখন child active (Faculty → Department); parent trash-এ থাকা অবস্থায় child restore | সতর্কতা ও child-এর তালিকা; restore-এ parent সম্পর্কে সতর্কতা | FR-GEN-07, 08 |
| TC-PUB-06 | Unique field (যেমন program code) — soft-delete করা record-এর একই code দিয়ে নতুন | অনুমোদিত (`active_key`); দুটি active একই code — বাতিল | IX-8 |
| TC-PUB-07 | Slug পরিবর্তন | পুরনো URL → নতুন URL 301 | AC-SEO-01 |

### 6.4 Relation, highlight, main site (TC-REL)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-REL-01 | News "Show in: CSE" | CSE microsite-এর news-এ আছে; main site news-এ **নেই** | Q-26 |
| TC-REL-02 | Admin "Main site" চালু | Main site listing-এ আছে | Q-26 |
| TC-REL-03 | CSE context-এ highlight, `highlight_until` গতকাল | CSE homepage "Highlighted" section-এ নেই; listing-এ আছে | BR-CNT-07 |
| TC-REL-04 | Highlight আলাদা context-এ | CSE-তে highlight ≠ main site highlight (প্রতিটি আলাদা) | FR-CNT-H |
| TC-REL-05 | Homepage section source mode Latest / Highlighted / Highlighted-first / Manual | প্রতিটির ক্রম ও সংখ্যা ঠিক | FR-HOME-11 |
| TC-REL-06 | Target (Department) inactive | Relation থাকে, frontend-এ ঐ context দেখায় না | FR-REL-05 |
| TC-REL-07 | Microsite listing "descendants" | FSIT microsite news-এ CSE-এর news-ও (setting অনুযায়ী) | SAD Part B §3.3 |

### 6.5 Microsite ও fallback (TC-MS)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-MS-01 | CSE-এর নিজস্ব primary menu আছে | CSE-এ CSE menu | BRL-MENU-01 |
| TC-MS-02 | CSE menu মুছে ফেলা | FSIT menu; FSIT-এর না থাকলে main menu | BRD §11 |
| TC-MS-03 | CSE-এর primary আছে, footer নেই | Primary CSE-এর, footer parent / main-এর (location অনুযায়ী আলাদা) | SAD Part B §6.2 |
| TC-MS-04 | Fallback mode `own` / `main` | `own`: নিজের না থাকলে theme default; `main`: parent বাদ | SAD Part B §6.2 |
| TC-MS-05 | Header, footer, template, homepage, stat set, logo, banner | একই resolver — প্রতিটির জন্য TC-MS-01..03 data-driven | FR-HF-05, FR-STAT-08 |
| TC-MS-06 | নতুন Lab তৈরি | Code ছাড়া `/lab/{slug}` microsite, menu, permission | BRD §11 |
| TC-MS-07 | Fallback cache | Parent menu পরিবর্তন → child microsite-এ সাথে সাথে নতুন menu | NFR-PERF-04 |

### 6.6 Person assignment (TC-ASG)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-ASG-01 | একজন Faculty Member CSE-এর Head ও এক committee-র Chairperson | একই person record, দুটি assignment, দুই জায়গায় দেখায় | FR-REL-10 |
| TC-ASG-02 | Faculty Member-এর ছবি / পদবি update | সব assignment-এ নতুন তথ্য (copy নয়) | Person Source |
| TC-ASG-03 | Assignment `ends_on` পেরিয়েছে | Frontend-এ নেই, history-তে আছে | DB-01 |
| TC-ASG-04 | Role label পরিবর্তন ("Head" → "Chairman") | সব জায়গায় নতুন label; key `head` অপরিবর্তিত | FR-REL-11 |
| TC-ASG-05 | Person Source module (Alumni) inactive | তার assignment frontend-এ নেই; manual person দেখায় | D-rules |

### 6.7 Data lifecycle, log, trash (TC-DATA)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-DATA-01 | যেকোনো business record create / update / delete / restore / force delete / activate / deactivate | Activity log-এ user, action, module, শুধু পরিবর্তিত field-এর পুরনো ও নতুন মান, IP, সময়; password কখনো নয় | FR-GEN-90, 91, BR-LOG-04 |
| TC-DATA-01a | Login, failed login, module / theme / settings / role পরিবর্তন | Log-এ আছে | FR-LOG-02, 03 |
| TC-DATA-02 | Log cleanup job, retention ৬০ দিন | ৬১ দিনের পুরনো মুছে যায়, ৫৯ দিনের থাকে; cleanup নিজেও log হয়; setting ৬০-এর নিচে নামানো যায় না | FR-LOG-06, 07, BR-LOG-05 |
| TC-DATA-02a | Log edit / delete চেষ্টা (UI ও application) | সম্ভব নয় | FR-LOG-09 |
| TC-DATA-03 | Trash → restore → force delete | Force delete শুধু Super Admin, confirmation সহ; অন্য role-এ action নেই; force delete-এ media usage ও relation পরিষ্কার | FR-GEN-03, BR-DATA-02 |
| TC-DATA-04 | `created_by` / `updated_by` / `deleted_by` | সঠিক user id | FR-GEN-05 |
| TC-DATA-05 | Orphan-check job (FK নেই এমন reference) | Deleted target-এর relation report-এ আসে | SAD §6.6 |

### 6.8 Media Library (TC-MED)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-MED-01 | যেকোনো form থেকে upload | Media Library-তে gallery ও list view-এ দেখা যায় | BRD §11 |
| TC-MED-02 | একই file দ্বিতীয় form-এ picker দিয়ে ব্যবহার | পুনরায় upload নয়; usage-এ দুটি record | Q-18 |
| TC-MED-03 | ব্যবহৃত media delete | সতর্কতা ও usage তালিকা | FR-MED |
| TC-MED-04 | `.php`, `.phtml`, SVG-এ script, double extension upload | বাতিল / sanitize | NFR-SEC |
| TC-MED-05 | Image conversion (thumbnail, WebP) queue-তে | Upload response দ্রুত; conversion পরে তৈরি | NFR-PERF-06 |

### 6.9 Multi-language (TC-LANG)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-LANG-01 | বাংলা translation নেই এমন news `/bn/` এ | Fallback ভাষায় দেখায় (setting অনুযায়ী) বা তালিকা থেকে বাদ | BR-LANG |
| TC-LANG-02 | Translatable slug | `/en/news/x` ও `/bn/news/y` দুটোই কাজ করে, hreflang ঠিক | `core_slugs` |
| TC-LANG-03 | Admin translation override | Frontend string বদলায়, cache clear হয় | FR-LANG-03 |

### 6.10 Theme (TC-THM)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-THM-01 | Theme পরিবর্তন | সব page নতুন design; content অক্ষত | BRD §11, FR-THEME-11 |
| TC-THM-02 | নতুন theme-এ কোনো layout নেই | Module-এর default layout | FR-THEME-11 |
| TC-THM-03 | Admin primary color পরিবর্তন | নতুন hash-এর CSS; সব page-এ রং বদলায় | FR-THEME-07 |
| TC-THM-04 | Theme ZIP-এ `.php` (Blade ছাড়া) বা Blade-এ DB call | Install বাতিল | T-1 |
| TC-THM-05 | Preview | শুধু admin-এর session-এ; visitor পুরনো theme দেখে | FR-THEME-03 |
| TC-THM-06 | Active / default theme uninstall | বাতিল | BRL-THEME-01 |

### 6.11 Statistics ও Content Update Reminder (TC-STAT, TC-REM)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-STAT-01 | `program.count` @ FSIT | FSIT-এর সব department-এর visible program সংখ্যা | SAD Part B §7.2 |
| TC-STAT-02 | Override value দেওয়া | Override দেখায় | FR-STAT |
| TC-STAT-03 | Program inactive করা | Count কমে (cache invalidate) | FR-STAT-05 |
| TC-REM-01 | CSE-এর notice ৩০ দিন update হয়নি, rule ৩০ দিন | CSE editor-দের notification; একই কারণে বারবার নয় | FR-NOTI-09..16 |
| TC-REM-02 | নতুন notice publish | Reminder state reset | FR-NOTI |

### 6.12 Public form ও security (TC-SEC)

| ID | Scenario | প্রত্যাশিত ফল | Ref |
|---|---|---|---|
| TC-SEC-01 | Honeypot field পূরণ করা submit | নিঃশব্দে বাতিল (save নয়) | NFR-SEC-06 |
| TC-SEC-02 | এক IP থেকে rate limit-এর বেশি submit | 429 / বার্তা | NFR-SEC-06 |
| TC-SEC-03 | Rich text-এ `<script>`, `onerror=` | Sanitize — render-এ execute হয় না | NFR-SEC-05 |
| TC-SEC-04 | Login ৫ বার ভুল | ১৫ মিনিট lock | NFR-SEC-03 |
| TC-SEC-05 | Super Admin 2FA বাধ্যতামূলক setting চালু | 2FA ছাড়া panel-এ ঢোকা যায় না | NFR-SEC-04 |
| TC-SEC-06 | Response header | HSTS, X-Frame-Options, X-Content-Type-Options, CSP উপস্থিত | NFR-SEC-08 |
| TC-SEC-07 | Error page production-এ | Stack trace নেই | NFR-USE-03 |
| TC-SEC-08 | কোনো module-এর frontend section exception দেয় | Page বাকিটা render হয়, section লুকায়, error log হয় | NFR-REL-04 |

---

## 7. Module test — SRS acceptance criteria

SRS-এর প্রতিটি module-এ **Acceptance Criteria (AC)** আছে — মোট **৯০টি**:

| SRS Part | AC সংখ্যা |
|---|---|
| 01 Core (C-01 থেকে C-20) | 43 |
| 02 Academic | 17 |
| 03 People | 12 |
| 04 Content | 10 |
| 05 Services | 8 |
| **মোট** | **90** |

**নিয়ম:**

1. প্রতিটি AC-এর জন্য ন্যূনতম একটি automated test, নাম AC-এর ID দিয়ে, যাতে trace করা যায়:
   ```php
   it('AC-SEO-01: redirects the old slug to the new one with 301', function () { … });
   ```
2. CI একটি **traceability report** তৈরি করে: SRS থেকে সব `AC-*` ID বের করে, test নামের সাথে মিলিয়ে — test নেই এমন AC থাকলে build fail।
3. Browser-এ যাচাই করতে হয় এমন AC (layout, interaction) → Pest Browser test; বাকিগুলো feature test।
4. প্রতিটি module-এ আরও তিনটি বাধ্যতামূলক test (Module Guide §14): lifecycle (TC-MOD-01/09/10/11), visibility (TC-PUB-01..03), scope (TC-PERM-03)।
5. "Must" priority-র FR যার AC নেই, সেগুলোর জন্য module owner কমপক্ষে একটি feature test লিখবেন; "Should" / "Could" ঐচ্ছিক।

### 7.1 Interactive tool — বিশেষ test

এগুলোর ভুল সরাসরি ছাত্র বা অভিভাবকের সিদ্ধান্তে প্রভাব ফেলে, তাই data-driven test বাধ্যতামূলক:

| Tool | Test | Ref |
|---|---|---|
| Waiver Calculator | **Golden master (differential) test** — নিচে 7.1.1 | FR-FEE-06/08 |
| Admission Result | Selected / Waiting / Not selected / ভুল serial / captcha ভুল; rate limit | FR-ADM-06/07 |
| Tuition fee | Local / International, প্রতিটি fee category-র মোট | FR-FEE-04 |
| Transport route finder | Stop search, দিন অনুযায়ী schedule | FR-TRN-05/06 |
| Academic Calendar | Session / audience filter, `.ics` export বৈধ | FR-CAL-04/06 |

#### 7.1.1 Waiver Calculator — golden master

`project_diu`-এর বর্তমান waiver calculator live এবং সঠিকভাবে কাজ করছে (DIU নিশ্চিত, TPQ-01)। তাই সেটিই **সঠিক উত্তরের উৎস (oracle)**। নতুন calculator-কে একই input-এ হুবহু একই ফল দিতে হবে।

| ধাপ | কাজ |
|---|---|
| 1 | Legacy app (`/home/Project/diu`) local-এ, migration-এর **একই** `project_diu` snapshot-এর উপর চালানো — live server-এ নয় |
| 2 | Input তৈরি: সব program type × tuition category (Local / International) × waiver category × board × gender, আর result-এর সীমানা মান (প্রতিটি waiver rule-এর min / max GPA বা CGPA, তার ঠিক নিচে ও উপরে, golden / non-golden) — legacy-র `waivers` table থেকে script দিয়ে |
| 3 | প্রতিটি input legacy-র `POST /calculate` (WaiverApiController) দিয়ে চালিয়ে ফল সংরক্ষণ → `tests/fixtures/waiver-golden.json` (waiver %, waiver-পরবর্তী মোট, বার্তা; invalid input-এর validation error) |
| 4 | নতুন Tuition / Waiver module-এর calculator service একই input-এ → golden file-এর সাথে তুলনা (Pest dataset) |
| 5 | পার্থক্য পাওয়া গেলে: migration mapping-এর ভুল কিনা দেখা; legacy-র ইচ্ছাকৃত আচরণ হলে নতুনটি ঠিক করা। Legacy-র আচরণ থেকে সচেতনভাবে সরে যেতে হলে (যেমন legacy-র bug) DIU Admission office-এর লিখিত অনুমোদন, golden file-এ নোট সহ |

Pass শর্ত: **১০০% মিল** (অনুমোদিত ব্যতিক্রম ছাড়া)। Waiver data বা rule admin-এ বদলালে golden file নতুন করে তৈরি নয় — বরং সেই পরিবর্তনের জন্য আলাদা test যোগ হয়; golden master শুধু migration-এর সঠিকতা প্রমাণ করে।

Tuition fee-র হিসাবও একই পদ্ধতিতে (legacy `tuition-fees` endpoint বনাম নতুন)।

### 7.2 সবচেয়ে বেশি দেখা page — অগ্রাধিকার

DIU-র তথ্য অনুযায়ী visitor সবচেয়ে বেশি যান: **Admission, Waiver Calculator, Programs (listing ও program page), Scholarship**। এগুলোর জন্য অতিরিক্ত নিয়ম:

| বিষয় | নিয়ম |
|---|---|
| Severity | এই page-গুলোর defect এক ধাপ উপরে গণ্য (S3 → S2, S4 → S3) |
| Browser E2E | প্রতিটি page-এর মূল journey desktop ও mobile (৩৯০px) দুটোতেই, en ও bn: Program খোঁজা → program page → tuition → waiver হিসাব → Apply link; Admission info → deadline → apply; Scholarship তালিকা → details |
| Performance | NFT-01 / NFT-02-এর page তালিকায় বাধ্যতামূলক; Lighthouse mobile Performance ≥ **90** (সাধারণ লক্ষ্য ৮০) |
| Load | NFT-06-এর traffic mix-এ এই page-গুলো ≥ ৬০%; admission মৌসুমের spike (স্বাভাবিকের ৩ গুণ) আলাদা চালানো |
| Accessibility | axe ছাড়াও manual keyboard ও screen reader (NFT-08) |
| Content সঠিকতা | Migration-এর পর live site-এর সাথে পাশাপাশি তুলনা: সব program-এর নাম, credit, duration, tuition, eligibility; সব scholarship; admission তারিখ (MIG-11) |
| URL | Legacy URL (যেমন `/tuition-fee-calculator`) সঠিক নতুন page-এ 301 — generic page-এ নয় (MIG-07) |
| UAT | Visitor scenario S-09 এই page-গুলো ঘিরেই |
| Go-live | Smoke test-এ প্রথমে এগুলো; প্রথম ৭ দিন uptime ও error monitoring আলাদা alert |

---

## 8. Non-functional test

| ID | Requirement | পদ্ধতি | Pass শর্ত |
|---|---|---|---|
| NFT-01 | NFR-PERF-01 TTFB | k6, staging, cache warm: homepage, admission, waiver calculator, program listing, program page, scholarship, news detail, microsite home | p95 < 500ms |
| NFT-02 | NFR-PERF-02 | Lighthouse CI (mobile, simulated 4G) ঐ ৮টি page | Performance ≥ 80 (অগ্রাধিকার page ≥ 90, Section 7.2); load < 3s |
| NFT-03 | NFR-PERF-03 | Admin list ১০,০০০ record (seeded) | < 2s |
| NFT-04 | NFR-PERF-04 | Menu / setting পরিবর্তন → পরের request-এ নতুন মান | Stale cache নেই |
| NFT-05 | NFR-CAP-01 | ৫০,০০০ content + ১,০০,০০০ media seed করে NFT-01, NFT-03 আবার | একই লক্ষ্য পূরণ |
| NFT-06 | NFR-CAP-02 | k6: ৫০০ concurrent virtual user, ১০ মিনিট, mixed page | Error < 1%, p95 < 1s |
| NFT-07 | NFR-USE-02 | axe-core: প্রতিটি template × en / bn | Critical / serious = 0 |
| NFT-08 | NFR-USE-02 | Manual keyboard ও screen reader (NVDA) — homepage, program, notice, waiver calculator | Theme Guide §13 manual checklist pass |
| NFT-09 | NFR-USE-01 | Admin panel tablet (768px) — মূল form | ব্যবহারযোগ্য |
| NFT-10 | NFR-USE-04 | নতুন content editor-কে ২ ঘণ্টা training, তারপর news / notice / event publish (UAT-এর অংশ) | সাহায্য ছাড়া সম্পন্ন |
| NFT-11 | NFR-SEO-01..03 | HTML source, `sitemap.xml` (hreflang সহ), robots, JSON-LD (Google Rich Results test) | বৈধ |
| NFT-12 | NFR-SEC | OWASP ZAP baseline scan (staging) | High = 0 |
| NFT-13 | NFR-REL-02 | Backup → নতুন server-এ restore → site চলে | সফল, সময় লিপিবদ্ধ |
| NFT-14 | NFR-MNT-01 | Pint, Larastan (level নির্ধারিত) | CI সবুজ |
| NFT-15 | NFR-MNT-03 | Coverage report | Kernel + Core ≥ 70% |

---

## 9. Data migration test

বিস্তারিত migration পদ্ধতি Deployment & Data Migration Plan-এ (ধাপ ৯); এখানে যাচাইয়ের নিয়ম।

| ID | যাচাই | পদ্ধতি | Pass শর্ত |
|---|---|---|---|
| MIG-01 | Row count | প্রতিটি mapping (DB-03 থেকে DB-06-এর migration অংশ): উৎসে `SELECT COUNT(*)` (information_schema নয়) বনাম লক্ষ্যে; বাদ দেওয়া row-এর কারণ সহ report | পার্থক্য = শুধু নথিভুক্ত বাদ |
| MIG-02 | Field mapping | প্রতিটি table থেকে random ২০টি record, field-by-field তুলনা (script) | ১০০% মিল |
| MIG-03 | Relation | Legacy `is_home` → `site:0` relation; department / program link; committee member → assignment | Count ও sample মিল |
| MIG-04 | Person dedupe | Merge sheet-এর প্রতিটি সিদ্ধান্ত প্রয়োগ হয়েছে (DBQ-04 reviewer sign-off) | ১০০% |
| MIG-05 | File / media | প্রতিটি migrated file-এর অস্তিত্ব, size, Media Library-তে usage | Missing file report = নথিভুক্ত |
| MIG-06 | Translation | বাংলা text encoding (mojibake নেই) | Sample-এ ভাঙা অক্ষর নেই |
| MIG-07 | URL | Legacy site-এর শীর্ষ URL (Analytics থেকে ন্যূনতম ৫০০টি) + sitemap-এর সব URL → নতুন site-এ 200 বা 301 | 404 = 0 |
| MIG-08 | Repeatability | Migration ২ বার চালানো | একই ফল (idempotent) |
| MIG-09 | Known issues | DBQ-05 (২টি country id), DBQ-06 (`mps_categories`) — সিদ্ধান্ত অনুযায়ী handle | Report-এ উল্লেখ |
| MIG-10 | Content owner review | প্রতিটি faculty / office-এর প্রতিনিধি নিজের microsite দেখে sign-off | Sign-off |
| MIG-11 | অগ্রাধিকার page-এর content | Program (নাম, credit, duration, tuition, eligibility), scholarship, admission তথ্য — live site-এর সাথে script ও পাশাপাশি তুলনা; waiver golden master (7.1.1) | ১০০% মিল |

---

## 10. User Acceptance Test (UAT)

### 10.1 অংশগ্রহণকারী

| ভূমিকা | DIU-তে কে | Scenario |
|---|---|---|
| Super Admin | IT / Web team | 10.2 — S-01, S-02, S-08 |
| Admin (central content) | PR / Web team | S-03, S-04, S-07 |
| Faculty / Department editor | ১টি faculty ও ২টি department-এর প্রতিনিধি | S-05, S-06 |
| Office editor | Registrar বা Admission office | S-06 |
| Visitor | ছাত্র / অভিভাবক (৫ জন) | S-09 |

### 10.2 Scenario

| ID | Scenario | সফলতার শর্ত |
|---|---|---|
| S-01 | একটি installable module (যেমন CSR) deactivate → website দেখা → reactivate | Site ভাঙে না; data ফিরে আসে |
| S-02 | Dev Guide অনুযায়ী বানানো sample module ZIP install ও activate | Conflict ছাড়া চলে (BRD §11) |
| S-03 | Main site homepage section সাজানো, slider ও highlight পরিবর্তন | Developer ছাড়া সম্পন্ন |
| S-04 | Department-এর "main site request" approve | Main site-এ দেখায় |
| S-05 | Department editor: notice, news, event (ছবি সহ), নিজের menu তৈরি ও মুছে ফেলা | Fallback ঠিক; অন্য department-এর কিছু দেখা যায় না |
| S-06 | Office editor: office page, team (assignment), documents | শুধু নিজের office |
| S-07 | Media Library থেকে পুরনো ছবি নতুন news-এ ব্যবহার; ভুল করে মুছে Trash থেকে restore | সফল |
| S-08 | Theme color ও logo পরিবর্তন; admin panel branding পরিবর্তন | সব page-এ প্রযোজ্য |
| S-09 | Visitor: CSE program খুঁজে tuition দেখা, waiver হিসাব, scholarship খোঁজা, admission deadline ও result দেখা, notice খোঁজা — mobile-এ | ৩ click-এর মধ্যে তথ্য (UI/UX F-1); বিভ্রান্তি নথিভুক্ত |

### 10.3 Sign-off

Scenario-র ফল, খোলা defect (S3 / S4) তালিকা ও পরিকল্পনা সহ UAT report → Project Owner sign-off।

### 10.4 Production smoke test (go-live দিন)

প্রথমে অগ্রাধিকার page (admission, waiver calculator — ৫টি golden input, program listing ও ৩টি program page, scholarship), তারপর homepage, ৩টি microsite, notice detail, search, contact form submit, admin login, একটি notice publish → frontend-এ দেখা, Horizon চলছে, scheduler চলছে, backup job, HTTPS ও header, sitemap।

---

## 11. BRD Success Criteria → Test

| BRD §11 Success Criteria | Test |
|---|---|
| Go-live-এর পর routine কাজে developer লাগবে না | UAT S-03, S-05, S-06, S-08; NFT-10 |
| Documentation follow করে নতুন developer sample module বানিয়ে conflict ছাড়া install | UAT S-02; TC-MOD-01/04 |
| Module deactivate ও re-activate-এ data অক্ষত | TC-MOD-09, TC-MOD-10; UAT S-01 |
| Theme পরিবর্তনে সব page-এ নতুন design | TC-THM-01/03; UAT S-08 |
| নতুন Office / Lab-এ code ছাড়া microsite, menu, permission | TC-MS-06, TC-PERM-08 |
| Department menu মুছলে parent / main menu | TC-MS-01, TC-MS-02 |
| Scoped user scope-এর বাইরে দেখতে পারে না | TC-PERM-01..04, 09, 10 |
| সব পরিবর্তন activity log-এ ≥ ৬০ দিন | TC-DATA-01, TC-DATA-02 |
| Delete করা record Trash থেকে restore | TC-PUB-04, TC-DATA-03 |
| সব upload Media Library-তে, পুনরায় ব্যবহারযোগ্য | TC-MED-01, TC-MED-02 |

---

## 12. সময়সূচি

Development phase অনুযায়ী (চূড়ান্ত তারিখ project plan-এ):

| Phase | Test কাজ |
|---|---|
| 0 — POC | POC-1 থেকে 4 (Section 5) |
| 1 — Kernel | Architecture, schema, TC-MOD, TC-PERM, TC-PUB, TC-REL, TC-MS, TC-ASG, TC-DATA (reference module FAQ দিয়ে); CI pipeline প্রস্তুত |
| 2 — Core module (C-01 থেকে C-20) | Core AC (43); TC-MED, TC-LANG, TC-THM, TC-STAT, TC-REM, TC-SEC |
| 3 — Installable module | প্রতিটি module-এর AC + তিন বাধ্যতামূলক test; interactive tool (Section 7.1) |
| 4 — Default theme ও frontend | Browser, accessibility, Lighthouse |
| 5 — Migration dry-run ও system test | MIG-01..10, NFT-01..15, staging |
| 6 — UAT ও go-live | Section 10 |

---

## 13. Tool

| কাজ | Tool |
|---|---|
| Unit / feature / architecture | Pest 4 (+ `pest-plugin-laravel`, `pest-plugin-livewire`, `pest-plugin-arch`) |
| Browser | Pest Browser plugin (Playwright) |
| Accessibility | axe-core (Browser test-এ inject) |
| Performance | Lighthouse CI, k6 |
| Security | `composer audit`, `npm audit`, OWASP ZAP baseline |
| Static analysis / style | Larastan, Pint, Stylelint (theme) |
| Coverage | Xdebug / PCOV |
| Defect tracking | Project-এর issue tracker (GitHub / GitLab Issues), severity label S1–S4 |

---

## 14. ঝুঁকি

| ঝুঁকি | প্রভাব | প্রতিরোধ |
|---|---|---|
| Shield + Scope সংযোগ প্রত্যাশামতো কাজ করে না | Permission architecture পুনর্নকশা | Phase 0 POC আগে |
| ৫৮ module × lifecycle test CI ধীর করে | Developer feedback দেরিতে | Lifecycle test parallel; PR-এ শুধু পরিবর্তিত module, nightly-তে সব |
| Waiver / tuition-এর হিসাব legacy থেকে আলাদা হয় | ছাত্রকে ভুল তথ্য | Legacy calculator-কে oracle ধরে golden master test (7.1.1); ১০০% মিল ছাড়া go-live নয় |
| Admission মৌসুমে traffic spike | সবচেয়ে গুরুত্বপূর্ণ সময়ে site ধীর | Spike load test (7.2); go-live admission মৌসুমের বাইরে রাখার প্রস্তাব |
| Legacy data-র মান খারাপ (duplicate, missing id) | Migration দেরি | Dry-run আগে; DBQ-04..06 আগেই সমাধান |
| Staging-এ বাস্তব data-র ব্যক্তিগত তথ্য | Privacy ঝুঁকি | Mask script বাধ্যতামূলক (Section 3) |
| UAT অংশগ্রহণকারীর সময় নেই | Go-live দেরি | তারিখ আগে নির্ধারণ; scenario ছোট (Section 10.2) |

---

## খোলা প্রশ্ন

| # | প্রশ্ন | প্রস্তাব |
|---|---|---|
| TPQ-01 *(✔ সমাধান)* | Waiver calculator-এর সঠিক উত্তর কোথা থেকে? | `project_diu`-এর live calculator ১০০% সঠিক (DIU) → golden master oracle (7.1.1) |
| TPQ-02 *(✔ সমাধান)* | UAT-এ কোন faculty / department / office অংশ নেবে? | FSIT, CSE, BBA, Registrar office |
| TPQ-03 | Legacy site-এর Analytics access (MIG-07-এর শীর্ষ URL)? | Web team থেকে export |
