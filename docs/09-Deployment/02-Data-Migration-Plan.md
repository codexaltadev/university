# Data Migration Plan

| বিষয় | তথ্য |
|---|---|
| Document | Deployment & Data Migration Plan — Part B: Data Migration (SDLC ধাপ ৯) |
| Version | 1.0 |
| Date | 2026-10-08 |
| Status | ✔ Approved |
| Based on | DB-01 থেকে DB-06-এর "`project_diu` থেকে Migration Mapping" অংশ, Test Plan §9 (MIG-01 থেকে MIG-11) ও §7.1.1 (waiver golden master), BRD Q-21 / Q-26 |
| পাঠক | Migration developer, technical lead, DIU content owner (manual review অংশ) |

> **মূলনীতি:** `project_diu` শুধু **উৎস ও reference** — নতুন system-এর গঠন তার অনুকরণ নয়। Data নতুন standard-এ রূপান্তরিত হয়ে আসে; যা নতুন design-এ মানায় না, তা archive বা manual review-তে যায়।

## সূচি

1. [পরিধি](#1-পরিধি)
2. [নিয়ম](#2-নিয়ম)
3. [উৎস](#3-উৎস)
4. [Migration tool](#4-migration-tool)
5. [ধাপের ক্রম](#5-ধাপের-ক্রম)
6. [যা migrate হবে না](#6-যা-migrate-হবে-না)
7. [Manual কাজ](#7-manual-কাজ)
8. [Dry-run](#8-dry-run)
9. [Go-live (cutover)](#9-go-live-cutover)
10. [Rollback](#10-rollback)
11. [Go-live-এর পর](#11-go-live-এর-পর)
12. [খোলা প্রশ্ন](#12-খোলা-প্রশ্ন)

---

## 1. পরিধি

| উৎস | কী আসে |
|---|---|
| `project_diu` database | Academic, people, content, services — DB-01 থেকে DB-06-এর mapping অনুযায়ী (≈ ১২০টি legacy table) |
| Upload করা file | `https://webbackend.daffodilvarsity.edu.bd/{path}` (DB-তে relative path, যেমন `noticeFile/…pdf`, `images/faculty/…webp`; notice file ৩,৫৩২টি সহ) → Media Library |
| পুরনো website-এর URL | Sitemap + Analytics-এর শীর্ষ URL → 301 redirect |

**নতুন করে entry (legacy-তে নেই):** Course, Curriculum, Campus, Contact inquiry, News (BRD Q-21 — একটি blog post ছাড়া)। **Faculty Member** *(2026-10-17)*: entry বা migration নয় — DIU-র আলাদা faculty site থেকে `faculty-member:sync` (DB-03 §8.3); go-live-এর আগে Settings-এ API address।

---

## 2. নিয়ম

| # | নিয়ম |
|---|---|
| M-1 | **Legacy শুধু পড়া:** আলাদা `legacy` DB connection, `SELECT`-only user; কখনো লেখা নয় |
| M-2 | **Script দিয়ে, হাতে নয়:** সব রূপান্তর code-এ; manual সিদ্ধান্ত CSV / sheet-এ, script সেটি পড়ে প্রয়োগ করে |
| M-3 | **Idempotent ও পুনরায় চালানো যায়:** প্রতিটি ধাপ `migration_id_map` (DB-01 §13) দেখে — আগে আনা row আবার তৈরি করে না, update করে। পুরো migration শূন্য থেকে বারবার চালানো যায় (MIG-08) |
| M-4 | **নতুন নিয়ম মেনে:** সব record `BaseModel` দিয়ে তৈরি — `platformColumns()`, slug (`core_slugs`), relation (`core_relations`), media usage — নিজে table-এ সরাসরি insert নয় (event ও log বন্ধ রাখা যায়, নিচে দেখুন) |
| M-5 | **Activity log ও notification বন্ধ:** Migration চলাকালীন activity log, reminder, newsletter email, search indexing — বন্ধ; শেষে একবারে index |
| M-6 | **কিছু হারায় না:** যে field নতুন design-এ নেই কিন্তু তথ্য আছে, তা `note` / `role_label` / `overrides` বা archive report-এ (DB-04 §11.2 ধাপ ৫) |
| M-7 | **প্রতিটি ধাপের report:** উৎসে কত (`SELECT COUNT(*)` — information_schema নয়), কত এল, কত বাদ ও কেন, কত manual review-এ |
| M-8 | **`created_by`:** Legacy user migrate হলে তার নতুন id; না হলে "Legacy Import" system user |

---

## 3. উৎস

| উৎস | কীভাবে পাওয়া | দায়িত্ব |
|---|---|---|
| Database | Live server থেকে `mysqldump --single-transaction` → নতুন server-এ `project_diu_snapshot` হিসেবে restore | DIU IT |
| File | **HTTPS download:** DB-তে থাকা প্রতিটি path → `https://webbackend.daffodilvarsity.edu.bd/{path}` → নতুন server-এর `shared/legacy-files/{path}` (cache — পরের run-এ আবার নামায় না) | Migration developer (DMQ-01 ✔) |
| URL তালিকা | Legacy `sitemap.xml` + Analytics-এর শীর্ষ URL (TPQ-03) | Web team |
| Manual sheet | Role mapping, person merge, program review ইত্যাদি (Section 7) | Section 7-এর owner |

> Local development copy-তে (`/home/Project/diu`) upload করা file নেই। File সব `webbackend.daffodilvarsity.edu.bd`-এ public-ভাবে পাওয়া যায় (যাচাই: notice PDF, faculty ও admin ছবি — HTTP 200, 2026-10-08), তাই server access ছাড়াই HTTPS-এ নামানো যায়।

**কখন নামানো হবে (owner, 2026-10-21 — আগের 2026-10-08-এর সিদ্ধান্ত বদলে):** development-এ শুধু যেসব page তৈরি হয়েছে সেগুলোর file এখনই নামানো হয় (`legacy:migrate --step=S03 --download-files --only=faculty --only=department --only=program --only=facilities --only=accreditation --only=testimonials` — ১৩৪টি file, ~২০০ MB cache); বাকি group (alumni, event, gallery, notice PDF …) সেই page তৈরি হলে বা DR-2-এর আগে। *আগের সিদ্ধান্ত (2026-10-08):* এখন (development ও DR-1) file নামানো **হবে না** — script শুধু path ও metadata নিয়ে Media record তৈরি করে, file-এর জায়গায় placeholder। আসল download পরে, `webbackend.daffodilvarsity.edu.bd` (মূল উৎস) থেকে — DR-2-এর আগে একবার পূর্ণ, তারপর cutover-এ শুধু নতুনগুলো। Command: `legacy:migrate --step=S03 --download-files`।

**File download-এর নিয়ম:**

| বিষয় | নিয়ম |
|---|---|
| কোন file | শুধু DB-তে reference থাকা path (column) **এবং** rich text-এর ভেতরের link (`<img src>`, `<a href>` যা `webbackend.daffodilvarsity.edu.bd` বা relative `images/`, `uploads/`, `noticeFile/` নির্দেশ করে) — HTML parse করে |
| Live site-এর উপর চাপ | সর্বোচ্চ ৪টি একসাথে download, রাতে; `User-Agent: UWMS-Migration`; ব্যর্থ হলে ৩ বার retry |
| পুনরায় চালানো | `shared/legacy-files/`-এ আগে থেকে থাকলে নামায় না (ETag / size মিলিয়ে); cutover-এ শুধু নতুন file |
| পাওয়া যায়নি (404) | Report-এ (MIG-05) — record আসে, file ছাড়া; content owner জানবেন |
| আকার | DR-1-এ শুধু HEAD request (download নয়) — `Content-Length` যোগ করে মোট আকার → Part A §2.2-এর media disk |
| Rich text link | Import-এর পর body-র পুরনো URL নতুন Media URL-এ বদলানো — নইলে legacy বন্ধ হলে ছবি ভেঙে যাবে |

Staging-এ snapshot restore করার পর **ব্যক্তিগত তথ্য mask** (Test Plan §3): subscriber email, phone, admission applicant serial।

---

## 4. Migration tool

Migration code একটি **আলাদা package** — `tools/legacy-migration/` (repository-তে, কিন্তু core বা কোনো module-এর অংশ নয়, অন্য university-র installation-এ যায় না)।

> **অগ্রগতি (2026-10-10):** module তৈরির সাথে সাথে ধাপগুলো তৈরি হচ্ছে, যাতে development-এ আসল data থাকে। তৈরি: **S02** (শুধু program major category ও program tag), **S04**, **S06**, **S07** (tuition অংশ; waiver Phase 3.4b-তে) — বিস্তারিত ও সীমাবদ্ধতা [`tools/legacy-migration/README.md`](../../tools/legacy-migration/README.md)-এ। S04-এর সিদ্ধান্ত: legacy-র "Office" ও "Institutional Programs" faculty (`is_optional = 1`) academic নয় — এদের ১১টি "department" (Registrar, Exam, Accounts, Transport, Campus, Hall, HRDI, DIL, YSBC …) S10-এ Office module-এ যাবে।

```
tools/legacy-migration/
├── src/
│   ├── Steps/                 # প্রতিটি ধাপ একটি class (Section 5)
│   │   ├── S01UsersAndRoles.php
│   │   ├── S03Faculties.php
│   │   └── …
│   ├── Support/
│   │   ├── IdMap.php          # migration_id_map
│   │   ├── MediaImporter.php  # file → Media Library (hash দিয়ে duplicate বাদ)
│   │   ├── PersonMatcher.php  # DB-04 §11.2
│   │   └── Normalizer.php     # phone, email, বাংলা text, HTML পরিষ্কার
│   └── Verify/                # MIG-01 থেকে MIG-11
├── sheets/                    # manual সিদ্ধান্তের CSV (Section 7)
└── reports/                   # প্রতিটি run-এর report (git-এ নয়)
```

```bash
php artisan legacy:migrate --all                  # সব ধাপ ক্রমে
php artisan legacy:migrate --step=S07 --step=S08  # নির্দিষ্ট ধাপ
php artisan legacy:migrate --fresh                # নতুন DB-তে শূন্য থেকে (dry-run)
php artisan legacy:verify                         # MIG-01 থেকে MIG-11 report
php artisan legacy:redirects --import=sheets/redirects.csv
php artisan legacy:export-seed                    # project_diu → seed/ (git-এ)
php artisan db:seed --class=DiuContentSeeder      # legacy DB ছাড়া: সব ধাপ seed থেকে (= legacy:migrate --all --from-seed)
```

**Seed file (owner 2026-10-17):** legacy DB হাতের কাছে না থাকলেও (নতুন developer, CI, staging) DIU-র content আনার জন্য step-গুলো যে ১৩০টা table পড়ে, সেগুলোর structure (`seed/schema.sql`) আর row (`seed/{table}.jsonl.gz`) git-এ রাখা হয়। `--from-seed` এগুলো নিজস্ব MySQL database `diu_legacy_seed`-এ বসায় (`project_diu`-তে কখনো নয়), তারপর `legacy` connection সেখান থেকে পড়ে, ফলে step আর ফল একই থাকে। Personal data git-এ যায় না: `newsletters`, `admission_subscribers`, `admission_results` শুধু structure; alumni-র email, phone, student ID, office address খালি। ছবি ও file এতে নেই (S03)।

---

## 5. ধাপের ক্রম

Dependency অনুযায়ী — আগের ধাপের id পরের ধাপে লাগে (`migration_id_map`)।

| ধাপ | বিষয় | Legacy উৎস (মূল) | Mapping | Manual input |
|---|---|---|---|---|
| S00 | প্রস্তুতি: নতুন DB `uwms:install`, সব bundled module install ও activate (Publications, Research সহ — data আসবে, পরে default অনুযায়ী inactive), Legacy Import user | — | — | — |
| S01 | User, role, scope | `users`, `roles`, `model_has_roles`, `department_user` | DB-01 §13 | Role mapping sheet (৭.১) |
| S02 | Vocabulary ও term | সব `*_categories` | DB-02 §18 + module অনুযায়ী | — |
| S03 | **Media import** | `webbackend` থেকে HTTPS download (Section 3) | DB-02 §18 | প্রতিটি file Media Library-তে **একবার** (একই hash = একই item); id map `files`-এ legacy path (যেমন `gallery/…/x.webp`) → media item মনে রাখবে — পরের step-গুলো (S17G gallery, S16E event, S16T notice …) সেখান থেকে reference নেয়, copy নয় — *(2026-10-21, dev)* তৈরি: group ধরে (`--only`), cache `LEGACY_FILES_PATH` (default `storage/app/legacy-files`), একসাথে ৪টি, ৩ বার চেষ্টা, path-এ না পেলে `storage/{path}` (পুরনো app-এর upload disk); id map-এ path ও cache file-এর hash দুটোই; Media Library-র ১০ MB সীমার বড় ছবি ২৪০০px JPEG করে (মূলটা cache-এ); folder "Legacy import / {table}", scope main site, shared; হাতে বাছা ছবি বদলায় না। Dev: ১৩৪ file, ২টি webbackend-এ নেই (404) |
| S04 | Faculty, Department | `faculties`, `departments` | DB-03 §15 | — |
| S05 | Campus, Session | — (নতুন), `semesters` | DB-03 §15 | Campus তালিকা |
| S06 | **Program** ও তার অংশ | `department_program_type`, `program_types`, majors, career paths, facilitators, eligibilities, variants … | DB-03 §15, §15.1 | Program review sheet (৭.৩) |
| S07 | **Tuition, waiver, scholarship** | `tuition_categories`, `tuitions`, `boards`, `waiver_categories`, `waivers` + pivot, `scholarship_categories` + `scholarships` (→ Scholarship module, parent / child — DB-03 v1.4 §11.3), `accordion_categories` + `accordions` (→ Scholarship, category সহ; step `S07S`) | DB-03 §15 | — (golden master দিয়ে যাচাই, Section 8) |
| S08 | **Admission** | `admission_notices`, `admissions` + `admission_facility_categories`, `admission_tests`, `admission_results`, `admission_contact_types` + `admission_contacts` (ব্যক্তি `admins` থেকে manual person), `admission_subscribers` (DB-03 v1.5 §10, §15); `/admission` page ও তার child page নতুন content — legacy-তে নেই, হাতে বা seeder | DB-03 §15 | Program নাম না মেলা result-এর তালিকা |
| S09 | Academic Calendar | `calenders`, `calendar_events`, `calender_programs` + `calendar_event_calender_program` (audience) | DB-03 §12 v1.6, §15 | তারিখ (৭.৬) — *(2026-10-16, dev)* লেখা তারিখ parse: ১৮৪-র ১৬৩টি তারিখ, ২১টি শুধু লেখা; title-এর "(TRI)" = FBE, FHSS, FHLS, FSIT, "(BI)" / "(FE)" = Engineering (owner); `total_views` → view count; hard copy S03-এর পরে |
| S10 | Office, Section *(✔ 2026-10-11)* | `offices`, legacy office-"departments", `sections` + `section_employees`, `office_admins`, `support_contacts` (people → manual persons, `admins@manual`) | DB-04 v1.1 §11.1 | Office type (হাতে); campus দুটি M-08-এ |
| S11 | **Person ও dedupe** | `admins`, `staff`, `alumnis` + `admin_id` ছাড়া row | DB-04 §11.2 | Merge review sheet (৭.২) — *(2026-10-16, dev, Administration অংশ)* `admin_categories` (১১) + `staff_categories` (৫, support, directory-তে নেই) → ১৬ category DIU-র ক্রমে; `admins` ৬০৩ + `staff` ৩৭৯ → ৯৮২ member (directory-তে ৩৪৩); legacy `office_id` = employee ID (২টি duplicate খালি), `unique_id` = পুরনো address; S08 / S10-এর ৪২ admin manual person → member (৫১ assignment সরানো); ছবি S03-এর পরে; *(alumni অংশ = step S11A)* `alumnis` ৬৯৭ → alumni (৬৯২ active, Pride ১১৬, department মিলেছে ৬৯৫), contact লুকানো, duplicate student ID ২০টি খালি, ছবি / upload করা video S03-এর পরে, `is_faculty` (৩৭) M-07-এর সাথে, DIU-র `is_home` (১৬) বাদ — homepage-এ Pride |
| S12 | Assignment | `contacts`, `coordination_officers`, `office_admins`, `support_contacts`, `section_employees`, `facilitators` | DB-04 §11.1 | — |
| S13 | Committee | `committee_categories`, `committees`, `lab_committees` | DB-04 §7 v1.4, §11.1 | — *(2026-10-16, dev)* ১৩ committee (main site, DIU-র ক্রমে এক তালিকা — legacy site-এ committee-র উপরে কোনো category ছিল না, তাই category খালি; Admin চাইলে পরে দেবেন), ১৭৪ সদস্য (৪৬ Administration member, ১২৮ manual person — নাম প্রতি একজন; উপাধি বাদ দিয়ে মেলানো), `position` → role (নিজস্ব পদ-নাম সহ), `header` → note; নামহীন ১টি review; `lab_committees` (১৮) Lab module-এর সাথে |
| S14 | Lab | `lab_facilities`, `lab_tools`, scopes, projects, products, `lab_mid_sections` | DB-06 §13 | — |
| S15 | Page ও Menu | *(department অংশ তৈরি 2026-10-10, step `S15D`: `department_articles` → department-এর page (slug রাখা), `department_menus` → department-এর primary menu; module link → সেই module-এর ভবিষ্যৎ page-এর link, module না থাকা পর্যন্ত inactive — module এলে step আবার চালালে চালু; program-গুলোর menu "inherit" (department-এর menu)। Main site-এর menu step `S15M` (2026-10-17): live site-এর main menu-গুলো code-এ লেখা, project_diu-তে নেই — তাই live site থেকে নেওয়া snapshot `tools/legacy-migration/data/diu-site-menus.json` থেকে: primary (৬টি mega menu), topbar (utility link), quick-links-icon (Services grid), footer-1…4 ও footer region-এর column শিরোনাম, copyright, social link; go-live-এর আগে snapshot আবার নেওয়া হবে। Main homepage step `S15H`: live homepage-এর ক্রমও code-এ, তাই main site-এর homepage-এ default theme-এর section-গুলো DIU-র ক্রমে — hero, mission/rankings, degree, scholarships, research, events, testimonials, alumni stories, alumni-portal button; লেখা theme-এর default। Step `S15K` (2026-10-17): live site সব department আর সব faculty page একই code-এ আঁকে, তাই "All Departments" আর "All Faculties"-এর shared homepage DIU-র ক্রমে — page-hero, programs / faculty-stats + faculty-departments, notices, labs, partners, ambitions, news, events, campus services; আবার চালালে shared দুটো reset হয়, কারও নিজের homepage-এ হাত দেয় না; step `S15A` (2026-10-24): main site-এর `articles` (২৮৫) → "article" page-এর নিচের page, তাই `/article/{slug}` ঠিকানা অপরিবর্তিত; S15A ও S15D-র লেখা "HTML" block-এ (Bootstrap class থাকে), relative ছবি ও file-link webbackend-এ — S03-এর আগ পর্যন্ত)* `*_articles`, `articles`, `registrar_offices`, `proctor_offices`, `*_menus`, `side_menus` | DB-02 §18 | — |
| S16 | Notice, Event | `notices`, `notice_files`, `events`, `event_images` | DB-05 §18 | — |
| S16T | Notice *(2026-10-16, dev)* | `notices` (৩,৩৯১), `notice_categories` (৫), `notice_files` (৩,৫৩২) | DB-05 v1.4 §5, §18 | ৩,২৬৭ notice (deleted বাদ); department / office relation, `is_home` (৫৭৩) → main site, লেখার তারিখ → `active_at`, `view_counter` → view count; file ও তাদের title S03-এর পরে attachment-এ — *(2026-10-24)* DIU-র `/noticeboard` → `/notices` ও প্রতিটি `/noticeboard/{slug}` → notice-এ 301 (Site › Redirects); S03 group `notices`: `notice_files` (deleted বাদ) একই notice-এ ক্রমে, `file_title` → attachment-এর title |
| S16E | Event *(2026-10-16, dev)* | `events` (৫৬৩), `event_categories` (৫) | DB-05 v1.3 §4, §18 | ৫৫৯ event (শিরোনামহীন ৪টি বাদ); সময় নেই = all-day (৩৬৯); department relation, `is_home` → main site; `url` → registration link (১২০, যাচাই); ১৪টি কোথাও দেখানো ছিল না — review; legacy category-গুলো এলোমেলো ("Upcoming", "Pohela Falgun 2022") — Admin গুছিয়ে নেবেন; lab event (১২) Lab module-এর সাথে; ছবি ও `event_images` S03-এর পরে — *(2026-10-24)* DIU-র ঠিকানা `/events-detail/{id}` → event-এ 301 (Site › Redirects, record ধরে; ৫৫৯টি); S03 group `events`: `photo` → featured image (dev: ৫৪৩টি, ২০টি webbackend-এ নেই) |
| S16N | News *(2026-10-16)* | `blog_categories` (৩), `blog_posts` (১) | DB-05 v1.2 §3, §18 | News নতুন করে শুরু (Q-21): একটি post + ৩ category, main site-এ, লেখার তারিখ `active_at`; ছবি S03-এর পরে; `lab_news` Lab module-এর সাথে |
| S17A | Announcement *(2026-10-17, dev)* | `greetings` (৫), `news_tickers` (৪), `countdown_timers` (১) | DB-05 v1.11 §15, §18 | greeting → popup (flag → জায়গা: `is_home` main site, `is_faculty` / `is_department` / `is_program` → entity, `is_admission` → `/admission`-এর page-গুলো), `start_date` / `expiry_date` → `active_at` / `expires_at`; ticker → department (বা `is_home` → main site); countdown → main site; বন্ধগুলো বন্ধই (চালু ৩); popup-এর ছবি S03-এর পরে (S17A আবার) |
| S17D | Documents *(2026-10-16, dev)* | `flipbooks` (১৬) + categories (৩), `prospectuses` (৪৫), `charter_guidebooks` (৮৬) + categories (৪), `policies` (১), `semesters` | DB-05 v1.9 §12, §18 | ১৪৮ document: prospectus ৪৫ (সবগুলো নিজের Program-এ, "Prospectus {program} Fall 2026"), guidebook ৫৬ ও charter ৩০ (department-এর, না থাকলে main site), flipbook ১৬ (main site, slug রাখা; মূল PDF লাগবে — ৭.৭), policy ১ (test entry — review); category → `documents.category`; file **copy নয়** — S03-এর পরে id map `files` থেকে reference, তাই S03-এর পরে S17D আবার |
| S17S | Slider *(2026-10-16, dev)* | `sliders` (১৫৩), `cover_photos` (১৩), `program_sliders` (৩), `mba_sliders` (৪) | DB-05 v1.7 §8, §18 | ১৫১ hero slide (lab-এর ২টি Lab-এর সাথে); department ও `show_departments` → relation, `is_home` (১৪) → main site; `url` + `button_text` → button; ১৩ page banner; program slider → Program hero (bullet ৪, button ২); `mba_sliders` (৪) — MBA program বেছে নিতে হবে; ছবি **copy নয়** — S03-এর পরে id map `files` থেকে reference, তাই S03-এর পরে S17S আবার; main homepage-এর `core.hero` → `slider.hero`; DIU-র এখনকার hero video (`/hero.mp4`) S03-তে Media Library-তে এনে main site-এর একটি video slide |
| S17G | Gallery *(2026-10-16, dev)* | `gallery_categories` (১৪), `gallery_sub_categories` (১৮), `gallery_photos` (২৪৬), `documentaries` (২৭), `lecture_series` (১৬), `program_videos` (২) | DB-05 v1.6 §7, §18 | category → `gallery.category`; sub-category-প্রতি album (১৮) + sub-category ছাড়া ছবির category-র নামে (৪); department → relation, বাকিগুলো main site; ছবি **copy নয়** — S03 legacy file Media Library-তে রাখার পর id map `files` (key = path) থেকে album-এ reference (`photo_title` → caption), তাই S03-এর পরে S17G আবার চালাতে হবে; documentary → department-প্রতি album (২২ album, ২৫ video; lab-এর ২টি Lab-এর সাথে); lecture series → ১ album, ১৬ video, বক্তা manual person; program video → "Program Videos" (program বেছে নিতে হবে); `event_images` (৭৭) → Event-এর Media (S16E), `lab_galleries` (৫৬৪) → Lab — *(2026-10-24)* S03 group `gallery`: `gallery_photos` শুধু Media Library-তে (record-এ নয়; dev: ২৩৮টি, ৮টি webbackend-এ নেই), তারপর S17G আবার → album-গুলো সেই item ব্যবহার করে (path `Normalizer::filePath` দিয়ে মেলানো) |
| S17M | Testimonial *(2026-10-16, dev)* | `testimonials` (২০), `mba_testimonials` (৫) | DB-05 v1.5 §6, §18 | ২০টি testimonial, সবগুলো Program-এর (`testimonialable_id` → S06-এর Program), ধরন `alumni`; নাম / পদবি / প্রতিষ্ঠান → manual person, নিজের title "পদবি, প্রতিষ্ঠান"; `mba_testimonials`-এর ৫টি একই ব্যক্তির পুরোনো কপি — বাদ; ছবি S03-এর পরে — *(2026-10-24)* DIU `/testimonials`-এ সবগুলো দেখায়, তাই চালু testimonial main site-এও (program-এর সাথে); DIU-র `/testimonials/{id}`-এর id অন্য table-এর (live API), তাই redirect নেই — *(2026-10-25)* `testimonials` ও `mba_testimonials`-এ `is_home` নেই (homepage-এর slider visitors-এর); তাই প্রতিটি program-এর প্রথম testimonial main site-এ Highlight — "What People Say"-এর শুরুর বাছাই (dev: ৬টি) |
| S17 | Gallery, Slider, Documents, FAQ *(FAQ অংশ = step `S17F`, ✔ 2026-10-11 — DB-05 v1.1 §18)*, Testimonial *(step `S17M`, ✔ 2026-10-16)*, Gallery *(step `S17G`, ✔ 2026-10-16)*, Slider *(step `S17S`, ✔ 2026-10-16)*, Documents *(step `S17D`, ✔ 2026-10-16)*, Widget *(Announcement, step `S17A`, ✔ 2026-10-17)* | `gallery_*`, `lab_galleries`, `documentaries`, `lecture_series`, `sliders`, `cover_photos`, `flipbooks`, `prospectuses`, `charter_guidebooks`, `policies`, `faqs`, `accordions`, `testimonials`, `greetings`, `news_tickers`, `countdown_timers` | DB-05 §18 | Flipbook PDF (৭.৭) |
| S18T | Transport *(2026-10-17, dev)* | `transport_services` (৪), `stops` (১৭৮, চালু ১০২), `roads` (২০) + `road_stop` + `road_schedule` + `schedules` (চালু ১৮), `road_services` (২০) + `road_service_stop` + `road_service_schedule`, `transport_settings` | DB-06 v1.2 §2 | ৪ service (legacy-র চালু service → settings), ১০২ stop, ২০ route, ৪০ variant (default ২০ + Final Exam ২০), ১৬৩ সময়; road-এর শুরু ও শেষ stop variant-এর প্রথম ও শেষে (DSC pivot-এ ছিল না); সময় variant-প্রতি copy; নিয়ম PDF S03-এর পরে (S18T আবার) |
| S18F | Facilities *(2026-10-17, dev)* | `facilities` (৭৬: ৪১ নিজের শিরোনামসহ, ৩৫ = program-এ একটি main facility-র ব্যবহার → সেই facility program-এও "Show in") | DB-06 v1.4 §4 | icon ছিল font নাম — ছবি S03-এর পরে |
| S18C | CSR *(2026-10-17, dev)* | `csr_categories` (৭, parent সহ), `csrs` (১১৯; `is_featured` → Highlight) | DB-06 §7 | তারিখ = entry-র দিন; ছবি S03-এর পরে |
| S18L | Labs *(2026-10-17, dev)* | `lab_facilities` (১১৩ → department-এর নিচে; `assign_person_*` → in-charge manual person), `lab_tools` (২৯) / `lab_scopes` (৬) / `lab_projects` (৭) / `lab_products` (২) + category, `lab_articles` (৯ → lab-এর Page) | DB-06 §3 | gallery, news, committee, menu, mid-section, summary — review তালিকায় (সেই module / হাতে) |
| S18P | Partners *(2026-10-17, dev; S18L-এর পরে)* | `discount_partner_categories` (১২; Font Awesome → Heroicon), `discount_partners` (৬৩), `mps` (১২০; Members → membership, Partners → industry, অন্য → other; lab-এর ৩টি lab-এ) | DB-06 §8 | একই নামের discount partner আলাদা থাকে; logo S03-এর পরে |
| S18R | Accreditation & Ranking *(2026-10-17, dev)* | `accreditation_categories` (২), `accreditations` (১৫ → program, `is_department` / `is_faculty` → সেখানেও), `rankings` (৬১) | DB-06 §5 | rank field ছিল না — লেখা থেকে পড়া ("201–300", "3rd"), না পেলে "Ranked": review |
| S18I | International *(2026-10-17, dev)* | `countries` (৭৯ → ISO code নাম থেকে; UAE → AE, North Cyprus → XC), `international_linkages` (৭১৭; ৪টি দেশ ছাড়া বাদ), `featured_partners` (২৭ → Highlight), conferences (৯) + categories (৩), `visitors` (১৭৮) | DB-06 §6 | চুক্তির ধরন / তারিখ ছিল না → "other"; conference-এর দিন শিরোনামের বছর থেকে |
| S18N | Newsletter *(2026-10-17, dev)* | `newsletters` (৬,৬২৪ → ৬,৫৬৯ subscriber, "General" list, source legacy; ৫৫ ভুল / দুবার) | DB-06 §10 | — |
| S18 | Services *(Transport-এর অংশ = step `S18T` — DB-06 v1.2 §2)* | Transport, `facilities`, accreditation, rankings, international, `visitors`, `csrs`, `mps`, `discount_partners` | DB-06 §13 | Ranking parse, country, mps (৭.৮) |
| S19 | Newsletter subscriber | `newsletters`, `admission_subscribers` | DB-06 §13 (DBQ-07: সরাসরি subscribed) | — |
| S20 | **Main site ও highlight** | সব `is_home = 1` → `site:0` relation; `featured_partners`, `csrs.is_featured` → highlight | DB-05 §1, BRD Q-26 | — |
| S21 | SEO meta, slug, redirect | `seo`, সব slug, পুরনো URL তালিকা; *(2026-10-10, SRS FR-GEN-34 থেকে 36)* DIU-র microsite URL pattern (`/faculties/{x}` একই থাকে, `/department/{x}` → `/{faculty}/{x}`, `/department/{d}/program/{p}` ও `/program/{p}` → `/{faculty}/{d}/{program}`) আলাদা redirect row ছাড়াই 301 পায় — resolver entity-র পুরনো route prefix (`department`, `program`) চেনে (3.5c বাস্তবায়ন); S21 বাকি পুরনো URL-এর জন্য | DB-02 §18 | Redirect sheet (৭.৯) |
| S22 | Content Update Reminder rule | `notification_settings` | DB-02 §18 | — |
| S23 | Stat set (manual মান) | `department_program_type`-এর count field | DB-03 §15 | — |
| S24 | শেষ কাজ | — | — | Search index, sitemap, cache warm, view count, Research / Publications `default_active` অনুযায়ী inactive |
| S25 | যাচাই | — | Test Plan MIG-01 থেকে MIG-11 | — |

**অগ্রাধিকার:** S06–S08 (Programs, Waiver / Scholarship, Admission) সবচেয়ে বেশি দেখা page-এর data (Test Plan §7.2) — প্রতিটি dry-run-এ এগুলোর যাচাই প্রথমে।

---

## 6. যা migrate হবে না

| Legacy | সিদ্ধান্ত | Ref |
|---|---|---|
| `audit_logs` (২২,৬৪৫) | Archive file (JSON, compressed) → offsite; নতুন log শূন্য থেকে | DBQ-02 |
| News (পুরনো API site থেকে আসত) | নতুন system-এ নতুন করে শুরু; শুধু `blog_posts`-এর ১টি ও `lab_news`-এর ১টি | BRD Q-21 |
| `notification_remembers` (০ row) | কিছু নেই | DB-02 §18 |
| `program_types` "Proposed Programs" (id 12) | কোনো program ব্যবহার করে না | DB-03 §15.1 |
| Legacy `roles` / `permissions` সরাসরি | Role mapping sheet দিয়ে নতুন Shield role-এ | DB-01 §13 |
| Session, cache, job, password reset table | System table — প্রয়োজন নেই | — |
| পূর্ণ legacy DB | Migration-এর পর পূর্ণ dump archive (ন্যূনতম ১ বছর, শুধু পড়া) | Part A §7 |

---

## 7. Manual কাজ

এগুলো script দিয়ে সিদ্ধান্ত নেওয়া যায় না। প্রতিটির জন্য script একটি **sheet (CSV) তৈরি করে**, owner পূরণ করেন, পরের run sheet পড়ে প্রয়োগ করে।

| # | কাজ | কী লাগবে | Owner (DIU) | কখন লাগবে |
|---|---|---|---|---|
| 7.1 | Role mapping | পুরনো ১৫টি role → নতুন role ও scope | Web team | Dry-run ১-এর আগে |
| 7.2 | Person merge review | `admins`-এর ২১টি একই-নামের group, নাম + পদবি দিয়ে প্রস্তাবিত merge | HR বা Registrar (DBQ-04) | Dry-run ২-এর আগে |
| 7.3 | Program review | `program_credit_duration` parse যাচাই; একাধিক `eligibilities` row-এর merge; `institutional_programs` ও `mba_programs`-এর level বা একত্রীকরণ; `tuitions.majors` text | Admission office + department | Dry-run ২-এর আগে |
| 7.4 | Campus তালিকা | Campus নাম ও ঠিকানা; `admins.campus` text → campus match | Web team | Dry-run ১-এর আগে |
| 7.5 | নতুন content | Course, Curriculum — legacy-তে নেই (Faculty Member faculty site থেকে sync, DB-03 §8.3) | প্রতিটি department | Go-live-এর আগে (staging-এ entry, final run-এর পর production-এ — Section 9 দেখুন) |
| 7.6 | Calendar তারিখ | `date_explanation` থেকে parse না হওয়া তারিখ | Registrar / Academic | Go-live-এর আগে (চলতি session শুধু) |
| 7.7 | Flipbook PDF | ১৬টি flipbook-এর মূল PDF (legacy-তে ZIP) — নতুন system-এর নিজস্ব flipbook শুধু PDF চায় (S17D-এর document-গুলোতে PDF দিলেই চলবে) | Web team | Dry-run ২-এর আগে |
| 7.8 | Services data | Ranking-এর agency / rank parse যাচাই; ২টি অমিল `country_id` (DBQ-05); ৪টি `mps_categories`-এর অর্থ (DBQ-06); `events.url`-এর ধরন | International Affairs, Web team | Dry-run ২-এর আগে |
| 7.9 | Redirect | পুরনো শীর্ষ URL → নতুন URL; script স্বয়ংক্রিয় প্রস্তাব দেয় (slug মিলিয়ে), বাকিগুলো হাতে | Web team (TPQ-03) | Dry-run ৩-এর আগে |

---

## 8. Dry-run

ন্যূনতম তিনবার, প্রতিবার **নতুন snapshot** দিয়ে শূন্য থেকে:

| Dry-run | কোথায় | লক্ষ্য | Pass শর্ত |
|---|---|---|---|
| **DR-1** | Developer machine / staging | সব ধাপ শেষ পর্যন্ত চলে; সমস্যা খুঁজে বের করা — **file ছাড়া** (placeholder) | Crash নেই; report-এ বাদ পড়া row-এর কারণ আছে |
| **DR-2** | Staging | সম্পূর্ণ, manual sheet সহ; যাচাই ও content owner review | MIG-01 থেকে MIG-11 pass (MIG-07 ছাড়া); **waiver golden master ১০০%** (Test Plan §7.1.1); content owner sign-off শুরু (MIG-10) |
| **DR-3** | Staging (production-এর মতো server) | **Go-live-এর পূর্ণ মহড়া** — Section 9-এর timeline হুবহু, সময় মেপে | সব MIG pass; মোট সময় cutover window-এর মধ্যে; UAT এই data-তে |

প্রতিটি dry-run-এর report (`reports/`): ধাপ অনুযায়ী count, সময়, warning, manual review বাকি। DR-3-এর সময় দেখে cutover window চূড়ান্ত হয়।

**Waiver ও tuition:** S07-এর পর legacy app একই snapshot-এ চালিয়ে golden master (Test Plan §7.1.1) — DR-2 থেকে প্রতিটি run-এ।

---

## 9. Go-live (cutover)

### 9.1 কখন

* **Admission মৌসুমের বাইরে** (Test Plan §14 ঝুঁকি) — সঠিক মাস DIU Admission office ঠিক করবে (DMQ-05)।
* সপ্তাহের কম traffic-এর দিন, রাতে।

### 9.2 Content freeze

Legacy admin panel-এ পরিবর্তন বন্ধ থাকে **final snapshot থেকে DNS switch পর্যন্ত** (DR-3-এর সময় অনুযায়ী, আনুমানিক কয়েক ঘণ্টা)। জরুরি notice এই সময়ে এলে: নতুন system-এ (switch-এর পর) সরাসরি entry।

Course, Curriculum (৭.৫) staging-এ entry করা হলে final run-এর পর একটি export / import command দিয়ে production-এ আনা হয় — legacy থেকে নয়, তাই freeze-এর প্রভাব নেই।

### 9.3 Timeline

| সময় | কাজ | কে |
|---|---|---|
| T−14 দিন | DR-3 সম্পন্ন ও pass; UAT sign-off; go / no-go meeting-এর তারিখ | Technical lead, Project Owner |
| T−7 | Freeze-এর ঘোষণা সব content editor-কে; production server প্রস্তুত (Part A), smoke test খালি installation-এ | Web team, DevOps |
| T−1 | সব file আগে থেকে download (production server-এর cache-এ; শেষে শুধু নতুনগুলো বাকি থাকে); DNS TTL কমিয়ে ৫ মিনিট | Migration developer, DevOps |
| T−0, ২২:০০ | **Freeze শুরু** — legacy admin read-only (বা login বন্ধ) | DIU IT |
| ২২:১৫ | Final DB dump; নতুন file-এর download (delta) migration-এর S03-এ | DIU IT, Migration developer |
| ২২:৪৫ | `legacy:migrate --fresh --all` production-এ | Migration developer |
| (DR-3 সময় অনুযায়ী) | `legacy:verify` — MIG-01..11; waiver golden master | Migration developer, QA |
| তারপর | **Go / no-go** (Section 10-এর শর্ত) | Technical lead + Project Owner |
| Go হলে | DNS / reverse proxy নতুন server-এ; Test Plan §10.4 production smoke test (অগ্রাধিকার page প্রথমে) | DevOps, QA |
| T+0, সকাল | Content editor-দের নতুন admin panel-এ login (পুরনো password কাজ করে — DB-01 §13); freeze শেষ | Web team |
| T+1 থেকে T+7 | **Hypercare:** কঠোর alert (Part A §8), দৈনিক review, 404 report থেকে redirect | সবাই |
| T+30 | Legacy site বন্ধ (APIQ-04 সমাধান সাপেক্ষে — নিচে দেখুন); legacy DB ও file archive | DIU IT |

### 9.4 পুরনো backend API (`webbackend.daffodilvarsity.edu.bd`)

বর্তমান live website এই API থেকে data নেয়; অন্য কোনো system-ও নিতে পারে (APIQ-04 — এখনো অজানা)। তাই:

* Go-live-এ শুধু **website domain** নতুন system-এ যায়; `webbackend` domain **চালু থাকে, read-only** (legacy admin বন্ধ)।
* Rich text-এর পুরনো file link migration-এ নতুন Media URL-এ বদলানো হয় (Section 3), তাই webbackend বন্ধ হলেও নতুন site-এর ছবি ও file ভাঙবে না। তবু বাইরের site বা পুরনো email-এ থাকা file link-এর জন্য webbackend-এর `images/`, `uploads/`, `noticeFile/` path নতুন site-এ redirect করার নিয়ম (Nginx map, legacy path → নতুন media URL) রাখা হবে।
* T+30-এর মধ্যে webbackend-এর access log দেখে কোন consumer আছে তা চিহ্নিত — তারপর বন্ধ, অথবা Release 2 API (API Spec) পর্যন্ত চালু।

---

## 10. Rollback

**No-go শর্ত (switch-এর আগে):** যেকোনো MIG fail (নথিভুক্ত ব্যতিক্রম ছাড়া), waiver golden master < ১০০%, অগ্রাধিকার page-এর smoke fail, migration DR-3-এর সময়ের দ্বিগুণের বেশি নেয়।
→ Freeze তুলে নিন, legacy চালু থাকে, কারণ ঠিক করে নতুন তারিখ। **কোনো data ক্ষতি নেই** (legacy-তে কিছু লেখা হয়নি — M-1)।

**Switch-এর পর rollback (T+0 থেকে T+2 দিন):** S1 defect (Test Plan §4.1) যা কয়েক ঘণ্টায় ঠিক করা যায় না → DNS আবার legacy-তে, legacy admin খুলে দেওয়া।
* এই সময়ে নতুন system-এ যা entry হয়েছে (notice ইত্যাদি) তার তালিকা activity log থেকে export → legacy-তে হাতে entry (সংখ্যা কম থাকবে)।
* T+2 দিনের পর rollback নয় — fix forward।

---

## 11. Go-live-এর পর

| কাজ | কখন |
|---|---|
| `migration_id_map` archive (CSV) ও table মুছে ফেলা | T+30 |
| `LEGACY_DB_*` ও legacy DB user মুছে ফেলা; `tools/legacy-migration` production server থেকে সরানো | T+30 |
| Legacy DB ও file-এর পূর্ণ archive (offsite, ১ বছর) | T+30 |
| Faculty Member data এলে person matching আবার (DB-04 §11.2 ধাপ ৬) | Faculty site-এর API দিয়ে প্রথম sync-এর পর |
| Content Update Reminder চালু (migration-এর সময় বন্ধ ছিল) | T+7 (hypercare-এর পর, যাতে প্রথম দিনেই সবাইকে reminder না যায়) |
| Migration-এর শিক্ষা — অন্য university-র জন্য সাধারণ "Import guide" (CSV import) User Manual-এ | ধাপ ১০ |

---

## 12. খোলা প্রশ্ন

| # | প্রশ্ন | প্রস্তাব |
|---|---|---|
| DMQ-01 *(✔ সমাধান)* | Upload করা file কোথায়? | `https://webbackend.daffodilvarsity.edu.bd/{path}` (DIU confirmed; যাচাই করা) — HTTPS download, server access লাগে না; মোট আকার DR-1-এ মাপা হবে |
| DMQ-05 | Go-live কোন মাসে (admission মৌসুমের বাইরে)? | Admission office-এর সাথে ঠিক করা |
| DMQ-06 | Freeze-এর সময় (কয়েক ঘণ্টা, রাতে) legacy admin বন্ধ রাখা যাবে? | হ্যাঁ ধরে নেওয়া হয়েছে; DR-3-এর পর সঠিক সময় জানানো হবে |
| DBQ-04 | Person merge review কে করবেন? | HR বা Registrar (৭.২) |
| DBQ-05 | ২টি অমিল country id | International Affairs (৭.৮) |
| DBQ-06 | `mps_categories`-এর অর্থ | Web team (৭.৮) |
| TPQ-03 | Legacy site-এর Analytics (শীর্ষ URL) | Web team (৭.৯) |
| APIQ-04 | `webbackend` API-র অন্য consumer আছে কি? | Access log দেখে (Section 9.4) |
