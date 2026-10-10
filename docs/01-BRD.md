# Business Requirements Document (BRD)
## University Website Management System

| বিষয় | তথ্য |
|---|---|
| Document | Business Requirements Document (BRD) |
| Version | 1.8 — Frontend Blade + Livewire, API Release 2-এ (Q-28); ৫৮টি module |
| Date | 2026-10-08 |
| Status | **Approved** (Project Owner, 2026-10-08) |
| Source | [requrment.md](../requrment.md), বর্তমান DIU website database (`project_diu`) |
| Technology Stack | Laravel + nwidart/laravel-modules + Filament |

### Version History

| Version | Date | পরিবর্তন |
|---|---|---|
| 0.1 | 2026-10-07 | প্রথম draft |
| 0.2 | 2026-10-07 | Multi-language, scoped user, entity microsite; core module ১৯টি |
| 0.3 | 2026-10-07 | Hierarchical menu fallback, Office permission model |
| 1.0 | 2026-10-08 | সব প্রশ্ন confirmed; `project_diu` analysis থেকে নতুন module; Administration ও Office আলাদা |
| 1.1 | 2026-10-08 | Centralized Media Library (WordPress-এর মতো), Section 7.6 |
| 1.2 | 2026-10-08 | Context-ভিত্তিক Highlight (BR-CNT-07), Statistics core module C-20 (BR-STAT); মোট ৫৯টি module |
| 1.3 | 2026-10-08 | Default vs Optional module (Section 6.2.1); Publications ও Research Optional; News নতুন site-এই তৈরি হবে (migration নেই) |
| 1.4 | 2026-10-08 | Content Update Reminder (Section 7.10, BR-REM-01 থেকে 06) — বর্তমান DIU system-এর `notification_settings` feature; Notification (C-18)-এর অংশ, module সংখ্যা অপরিবর্তিত |
| 1.5 | 2026-10-08 | M-13 Administration ও M-14 Support Staff একত্র করে **M-13 Administration** (Q-25); M-14 নম্বর খালি; Installable ৩৮, মোট ৫৮ |
| 1.6 | 2026-10-08 | Main site-এ প্রকাশ শুধু Admin-এর সিদ্ধান্ত (BR-CNT-08, Q-26) |
| 1.7 | 2026-10-08 | Publish সরল: `is_active` + `published_at`; `status` ও Approval workflow বাদ (Q-27) |
| 1.8 | 2026-10-08 | Public website Laravel Blade + Livewire; REST API Release 2-এ; API documentation public নয় (Q-28) |

---

## 1. Executive Summary

এটি একটি **Dynamic, Modular University Website Management System**। বিশ্ববিদ্যালয়ের website-এর সব academic, administrative এবং content information একটি centralized **Admin Panel** থেকে পরিচালনা করা যাবে।

System-টির তিনটি মূল বৈশিষ্ট্য:

1. **Developer-নির্ভরতা নেই।** একবার deploy হয়ে গেলে content, design, theme, menu, homepage এবং settings সব Admin Panel থেকে পরিবর্তন করা যাবে।
2. **Installable Module ও Theme।** প্রতিটি feature একটি আলাদা module। Super Admin module ও theme ZIP upload করে install, activate, deactivate, update এবং uninstall করতে পারবে।
3. **Extensible।** Documentation follow করে যে কেউ নতুন module বা theme বানাতে পারবে, এবং সেটি core বা অন্য module-এর সাথে conflict করবে না।

প্রতিটি university-র জন্য আলাদা installation হবে, কিন্তু সবগুলো একই core system ব্যবহার করবে।

---

## 2. Business Problem

| সমস্যা | প্রভাব |
|---|---|
| ছোট পরিবর্তনের জন্যও (notice, menu, banner, color) developer লাগে | সময় ও খরচ বাড়ে, তথ্য দেরিতে publish হয় |
| Faculty, Department, Program-এর তথ্য বিভিন্ন জায়গায় ছড়ানো এবং duplicate | তথ্য অসামঞ্জস্যপূর্ণ হয় |
| প্রতিটি নতুন অংশের জন্য আলাদা hardcoded table ও code (যেমন বর্তমান system-এ `registrar_offices`, `proctor_offices`, `faculty_menus`, `department_menus`, `program_menus` আলাদা আলাদা) | নতুন Office বা অংশ যোগ করতে developer লাগে, maintenance কঠিন |
| Design পরিবর্তন করতে হলে নতুন করে development লাগে | Rebranding ব্যয়বহুল |
| কে কখন কী পরিবর্তন করেছে তার পূর্ণাঙ্গ রেকর্ড থাকে না | Accountability থাকে না |
| ভুলবশত delete হওয়া data ফেরত আনা যায় না | Data হারিয়ে যায় |

---

## 3. Business Objectives

| ID | Objective | পরিমাপযোগ্য লক্ষ্য |
|---|---|---|
| BO-01 | Developer ছাড়াই website পরিচালনা | Go-live-এর পর routine কাজের ১০০% Admin Panel থেকে করা যাবে |
| BO-02 | Modular ও extensible system | নতুন module যোগ করতে core code-এ ০টি পরিবর্তন লাগবে |
| BO-03 | একক তথ্যভাণ্ডার (single source of truth) | Faculty / Department / Program-এর তথ্য একবার entry দিলে সব জায়গায় দেখা যাবে |
| BO-04 | Code ছাড়াই design পরিবর্তন | Theme, color, typography এবং layout Admin Panel থেকে পরিবর্তন করা যাবে |
| BO-05 | Accountability ও data নিরাপত্তা | সব পরিবর্তন log হবে (ন্যূনতম ৬০ দিন) এবং delete করা data restore করা যাবে |
| BO-06 | Reusability | একই core দিয়ে একাধিক university-র website deploy করা যাবে (আলাদা installation) |

---

## 4. Scope

### 4.1 In Scope

* Public university website (frontend), multiple theme ও layout সহ
* Admin Panel (Filament)
* Core system এবং installable module system
* Module Manager ও Theme Manager (Super Admin, ZIP upload)
* Role ও Permission ভিত্তিক access, এবং Faculty / Department / Program-ভিত্তিক scoped user
* Faculty, Department, Program, Office ও Lab / Research Center-এর নিজস্ব microsite (আলাদা menu, template, header, footer, fallback সহ)
* Multi-language content
* Polymorphic content relation (যেমন News ↔ Faculty / Department / Program / Course)
* Activity / Audit Log
* SEO, Media Library, Menu, Homepage builder
* Module ও Theme Development Documentation
* Public website: Laravel Blade theme + Livewire *(Q-28)*
* REST API — **Release 2** (Release 1-এ নেই; contract: API Specification) *(Q-28)*
* বর্তমান DIU database (`project_diu`) থেকে data migration-এর পরিকল্পনা *(বিস্তারিত Database Design phase-এ)*

### 4.2 Out of Scope (এই phase-এ নয়) — confirmed (Q-10)

* Student Information System (enrollment, result, attendance)
* Online admission application ও payment gateway *(Admission তথ্য প্রদর্শন in scope, Section 6.2)*
* Learning Management System (LMS)
* HR / Payroll / Accounting
* Student, teacher বা alumni-র login portal
* Mobile app
* Multi-tenant (একটি installation-এ একাধিক university)
* Online module marketplace / repository
* Exam Seat Plan ও Event Invitation & Feedback (বর্তমান system-এ আছে, এই phase-এ নয়)

> এগুলো পরবর্তীতে আলাদা installable module হিসেবে যোগ করা যাবে, কারণ architecture সেভাবেই design করা হচ্ছে।

---

## 5. Stakeholders ও User Roles

### 5.1 Stakeholders

| Stakeholder | আগ্রহ |
|---|---|
| University Authority | সঠিক, আপডেটেড এবং পেশাদার website |
| IT / Web Admin Team | সহজ পরিচালনা, কম developer-নির্ভরতা |
| Faculty / Department / Program Office | নিজের অংশের content নিজে আপডেট করা |
| Administrative Office (Registrar, Exam, Library ইত্যাদি) | নিজের Office-এর page, menu ও content নিজে পরিচালনা |
| Module / Theme Developer | পরিষ্কার documentation ও conflict-free extension |
| Website Visitor (ছাত্র, অভিভাবক, শিক্ষক, গবেষক) | সহজে তথ্য খুঁজে পাওয়া |

### 5.2 System Roles

| Role | ক্ষমতা |
|---|---|
| **Super Admin** | সবকিছু, এবং শুধুমাত্র এই role module ও theme install / activate / deactivate / uninstall, permanent delete ও log settings পরিবর্তন করতে পারবে |
| **Admin** | সব content ও settings পরিচালনা (module ও theme manager বাদে) |
| **Content Manager / Editor** | Assigned module-এর content create, edit ও publish |
| **Scoped User** (Faculty / Department / Program) | শুধু নিজের assigned entity (এবং তার অধীন entity)-র content, menu ও microsite settings পরিচালনা (Section 5.3) |
| **Office / Lab Role** (যেমন Registrar Office Staff) | যে Office বা Lab-এর permission role-এ দেওয়া আছে, শুধু সেটি পরিচালনা (Section 5.4) |
| **Visitor** | Public website দেখা, inquiry ও newsletter form পূরণ |

> Role ও permission dynamic হবে। Super Admin Admin Panel থেকে নতুন role তৈরি করে permission assign করতে পারবে।

### 5.3 Scoped User নিয়ম (Faculty / Department / Program)

* প্রতিটি Faculty, Department ও Program-এর জন্য আলাদা user assign করা যাবে।
* **একটি entity-র একাধিক user থাকতে পারবে** (যেমন CSE Department-এর ৩ জন content manager)।
* একজন user একাধিক entity-তে assign হতে পারবে (যেমন একজন user দুটি Program পরিচালনা করেন)।
* Scoped user-এর ক্ষমতা = **Role-এর permission ∩ assigned entity scope**। অর্থাৎ role ঠিক করবে *কী* করতে পারবে, আর scope ঠিক করবে *কোন entity-র* data-তে করতে পারবে।
* নতুন content তৈরি করলে তা automatically নিজের scope-এর সাথে যুক্ত হবে।
* **Scope নিচের দিকে প্রযোজ্য:** Faculty user তার অধীন সব Department ও সেগুলোর সব Program access করতে পারবে। Department user তার সব Program access করতে পারবে।
* Scope উপরের দিকে বা পাশের দিকে প্রযোজ্য নয়। যেমন Department user তার Faculty বা অন্য Department-এর data দেখতে পারবে না।

```
Faculty user      → Faculty + সব Department + সব Program
Department user   → Department + তার সব Program
Program user      → শুধু ওই Program
```

### 5.4 Office ও Lab Access (Permission-ভিত্তিক)

Office ও Lab / Research Center-এর access scope দিয়ে নয়, **module permission দিয়ে** নিয়ন্ত্রিত হবে:

* Office module (এবং Lab module) নিজের permission নিজেই register করবে।
* **প্রতিটি Office-এর জন্য আলাদা permission** automatically তৈরি হবে যখন Office তৈরি করা হবে (যেমন `office.registrar.manage`, `office.exam.manage`)। এতে Registrar Office-এর staff Exam Office-এর data পরিবর্তন করতে পারবে না। Lab-এর ক্ষেত্রেও একই নিয়ম।
* Super Admin একটি role তৈরি করবে (যেমন "Registrar Office Staff") এবং সেই role-এ প্রয়োজনীয় permission দেবে।
* যে user-কে ওই role দেওয়া হবে, সে ওই Office access করতে পারবে। Role-এ permission না থাকলে access পাবে না।

---

## 6. Module Inventory (Final List)

Original list-এ ৫৪টি item ছিল। Duplicate এবং ছোট configuration item একত্র করা হয়েছে, এবং বর্তমান DIU database (`project_diu`) analysis করে প্রয়োজনীয় নতুন module যোগ করা হয়েছে।

> **মোট ৫৮টি module = ২০টি Core + ৩৮টি Installable** (M-14 নম্বর খালি — Q-25)

### 6.1 Core Modules (সবসময় চালু থাকবে, deactivate বা uninstall করা যাবে না) — ২০টি

| # | Core Module | অন্তর্ভুক্ত |
|---|---|---|
| C-01 | Module Manager | Module install / activate / deactivate / update / uninstall |
| C-02 | User Management | 48 |
| C-03 | Role & Permission | 49, entity scope |
| C-04 | Activity / Audit Log | 53 |
| C-05 | Website Settings | 50, 36 Contact Information, 39 Social Media, default images, module visibility |
| C-06 | Theme Management | 40–45 (Theme install, Layout, Module Layout Selection, Color, Typography, Logo & Branding) |
| C-07 | Media Library (WordPress-এর মতো centralized gallery; Section 7.6) | 47 |
| C-08 | Menu Management | 31, 34 Quick Links; main site ও entity-specific menu |
| C-09 | Header & Footer | 32, 33 |
| C-10 | Homepage Builder | 35, 46 |
| C-11 | Pages | 15; Faculty / Department / Program / Office article (entity relation সহ) |
| C-12 | SEO & Redirect | 51, 52, sitemap |
| C-13 | Content Relation Engine | Polymorphic relation (infrastructure) |
| C-14 | API Management | 54 |
| C-15 | Localization (Multi-language) | নতুন |
| C-16 | Taxonomy (Category & Tag) | নতুন |
| C-17 | Global Search | নতুন |
| C-18 | Notification | নতুন |
| C-19 | Backup | নতুন |
| C-20 | Statistics (Main Site ও প্রতিটি entity-র counter) | নতুন (Q-20) |

### 6.2 Installable Modules — ৩৮টি

**A. Academic (১১টি)**

| # | Module | Dependency | বর্তমান DB-তে |
|---|---|---|---|
| M-01 | Faculty / School | — | `faculties` |
| M-02 | Department | Faculty | `departments` |
| M-03 | Program (major, career path, industry, facilitator, material, eligibility সহ) | Department | `department_program_type`, `program_*` |
| M-04 | Course | Program | — |
| M-05 | Academic Session / Semester | — | `semesters` |
| M-06 | Curriculum | Program, Course | — |
| M-07 | Faculty Member / Teacher | Department | — |
| M-08 | Campus / Location | — | — |
| M-09 | Admission Info (notice, eligibility, admission test, result, contact, subscriber) | — (optional: Program) | `admission_*` |
| M-10 | Tuition, Waiver & Scholarship (waiver calculator সহ) | Program | `tuitions`, `waivers`, `scholarships`, `boards` |
| M-11 | Academic Calendar | — (optional: Program) | `calenders`, `calendar_events` |

**B. Administration & People (৫টি)**

| # | Module | Dependency | বর্তমান DB-তে |
|---|---|---|---|
| M-12 | Office / Administrative Unit (VC Office, Registrar, Exam, Library, IT ইত্যাদি; Section / sub-unit সহ)। **স্বতন্ত্র entity**, নিজস্ব microsite, menu ও template | — | `offices`, `sections`, `registrar_offices`, `proctor_offices` |
| M-13 | **Administration** — শিক্ষক ছাড়া সব employee: উচ্চ ব্যবস্থাপনা, কর্মকর্তা ও সহায়ক কর্মী, ক্রমযুক্ত category সহ (Management, Director, Registrar, Officer … Attendant, Driver, Security, Cleaner); একই ব্যক্তি Assignment দিয়ে বিভিন্ন জায়গায় বিভিন্ন ভূমিকায় *(Q-25)* | — (optional: Office) | `admins`, `admin_categories`, `staff`, `staff_categories`, `office_admins`, `section_employees` |
| M-14 | *একত্রিত — M-13-এ (নম্বর খালি)* | — | — |
| M-15 | Leadership / Authorities | — | — |
| M-16 | Committee (Board of Trustees, Syndicate, Academic Council ইত্যাদি) | — | `committees`, `committee_categories` |
| M-17 | Alumni | — (optional: Program) | `alumnis` |

> **Administration ও Office আলাদা (confirmed, Q-08, Q-25):** Office হলো একটি প্রতিষ্ঠানিক unit (যার নিজস্ব page ও menu আছে), আর Administration হলো মানুষের তালিকা। একজন ব্যক্তি একাধিক Office, Section, Committee বা পদে বিভিন্ন ভূমিকায় যুক্ত হতে পারেন।

**C. Website Content (১৩টি)**

| # | Module | Polymorphic Attachable | একত্রিত (confirmed) |
|---|---|---|---|
| M-18 | News | ✔ | Blog post |
| M-19 | Events | ✔ | |
| M-20 | Notice / Announcement | ✔ | |
| M-21 | Testimonials | ✔ | |
| M-22 | Gallery (Photo Album + Video) | ✔ | Photo Album, Video Gallery, Documentary, Lecture Series |
| M-23 | Slider / Banner | ✔ | Cover photo |
| M-24 | Achievements | ✔ | |
| M-25 | Publications **(Optional — default inactive)** | ✔ (optional: Faculty Member) | |
| M-26 | Research **(Optional — default inactive)** | ✔ (optional: Faculty Member) | |
| M-27 | Downloads / Documents | ✔ | Prospectus, Flipbook, Charter / Guidebook, Policy |
| M-28 | FAQ | ✔ | Accordion |
| M-29 | Important Links | — | |
| M-30 | Announcement Widgets | ✔ | Greetings popup, News Ticker, Countdown Timer |

**D. Campus & Services (৩টি)**

| # | Module | Dependency | বর্তমান DB-তে |
|---|---|---|---|
| M-31 | Transport (route, stop, schedule, service) | — | `roads`, `stops`, `schedules`, `road_*`, `transport_*` |
| M-32 | Lab / Research Center। **স্বতন্ত্র entity**, নিজস্ব microsite (menu, facility, tool, project, scope, gallery, committee) | — (optional: Department) | `lab_*`, `mps` |
| M-33 | Facilities | — | `facilities` |

**E. Reputation & Partnership (৪টি)**

| # | Module | বর্তমান DB-তে |
|---|---|---|
| M-34 | Accreditation & Ranking | `accreditations`, `rankings` |
| M-35 | International Linkage & Conference | `international_linkages`, `international_conferences` |
| M-36 | CSR | `csrs` |
| M-37 | Partners (Featured, Discount) | `featured_partners`, `discount_partners` |

**F. Communication (২টি)**

| # | Module | বর্তমান DB-তে |
|---|---|---|
| M-38 | Contact / Inquiry | `contacts`, `support_contacts` |
| M-39 | Newsletter / Subscription | `newsletters` |

### 6.2.1 Default ও Optional Installable Module

নতুন installation-এর সময় installable module দুই ধরনের অবস্থায় থাকবে:

| ধরন | অবস্থা | Module |
|---|---|---|
| **Default** | Install ও **active** | Publications ও Research বাদে সব installable module |
| **Optional** | Install থাকবে কিন্তু **inactive**; প্রয়োজন হলে Super Admin activate করবেন | M-25 Publications, M-26 Research *(confirmed, Q-22)* |

> Module-এর manifest (`module.json`)-এ `default_active: true / false` দিয়ে এটি নির্ধারিত হবে। নতুন third-party module default-এ inactive অবস্থায় install হয় (BR-MOD-02)।

### 6.3 Relation Targets

যে module-গুলোর সাথে content attach করা যাবে: **Faculty, Department, Program, Course, Office, Lab / Research Center, Campus**। ভবিষ্যতে নতুন module নিজেকে target হিসেবে register করতে পারবে।

### 6.4 Microsite Entities

যে entity-গুলোর নিজস্ব microsite (আলাদা menu, template ও homepage) থাকবে: **Faculty, Department, Program, Office, Lab / Research Center**। বিস্তারিত Section 7.4-এ।

---

## 7. Business Requirements

### 7.1 Module ও Theme System

| ID | Requirement | Priority |
|---|---|---|
| BR-MOD-01 | Core ছাড়া প্রতিটি feature একটি আলাদা installable module হবে | Must |
| BR-MOD-02 | Super Admin Admin Panel থেকে **ZIP upload** করে module install, activate, deactivate, update এবং uninstall করতে পারবে | Must |
| BR-MOD-03 | Module deactivate করলে তার data মুছবে না। আবার activate করলে আগের অবস্থায় ফিরে আসবে | Must |
| BR-MOD-04 | Dependency active না থাকলে module activate হবে না। অন্য active module যার উপর নির্ভর করে, সেটি deactivate হবে না | Must |
| BR-MOD-05 | নতুন module core বা অন্য module-এর code পরিবর্তন ছাড়াই যোগ করা যাবে | Must |
| BR-MOD-06 | Install-এর সময় নাম, table, route ও permission conflict check হবে। Conflict থাকলে install বাতিল হবে | Must |
| BR-MOD-07 | Install বা update-এর আগে backup নেওয়া হবে, এবং ব্যর্থ হলে system আগের অবস্থায় ফিরে যাবে (rollback) | Must |
| BR-MOD-08 | Module Development Documentation থাকবে, যা পড়ে একজন Laravel developer নতুন module বানাতে পারবে | Must |
| BR-THM-06 | Theme-ও ZIP upload করে install, activate ও uninstall করা যাবে, এবং Theme Development Guide থাকবে *(confirmed, Q-15)* | Must |

### 7.2 Academic ও Content Management

| ID | Requirement | Priority |
|---|---|---|
| BR-CNT-01 | Faculty, Department, Program, Course, Faculty Member ইত্যাদি Admin Panel থেকে create, update ও publish করা যাবে | Must |
| BR-CNT-02 | একটি content (News, Event, Notice, Gallery ইত্যাদি) এক বা একাধিক Faculty, Department, Program, Course বা অন্য target-এর সাথে যুক্ত করা যাবে | Must |
| BR-CNT-03 | Faculty বা Department page-এ তার সাথে যুক্ত News, Events, Notice ইত্যাদি automatically দেখাবে | Must |
| BR-CNT-04 | Content চালু / বন্ধ হবে `is_active` দিয়ে; প্রকাশের তারিখ (`published_at`) থাকবে — website-এ এই তারিখ দেখাবে ও এই অনুযায়ী ক্রম হবে; ভবিষ্যতের তারিখ দিলে সেই সময়ে automatically দেখাবে (schedule)। আলাদা Draft / Review status থাকবে না *(Q-27)* | Must |
| BR-CNT-05 | Content-এ SEO meta (title, description, image) দেওয়া যাবে | Should |
| BR-CNT-06 | ~~ঐচ্ছিক Approval Workflow~~ — **বাদ** *(Q-27: শুধু `is_active` + `published_at`; Q-04 বাতিল)* | — |
| BR-CNT-07 | **Context-ভিত্তিক Highlight:** সব content পূর্ণ listing page-এ থাকবে, কিন্তু homepage-এ (main ও প্রতিটি microsite) শুধু highlight করাগুলো দেখাবে। প্রতিটি context-এ আলাদা highlight, ক্রম ও মেয়াদ; main homepage-এ highlight শুধু Admin করতে পারবে *(Q-19)* | Must |
| BR-CNT-08 | **Main site-এ প্রকাশ:** Faculty / Department / Program / Office / Lab user-রা শুধু নিজের entity-তে content প্রকাশ করবেন। কোন content main site-এর listing ও homepage-এও দেখাবে, তা **শুধু Admin** ঠিক করবেন (বর্তমান `is_home`-এর আসল অর্থ) *(Q-26)* | Must |
| BR-STAT-01 | Main homepage এবং প্রতিটি Faculty, Department, Program, Office ও Lab-এর নিজস্ব statistics counter থাকবে *(Q-20)* | Must |
| BR-STAT-02 | Counter-এর মান manual অথবা automatic (module-এর data থেকে, entity অনুযায়ী গণনা) হতে পারবে | Must |

### 7.3 Theme, Design ও Settings

| ID | Requirement | Priority |
|---|---|---|
| BR-THM-01 | একাধিক frontend theme থাকবে। Super Admin বা Admin একটি theme activate করতে পারবে | Must |
| BR-THM-02 | প্রতিটি module-এর একাধিক layout থাকবে (যেমন News → Grid / List / Card / Magazine), যা Admin Panel থেকে select করা যাবে | Must |
| BR-THM-03 | Color, typography, logo ও favicon central settings থেকে পরিবর্তন করলে সব module ও layout-এ automatically apply হবে | Must |
| BR-THM-04 | Homepage section-গুলো Admin Panel থেকে যোগ, বাদ, সাজানো ও configure করা যাবে | Must |
| BR-THM-05 | Menu, header, footer, contact information, social media ও SEO settings Admin Panel থেকে পরিচালনা করা যাবে | Must |

### 7.4 Entity Microsite (Faculty / Department / Program / Office / Lab)

প্রতিটি Faculty, Department, Program, Office ও Lab-এর website-এর ভেতরে নিজস্ব একটি অংশ (microsite) থাকবে, যেমন `/faculties/fsit`, `/fsit/cse`, `/fsit/cse/bsc-in-cse`, `/office/registrar`, `/fsit/cse/lab/robotics` (URL কাঠামো: SRS FR-GEN-34, 2026-10-10)।

| ID | Requirement | Priority |
|---|---|---|
| BR-MS-01 | Office ও Lab **স্বতন্ত্র entity** হবে, এবং তাদের নিজস্ব microsite থাকবে | Must |
| BR-MS-02 | Main site-এর একটি **default menu** থাকবে | Must |
| BR-MS-03 | প্রতিটি microsite entity-র জন্য **আলাদা menu set** তৈরি করা যাবে (ঐচ্ছিক) | Must |
| BR-MS-04 | **Hierarchical fallback:** কোনো entity-র নিজস্ব menu থাকলে সেটি দেখাবে। না থাকলে নিকটতম parent-এর menu দেখাবে, এবং কোনো parent-এর menu না থাকলে main site-এর menu দেখাবে *(confirmed, Q-11)* | Must |
| BR-MS-05 | প্রতিটি microsite-এর জন্য আলাদা **template / layout design** select করা যাবে। Select না করলে একই fallback নিয়মে parent বা main site-এর template ব্যবহার হবে | Must |
| BR-MS-06 | Microsite-এর template আলাদা হলেও main theme-এর color, typography ও branding বজায় থাকবে। প্রয়োজনে entity-ভিত্তিক logo ও banner দেওয়া যাবে | Should |
| BR-MS-07 | প্রতিটি microsite-এর নিজস্ব homepage section থাকবে (যেমন ওই Department-এর News, Events, Notice, Faculty Members) | Should |
| BR-MS-08 | Header ও footer-ও একইভাবে entity-ভিত্তিক override করা যাবে | Should |
| BR-MS-09 | Scoped user বা Office / Lab role-এর user নিজের entity-র menu, template ও microsite settings পরিচালনা করতে পারবে | Must |
| BR-MS-10 | সব entity-র menu একটি **একক Menu system** দিয়ে পরিচালিত হবে (বর্তমান system-এর মতো entity অনুযায়ী আলাদা table নয়), ফলে নতুন microsite entity যোগ করলে নতুন code লাগবে না | Must |

**Fallback নিয়ম (menu, template, header, footer সবক্ষেত্রে একই):**

```
Program     → নিজের → Department → Faculty → Main site
Department  → নিজের → Faculty → Main site
Faculty     → নিজের → Main site
Office      → নিজের → Main site
Lab         → নিজের → Main site
```

উদাহরণ: Engineering Faculty-র নিজস্ব menu আছে, কিন্তু CSE Department ও BSc in CSE Program-এর নেই। তাহলে CSE ও BSc in CSE-এর page-এ Engineering Faculty-র menu দেখাবে। এতে visitor বুঝতে পারবে সে কোন Faculty-র ভেতরে আছে।

> এটি microsite আছে এমন university CMS-এর standard pattern। একটি নির্দিষ্ট entity চাইলে "Main site menu ব্যবহার করুন" option select করে parent-কে skip করতে পারবে।

### 7.5 Localization (Multi-language)

| ID | Requirement | Priority |
|---|---|---|
| BR-LANG-01 | Website একাধিক ভাষা support করবে। Admin Panel থেকে ভাষা যোগ, active ও default ভাষা নির্ধারণ করা যাবে | Must |
| BR-LANG-02 | সব content (News, Pages, Program ইত্যাদি), menu, settings ও SEO meta প্রতিটি ভাষায় আলাদাভাবে লেখা যাবে | Must |
| BR-LANG-03 | কোনো content-এর translation না থাকলে default ভাষার content দেখাবে | Must |
| BR-LANG-04 | Frontend-এ language switcher থাকবে, এবং URL-এ ভাষা নির্দেশক থাকবে (যেমন `/en/news`, `/bn/news`) | Should |
| BR-LANG-05 | নতুন module-ও যেন translatable field ব্যবহার করতে পারে, সে জন্য core একটি standard ব্যবস্থা দেবে | Must |

### 7.6 Media Library (WordPress-এর মতো)

System-এ যত file upload হবে (image, PDF, document, video, audio ইত্যাদি), যে module থেকেই হোক না কেন, সব একটি **centralized Media Library**-তে জমা হবে। WordPress-এর Media Library-র মতো সেখানে gallery আকারে দেখা ও পরিচালনা করা যাবে।

| ID | Requirement | Priority |
|---|---|---|
| BR-MED-01 | যেকোনো module-এ (News, Notice, Program, Documents ইত্যাদি) upload করা সব file Media Library-তে automatically জমা হবে। কোনো module আলাদা জায়গায় file রাখবে না | Must |
| BR-MED-02 | **Grid view** (thumbnail gallery) ও **List view** (নাম, type, size, upload-কারী, তারিখ) থাকবে | Must |
| BR-MED-03 | Image-এর thumbnail দেখাবে। PDF, Word, Excel, video ও অন্যান্য file-এর জন্য type অনুযায়ী icon দেখাবে | Must |
| BR-MED-04 | Drag & drop করে একসাথে একাধিক file upload করা যাবে (bulk upload) | Must |
| BR-MED-05 | **Media Picker:** যেকোনো form-এ (News-এর featured image, Notice-এর attachment ইত্যাদি) "Media Library থেকে select করুন" অথবা "নতুন upload করুন" option থাকবে। ফলে একই file একাধিক জায়গায় আবার upload না করেই ব্যবহার করা যাবে | Must |
| BR-MED-06 | Filter ও search: file type (image / document / video / audio), upload তারিখ (মাস ও বছর), upload-কারী, folder এবং নাম অনুযায়ী | Must |
| BR-MED-07 | প্রতিটি file-এর details panel-এ preview, title, alt text, caption ও description edit করা যাবে। এগুলো multi-language হবে | Must |
| BR-MED-08 | File URL copy, download এবং replace (একই URL রেখে নতুন file দিয়ে বদলানো) করা যাবে | Should |
| BR-MED-09 | Folder বা collection তৈরি করে file সাজানো যাবে | Should |
| BR-MED-10 | **Usage tracking:** কোন file কোন content-এ ব্যবহৃত হয়েছে তা দেখা যাবে। ব্যবহৃত file delete করার আগে সতর্কবার্তা দেখাবে | Must |
| BR-MED-11 | Upload করা image-এর বিভিন্ন size (thumbnail, medium, large) এবং WebP version automatically তৈরি হবে, এবং প্রয়োজনে image compress হবে | Should |
| BR-MED-12 | Basic image editing: crop, rotate, resize | Could |
| BR-MED-13 | Allowed file type ও সর্বোচ্চ file size Super Admin settings থেকে নির্ধারণ করবে। Executable বা ঝুঁকিপূর্ণ file (php, exe, js ইত্যাদি) upload করা যাবে না | Must |
| BR-MED-14 | Media-ও soft delete হবে (Trash থেকে restore করা যাবে), এবং upload, edit ও delete activity log-এ যাবে | Must |
| BR-MED-15 | **Access:** Admin সব media দেখতে পারবে। Scoped user এবং Office / Lab user নিজের scope-এর ও নিজের upload করা media দেখতে পারবে, সাথে "Shared" হিসেবে চিহ্নিত media-ও দেখতে পারবে | Must |
| BR-MED-16 | নতুন module Media Picker ও Media Library ব্যবহার করবে core-এর একটি standard component-এর মাধ্যমে, যাতে নিজেকে আলাদা কিছু বানাতে না হয় | Must |

> **Gallery module (M-22) থেকে পার্থক্য:** Media Library হলো Admin Panel-এর ভেতরের file ভাণ্ডার, যেখানে সব file থাকে। Gallery module হলো public website-এ দেখানোর জন্য album, যা Media Library-র image ও video ব্যবহার করে তৈরি হয়।

### 7.7 Data Management Rules (সব Table-এর জন্য প্রযোজ্য)

| ID | Requirement | Priority |
|---|---|---|
| BR-DATA-01 | **সব business table-এ Soft Delete থাকবে।** Delete করা record "Trash"-এ যাবে এবং restore করা যাবে | Must |
| BR-DATA-02 | Permanent delete (force delete) শুধুমাত্র Super Admin করতে পারবে, confirmation সহ | Must |
| BR-DATA-03 | **সব business table-এ `is_active` column থাকবে।** Inactive record frontend-এ দেখাবে না, কিন্তু Admin Panel-এ দেখা যাবে | Must |
| BR-DATA-04 | Content frontend-এ দেখাবে যখন `is_active` চালু **এবং** `published_at` বর্তমান বা অতীত সময় (খালি থাকলে সঙ্গে সঙ্গে)। আলাদা publish status নেই *(Q-27)* | Must |
| BR-DATA-05 | সব business table-এ `created_by`, `updated_by` এবং `deleted_by` থাকবে | Should |
| BR-DATA-06 | এই নিয়মগুলো core-এর একটি base model বা trait দিয়ে enforce করা হবে, যাতে নতুন module-এও automatically প্রযোজ্য হয় | Must |

> **ব্যতিক্রম (confirmed, Q-06):** Pivot / relation table, activity log table এবং framework system table (migrations, jobs, cache, sessions, password reset tokens)-এ soft delete ও `is_active` থাকবে না। এটিই standard practice।

### 7.8 Activity Log ও Audit

| ID | Requirement | Priority |
|---|---|---|
| BR-LOG-01 | সব create, update, delete, restore, force delete, activate ও deactivate action log হবে | Must |
| BR-LOG-02 | Login, logout ও failed login log হবে | Must |
| BR-LOG-03 | Module ও theme install, activate, deactivate, update, uninstall এবং settings পরিবর্তন log হবে | Must |
| BR-LOG-04 | প্রতিটি log-এ থাকবে: user, action, module, record, পুরনো ও নতুন মান (changed fields), IP address, user agent এবং সময় | Must |
| BR-LOG-05 | **Log ন্যূনতম ৬০ দিন সংরক্ষিত থাকবে।** Retention period Super Admin configure করতে পারবে, কিন্তু ৬০ দিনের কম করা যাবে না *(confirmed, Q-09)* | Must |
| BR-LOG-06 | Retention period-এর চেয়ে পুরনো log একটি scheduled job প্রতিদিন automatically মুছে ফেলবে | Must |
| BR-LOG-07 | মুছে ফেলার আগে log file-এ archive (export) করার ঐচ্ছিক setting থাকবে | Could |
| BR-LOG-08 | Admin Panel-এ log দেখা, filter (user / module / action / date) ও export করা যাবে | Should |
| BR-LOG-09 | Log কেউ edit বা manually delete করতে পারবে না (read-only) | Must |

### 7.9 Security ও Access

| ID | Requirement | Priority |
|---|---|---|
| BR-SEC-01 | Role ও permission অনুযায়ী Admin Panel-এর প্রতিটি menu ও action নিয়ন্ত্রিত হবে | Must |
| BR-SEC-02 | প্রতিটি module install-এর সময় নিজের permission automatically register করবে | Must |
| BR-SEC-03 | Module ও theme ZIP validate করা হবে (manifest, file type, path traversal) | Must |
| BR-SEC-04 | Scoped user নিজের scope-এর বাইরের কোনো data দেখতে বা পরিবর্তন করতে পারবে না (Admin Panel ও API দুই জায়গাতেই) | Must |
| BR-SEC-05 | Office / Lab role-এর user শুধু যে Office বা Lab-এর permission পেয়েছে সেটিই পরিচালনা করতে পারবে | Must |

### 7.10 Content Update Reminder

বর্তমান DIU system-এ আছে (`notification_settings`): কোনো Department নির্দিষ্ট সময় ধরে কোনো module-এর content update না করলে তাদের reminder email যায়। নতুন system-এ এটি থাকবে এবং আরও বিস্তৃত হবে *(confirmed, Q-24)*।

| ID | Requirement | Priority |
|---|---|---|
| BR-REM-01 | Admin প্রতিটি module-এর জন্য reminder rule তৈরি করতে পারবে: কোন entity ধরন (Faculty / Department / Program / Office / Lab), সর্বোচ্চ কত দিন update ছাড়া থাকতে পারবে (যেমন ৯০ দিন), এবং কত দিন পর পর reminder যাবে (যেমন ৭ দিন) | Must |
| BR-REM-02 | **Mandatory** rule: প্রতিটি entity-র ওই module-এ অন্তত একটি content থাকতে হবে; একটিও না থাকলে "content নেই" reminder যাবে | Must |
| BR-REM-03 | Check-এর ধরন: (ক) **Latest** — entity-র সর্বশেষ content কবে update হয়েছে; (খ) **Each record** — প্রতিটি record আলাদাভাবে (যেমন প্রতিটি slider বা department article নির্দিষ্ট দিনের বেশি পুরনো হলে) | Must |
| BR-REM-04 | বার্তা চার ধরনের, admin edit করতে পারবে (multi-language): entity user-কে "content নেই" ও "content পুরনো", এবং Admin-কে একই দুটির সারাংশ | Must |
| BR-REM-05 | Reminder যাবে সেই entity-র দায়িত্বপ্রাপ্ত user-দের কাছে (scoped user; Office / Lab-এর ক্ষেত্রে permission থাকা user), email ও in-app notification-এ | Must |
| BR-REM-06 | Admin-এর জন্য report: কোন entity কোন module-এ পিছিয়ে আছে, কত দিন, কতবার reminder গেছে | Should |

---

## 8. Non-Functional Business Requirements

| ID | বিষয় | Requirement |
|---|---|---|
| NFR-01 | Performance | Public page ৩ সেকেন্ডের কম সময়ে load হবে (caching সহ) |
| NFR-02 | Responsive | Mobile, tablet ও desktop-এ সঠিকভাবে দেখাবে |
| NFR-03 | SEO | SEO-friendly URL, meta tags, sitemap.xml ও Open Graph থাকবে |
| NFR-04 | Accessibility | WCAG 2.1 AA মানের কাছাকাছি |
| NFR-05 | Security | OWASP Top 10 থেকে সুরক্ষা, এবং Admin Panel-এ strong password ও optional 2FA |
| NFR-06 | Maintainability | Core ও module আলাদা, coding standard ও documentation অনুসরণ |
| NFR-07 | Availability | ৯৯% uptime (hosting-এর উপর নির্ভরশীল) |
| NFR-08 | Usability | Non-technical user প্রশিক্ষণের পর Admin Panel ব্যবহার করতে পারবে |

---

## 9. Assumptions, Constraints ও Dependencies

**Assumptions**

* প্রতিটি university-র জন্য আলাদা installation (database সহ) হবে *(confirmed, Q-02)*।
* Content entry-র দায়িত্ব university-র নিজস্ব staff-এর।
* বর্তমান DIU database (`project_diu`)-এর data নতুন system-এ migrate করা হবে। Mapping Database Design phase-এ তৈরি হবে।

**Constraints**

* Technology: Laravel, nwidart/laravel-modules, Filament (version SRS-এ নির্ধারণ হবে)
* Hosting-এ PHP, Composer, queue ও scheduler (cron) চালানোর সুবিধা থাকতে হবে, কারণ log cleanup ও backup-এর জন্য scheduler দরকার

**Dependencies**

* Third-party package (spatie/laravel-permission, spatie/laravel-activitylog, spatie/laravel-medialibrary, spatie/laravel-backup, spatie/laravel-translatable ইত্যাদি; final তালিকা SRS-এ)

---

## 10. Risks

| Risk | প্রভাব | প্রতিরোধ |
|---|---|---|
| Third-party module বা theme-এর code খারাপ বা অনিরাপদ | System ভেঙে যেতে বা নিরাপত্তা ঝুঁকি তৈরি হতে পারে | ZIP validation, শুধু Super Admin install করবে, install-এর আগে backup, optional signature |
| Module-এর মধ্যে অতিরিক্ত নির্ভরতা | একটি বন্ধ করলে অন্যগুলো ভেঙে যায় | Dependency check, Relation Engine-এর মাধ্যমে loose coupling |
| Package-এর বড় version upgrade | Compatibility সমস্যা | Manifest-এ `requires_core` version constraint |
| Activity log দ্রুত বড় হয়ে যায় (বর্তমান system-এ ইতিমধ্যে ২২,০০০+ audit log) | Database ধীর হয়ে যায় | Retention cleanup, indexing |
| বর্তমান DB থেকে data migration-এ data হারানো বা ভুল mapping | পুরনো content হারিয়ে যায় | Module অনুযায়ী mapping document, test migration, verification |
| অতিরিক্ত configurable হওয়ায় Admin Panel জটিল হয়ে যায় | Usability কমে | Sensible defaults, role ভিত্তিক menu |

---

## 11. Success Criteria

* Go-live-এর পর routine কাজের জন্য developer লাগবে না
* Documentation follow করে একজন নতুন developer একটি sample module বানিয়ে conflict ছাড়া install করতে পারবে
* যেকোনো installable module deactivate ও re-activate করলে data অক্ষত থাকবে
* Theme পরিবর্তন করলে সব page-এ নতুন design apply হবে
* নতুন Office বা Lab যোগ করলে কোনো code ছাড়াই তার microsite, menu ও permission তৈরি হবে
* কোনো Department-এর নিজস্ব menu set করলে সেটি দেখাবে, আর মুছে ফেললে parent বা main menu-তে ফিরে যাবে
* Scoped user নিজের scope-এর বাইরের data দেখতে পারবে না
* সব পরিবর্তন activity log-এ ন্যূনতম ৬০ দিন দেখা যাবে
* Delete করা যেকোনো record Trash থেকে restore করা যাবে
* System-এ upload করা সব file Media Library-তে gallery আকারে দেখা যাবে, এবং যেকোনো form থেকে আবার upload না করে ব্যবহার করা যাবে

---

## 12. Decisions Log (সব প্রশ্ন confirmed)

| ID | প্রশ্ন | সিদ্ধান্ত |
|---|---|---|
| Q-01 | Website কি multi-language হবে? | হ্যাঁ, multi-language। Localization core-এ (Section 7.5) |
| Q-02 | Multi-tenant, নাকি আলাদা installation? | প্রতিটি university-র জন্য আলাদা installation |
| Q-03 | Faculty, Department বা Program-ভিত্তিক user লাগবে? | হ্যাঁ। প্রতিটি entity-তে একাধিক user থাকতে পারবে (Section 5.3) |
| Q-04 | Approval workflow লাগবে? | ~~ঐচ্ছিক setting হিসেবে~~ → **বাদ** (Q-27) |
| Q-05 | Module install কীভাবে হবে? | শুধু ZIP upload। Marketplace এই phase-এ নয় |
| Q-06 | Pivot, log ও system table-এ soft delete ও `is_active` বাদ? | হ্যাঁ, standard practice |
| Q-07 | Taxonomy, Localization, Search, Notification, Backup যোগ হবে? | হ্যাঁ, core-এ |
| Q-08 | Module একত্রীকরণ? | Gallery + Photo Album + Video একত্রে। **Administration ও Office আলাদা module** |
| Q-09 | Log retention? | ন্যূনতম ৬০ দিন, configurable, archive ঐচ্ছিক |
| Q-10 | Out-of-scope তালিকা? | ঠিক আছে (Section 4.2) |
| Q-11 | Menu fallback? | Standard hierarchical: নিজের → নিকটতম parent → main site |
| Q-12 | Faculty user কি অধীন Department ও Program access করতে পারবে? | হ্যাঁ |
| Q-13 | Office-এর access কীভাবে? | Office module-এর নিজস্ব permission, role-এর মাধ্যমে |
| Q-14 | প্রতিটি Office-এর জন্য আলাদা permission? | হ্যাঁ, Office তৈরি হলে automatically |
| Q-15 | Theme কি ZIP দিয়ে install করা যাবে? | হ্যাঁ |
| Q-16 | বর্তমান DB থেকে কোন অতিরিক্ত module যোগ হবে? | Admission Info, Tuition/Waiver/Scholarship, Academic Calendar, Transport, Lab / Research Center, Facilities, Accreditation & Ranking, International Linkage & Conference, CSR, Partners |
| Q-17 | কোন অংশ existing module-এ একত্রিত হবে? | Prospectus / Flipbook / Guidebook → Documents; Documentary / Lecture Series → Gallery; Accordion → FAQ; Greetings / News Ticker / Countdown → Announcement Widgets; Article / Blog → Pages ও News |
| Q-18 | Upload করা সব file কীভাবে দেখা যাবে? | WordPress-এর মতো centralized Media Library, gallery ও list view সহ (Section 7.6) |
| Q-19 | Homepage-এ সব content না দেখিয়ে শুধু গুরুত্বপূর্ণগুলো (বর্তমান `is_home`) কীভাবে? | Context-ভিত্তিক Highlight, মেয়াদ সহ (BR-CNT-07) |
| Q-20 | Home, Faculty, Department, Program-এর আলাদা statistics লাগবে? | হ্যাঁ, Core module C-20 Statistics (BR-STAT-01/02) |
| Q-21 | News কোথা থেকে আসবে? | বর্তমানে অন্য একটি site থেকে API-র মাধ্যমে আসে; নতুন system-এ News module-এই তৈরি ও প্রকাশ হবে। `project_diu` থেকে news migration নেই |
| Q-22 | কোন module optional? | Publications ও Research: install থাকবে, default-এ inactive (Section 6.2.1) |
| Q-23 | Flipbook কীভাবে? | ZIP-এর বদলে শুধু PDF upload; system online flip view দেখাবে |
| Q-24 | বর্তমান system-এর Content Update Reminder লাগবে? | হ্যাঁ — Notification (C-18)-এর অংশ, সব microsite entity-র জন্য (Section 7.10) |
| Q-25 | Administration ও Support Staff আলাদা directory লাগবে? | না — একটি **Administration** module (`administration_members`; নাম "Staff Directory"-র বদলে "Administration" — user সিদ্ধান্ত), শিক্ষক ছাড়া সব employee; category দিয়ে ভাগ, Assignment দিয়ে বিভিন্ন জায়গায় বিভিন্ন ভূমিকা |
| Q-26 | Entity-র content main site-এর listing-এও দেখাবে? কে ঠিক করবে? | শুধু Admin ঠিক করবেন; entity user শুধু নিজের entity-তে প্রকাশ করবেন (BR-CNT-08) |
| Q-27 | Content প্রকাশে কী কী লাগবে? | শুধু `is_active` (চালু / বন্ধ) + `published_at` (তারিখ দেখানো, ক্রম, ভবিষ্যৎ তারিখে schedule)। `status` (Draft / Review / Published / Scheduled) ও Approval workflow বাদ (BR-CNT-04, BR-DATA-04) |
| Q-28 | Website-এর প্রযুক্তি ও API? | Public website **Laravel Blade theme + Livewire**; Release 1-এ কোনো API নয় (Release 2-এ, contract প্রস্তুত); API documentation public নয় |

---

## 13. SDLC Document Roadmap

BRD sign-off হওয়ার পর নিচের ক্রমে document তৈরি হবে। প্রতিটি document confirm হলে পরেরটি শুরু হবে।

| ধাপ | Document | বিষয়বস্তু |
|---|---|---|
| 1 | **BRD** ✔ Approved | Business need, scope, module inventory |
| 2 | SRS (Software Requirements Specification) ✔ Approved (v1.0) | প্রতিটি module-এর functional requirement, use case, user story ও acceptance criteria |
| 3 | System Architecture Document ✔ Approved (Part A, Part B) | Core vs module architecture, module lifecycle, hooks, relation engine, theme engine, folder structure |
| 4 | Database Design Document ✔ Approved (DB-01 থেকে DB-06) | ERD, data dictionary ও `project_diu` migration mapping, **module অনুযায়ী একটির পর একটি** (Core → Academic → People → Content → Services → Communication) |
| 5 | UI/UX Design Document ✔ Approved (Part A, Part B, [Prototype v3](https://claude.ai/artifact/LYk8PswwPuktGYkXwFHLh5)) | Admin Panel flow, frontend theme ও layout wireframe |
| 6 | API Specification ✔ Approved — Release 2-এর contract ([06-API](06-API/01-API-Specification.md)) | Public ও admin API endpoints |
| 7 | Module ও Theme Development Guide ✔ Approved ([Part A: Module](07-DevGuide/01-Module-Development-Guide.md), [Part B: Theme](07-DevGuide/02-Theme-Development-Guide.md)) | নতুন module ও theme বানানোর developer documentation |
| 8 | Test Plan ও Test Cases ✔ Approved ([08-Test](08-Test/01-Test-Plan.md)) | Module lifecycle, permission ও functional test |
| 9 | Deployment ও Data Migration Plan ✔ Approved ([Deployment](09-Deployment/01-Deployment-Plan.md), [Data Migration](09-Deployment/02-Data-Migration-Plan.md)) | Server requirement, installation, scheduler, backup, `project_diu` থেকে migration |
| 10 | User Manual (Admin) ✔ Approved — screenshot development-এর সময় ([10-User-Manual](10-User-Manual/01-Manual-PartA-Basics.md)) | Non-technical admin-দের জন্য ব্যবহার নির্দেশিকা |

---

## 14. Sign-off

| নাম | ভূমিকা | স্বাক্ষর | তারিখ |
|---|---|---|---|
| | Project Owner | | |
| | Technical Lead | | |
