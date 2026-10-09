# Software Requirements Specification (SRS)
## Part 1 — Core Modules (২০টি)

| বিষয় | তথ্য |
|---|---|
| Document | SRS Part 1: Core Modules |
| Version | 0.2 — FR-REL-09/10 (Person Source, Assignment), FR-HOME-11 (section source mode), C-20 Statistics যোগ |
| Date | 2026-10-08 |
| Status | Review-এর জন্য অপেক্ষমাণ |
| Based on | [BRD v1.1](../01-BRD.md), [SRS Part 0](00-SRS-Overview.md) |

> Part 0-এর Common Requirement (FR-GEN) এখানে আবার লেখা হয়নি। সেগুলো সব core module-এ প্রযোজ্য, শুধু ব্যতিক্রম উল্লেখ করা হয়েছে।

## সূচি

| # | Module | Code |
|---|---|---|
| C-01 | [Module Manager](#c-01-module-manager) | MODM |
| C-02 | [User Management](#c-02-user-management) | USER |
| C-03 | [Role, Permission ও Scope](#c-03-role-permission-ও-scope) | ROLE |
| C-04 | [Activity / Audit Log](#c-04-activity--audit-log) | LOG |
| C-05 | [Website Settings](#c-05-website-settings) | SET |
| C-06 | [Theme Management](#c-06-theme-management) | THEME |
| C-07 | [Media Library](#c-07-media-library) | MEDIA |
| C-08 | [Menu Management](#c-08-menu-management) | MENU |
| C-09 | [Header & Footer](#c-09-header--footer) | HF |
| C-10 | [Homepage Builder](#c-10-homepage-builder) | HOME |
| C-11 | [Pages](#c-11-pages) | PAGE |
| C-12 | [SEO & Redirect](#c-12-seo--redirect) | SEO |
| C-13 | [Content Relation Engine](#c-13-content-relation-engine) | REL |
| C-14 | [API Management](#c-14-api-management) | API |
| C-15 | [Localization](#c-15-localization) | LANG |
| C-16 | [Taxonomy](#c-16-taxonomy) | TAX |
| C-17 | [Global Search](#c-17-global-search) | SRCH |
| C-18 | [Notification](#c-18-notification) | NOTI |
| C-19 | [Backup](#c-19-backup) | BKP |
| C-20 | [Statistics](#c-20-statistics) | STAT |

---

## C-01 Module Manager

**Purpose:** Installable module-এর সম্পূর্ণ lifecycle (install, activate, deactivate, update, uninstall) পরিচালনা।

**Actors:** Super Admin

**Data:** Module name, slug, version, author, description, status (installed / active / inactive), dependency, install তারিখ, version history, install log।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-MODM-01 | সব module-এর তালিকা দেখাবে: নাম, slug, version, author, status, dependency, শেষ update | BR-MOD-02 | Must |
| FR-MODM-02 | ZIP file upload করে নতুন module install করা যাবে | BR-MOD-02 | Must |
| FR-MODM-03 | Install-এর আগে validation: (ক) ZIP গঠন ও `module.json` বাধ্যতামূলক field, (খ) allowed file type ও path traversal, (গ) `requires_core` version, (ঘ) dependency উপস্থিত, (ঙ) slug, table, route ও permission conflict | BR-MOD-06, BR-SEC-03 | Must |
| FR-MODM-04 | Validation fail হলে কোন rule fail করেছে তার স্পষ্ট তালিকা দেখাবে, এবং কিছুই install হবে না | BR-MOD-06 | Must |
| FR-MODM-05 | Install ধাপ: backup → file copy → migration → permission register → default settings seed → status "Installed (Inactive)" | BR-MOD-07 | Must |
| FR-MODM-06 | যেকোনো ধাপে error হলে আগের সব ধাপ rollback হবে (file মুছে ফেলা, migration rollback) | BR-MOD-07 | Must |
| FR-MODM-07 | Activate: dependency active আছে কিনা যাচাই, তারপর route, admin menu, frontend, homepage section, API ও search provider চালু | BR-MOD-04 | Must |
| FR-MODM-08 | Deactivate: অন্য কোনো active module নির্ভর করলে deactivate হবে না এবং নির্ভরশীল module-এর তালিকা দেখাবে | BR-MOD-04 | Must |
| FR-MODM-09 | Deactivate হলে module-এর data, permission ও settings অক্ষত থাকবে | BR-MOD-03 | Must |
| FR-MODM-10 | Update: নতুন version-এর ZIP upload → version তুলনা (downgrade নিষেধ) → backup → নতুন migration → version history-তে রেকর্ড | BR-MOD-02 | Must |
| FR-MODM-11 | Uninstall: শুধু inactive module। Option: "Data রাখুন" বা "Data সহ মুছে ফেলুন"। দ্বিতীয়টির জন্য module-এর slug টাইপ করে confirm করতে হবে | BR-MOD-02 | Must |
| FR-MODM-12 | প্রতিটি module-এর details page: README, permission তালিকা, dependency tree, কোন module এর উপর নির্ভর করে, version history ও install log | BR-MOD-02 | Should |
| FR-MODM-13 | Module অন্য module-এর উপর optional dependency রাখলে, সেই module active না থাকলেও চলবে, শুধু সংশ্লিষ্ট feature লুকানো থাকবে | BR-MOD-04 | Must |
| FR-MODM-14 | Core-এর সাথে আসা (bundled) module-ও এই তালিকায় দেখাবে এবং একইভাবে activate / deactivate করা যাবে | BR-MOD-01 | Must |
| FR-MODM-15 | একটি Artisan command (`module:make-uwms {Name}`) standard structure সহ নতুন module-এর skeleton তৈরি করবে | BR-MOD-08 | Must |
| FR-MODM-16 | নতুন installation-এ bundled module-গুলো manifest-এর `default_active` অনুযায়ী active বা inactive অবস্থায় install হবে (Publications ও Research: inactive); ZIP দিয়ে install হওয়া module সবসময় inactive অবস্থায় শুরু হবে | BRD 6.2.1 | Must |

### Business Rules

* **BRL-MODM-01:** Core module এই তালিকায় "Core" হিসেবে দেখাবে, কিন্তু deactivate বা uninstall করা যাবে না।
* **BRL-MODM-02:** Module-এর `slug` অনন্য, ছোট হাতের অক্ষর, সংখ্যা ও `-` দিয়ে গঠিত, এবং install-এর পর পরিবর্তন করা যাবে না।
* **BRL-MODM-03:** Module-এর table `{slug}_` prefix ছাড়া হলে install বাতিল হবে।
* **BRL-MODM-04:** একই সময়ে একটির বেশি install / update চলবে না (lock)।

### Permissions

`modules.view`, `modules.install`, `modules.activate`, `modules.update`, `modules.uninstall`। Default-এ শুধু Super Admin।

### Acceptance Criteria

* **AC-MODM-01:** একটি valid sample module ZIP upload করলে install হবে এবং "Inactive" দেখাবে; activate করলে তার admin menu ও frontend route পাওয়া যাবে।
* **AC-MODM-02:** Department active থাকা অবস্থায় Faculty deactivate করতে চাইলে প্রক্রিয়া বন্ধ হবে এবং "Department এর উপর নির্ভর করে" দেখাবে।
* **AC-MODM-03:** অন্য module-এর একই slug বা table নামসহ ZIP upload করলে install হবে না এবং conflict-এর বিবরণ দেখাবে।
* **AC-MODM-04:** Migration-এ ইচ্ছাকৃত error থাকা module install করলে system আগের অবস্থায় ফিরবে এবং কোনো নতুন table থাকবে না।
* **AC-MODM-05:** News deactivate → re-activate করলে সব news আগের মতো ফিরে আসবে।

---

## C-02 User Management

**Purpose:** Admin Panel ব্যবহারকারীদের account পরিচালনা।

**Actors:** Super Admin, Admin (user management permission থাকলে), সব user (নিজের profile)

**Data:** নাম, username, email, phone, photo, password, role, scope (entity), ভাষা পছন্দ, 2FA, শেষ login, is_active।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-USER-01 | User create, edit, deactivate, soft delete, restore | BR-SEC-01 | Must |
| FR-USER-02 | User-কে এক বা একাধিক role assign করা যাবে | BR-SEC-01 | Must |
| FR-USER-03 | User-কে এক বা একাধিক entity scope (Faculty / Department / Program) assign করা যাবে | Q-03 | Must |
| FR-USER-04 | Email দিয়ে login; "Forgot password" email link দিয়ে reset | NFR-SEC-02 | Must |
| FR-USER-05 | Inactive user login করতে পারবে না; চলমান session সাথে সাথে বাতিল হবে | BR-DATA-03 | Must |
| FR-USER-06 | User নিজের profile, photo, password, interface ভাষা ও 2FA পরিবর্তন করতে পারবে | NFR-SEC-04 | Must |
| FR-USER-07 | নতুন user তৈরি হলে invitation email যাবে, যেখান থেকে user নিজে password set করবে | — | Should |
| FR-USER-08 | User list-এ role, scope, status ও শেষ login দিয়ে filter | FR-GEN-80 | Must |
| FR-USER-09 | Super Admin অন্য user হিসেবে "Impersonate" করে সমস্যা দেখতে পারবে; এটি log হবে | — | Could |
| FR-USER-10 | Admin চাইলে কোনো user-এর সব active session logout করাতে পারবে | NFR-SEC-07 | Should |

### Business Rules

* **BRL-USER-01:** Email অনন্য।
* **BRL-USER-02:** System-এ অন্তত একজন active Super Admin থাকবে; শেষ Super Admin-কে deactivate, delete বা role পরিবর্তন করা যাবে না।
* **BRL-USER-03:** Admin, Super Admin-কে edit করতে পারবে না।
* **BRL-USER-04:** User নিজেকে delete বা deactivate করতে পারবে না।

### Permissions

`users.view`, `users.create`, `users.update`, `users.delete`, `users.impersonate`

### Acceptance Criteria

* **AC-USER-01:** Deactivated user login করতে চাইলে "Account inactive" বার্তা দেখাবে।
* **AC-USER-02:** শেষ Super Admin-কে delete করার চেষ্টা ব্যর্থ হবে।

---

## C-03 Role, Permission ও Scope

**Purpose:** কে কী করতে পারবে (permission) এবং কোন data-তে করতে পারবে (scope) নিয়ন্ত্রণ।

**Actors:** Super Admin

**Data:** Role, permission (module অনুযায়ী grouped), role-permission mapping, user-scope mapping (user ↔ Faculty / Department / Program)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-ROLE-01 | Role create, edit, delete। Permission module অনুযায়ী grouped checkbox matrix-এ দেখাবে (View / Create / Update / Delete / Publish ইত্যাদি), resource, page ও widget-এর permission সহ — **Filament Shield** দিয়ে (SAD ADR-04, Section 7.4) | BR-SEC-01 | Must |
| FR-ROLE-02 | প্রতিটি module install-এর সময় তার permission automatically register হবে; uninstall (data সহ) হলে মুছে যাবে | BR-SEC-02 | Must |
| FR-ROLE-03 | Inactive module-এর permission role editor-এ ধূসর করে দেখাবে | BR-MOD-03 | Should |
| FR-ROLE-04 | Default role seed হবে: Super Admin, Admin, Content Manager, Faculty Editor, Department Editor, Program Editor | — | Must |
| FR-ROLE-05 | Scope engine: user-এর scope থেকে অধীন entity হিসাব করে (Faculty → Department → Program) query filter প্রয়োগ | Q-12, BR-SEC-04 | Must |
| FR-ROLE-06 | Scope engine একটি contract দেবে, যাতে নতুন module নিজেকে scope-যোগ্য entity হিসেবে register করতে পারে | BR-MOD-05 | Must |
| FR-ROLE-07 | Entity-ভিত্তিক dynamic permission: Office বা Lab তৈরি হলে `office.{slug}.manage` ধরনের permission তৈরি হবে, এবং মুছে ফেললে (permanent) সরে যাবে | Q-14 | Must |
| FR-ROLE-08 | Role duplicate (copy) করে নতুন role তৈরি | — | Should |
| FR-ROLE-09 | কোনো user-এর "effective permission" (সব role মিলিয়ে) ও scope এক জায়গায় দেখা যাবে | — | Should |

### Business Rules

* **BRL-ROLE-01:** Super Admin role সব permission পায় এবং edit বা delete করা যাবে না।
* **BRL-ROLE-02:** যে role কোনো user-এ assign আছে, সেটি delete করার আগে user-দের অন্য role দিতে হবে।
* **BRL-ROLE-03:** Scope না থাকা user (Admin, Content Manager) সব entity-র data দেখবে; scope থাকা user শুধু scope-এর data।
* **BRL-ROLE-04:** Permission ও scope প্রয়োগ Admin Panel, API এবং export সব জায়গায় একইভাবে হবে।

### Permissions

`roles.view`, `roles.create`, `roles.update`, `roles.delete`

### Acceptance Criteria

* **AC-ROLE-01:** Engineering Faculty scope-এর user CSE Department ও BSc in CSE-এর news দেখতে পারবে, কিন্তু Business Faculty-র news দেখবে না।
* **AC-ROLE-02:** CSE Department user EEE-এর একটি news-এর edit URL সরাসরি খুললে 404 পাবে (scope-এর বাইরের record দেখা যায় না, FR-GEN-52)।
* **AC-ROLE-03:** "Registrar Office" তৈরি করলে `office.registrar-office.manage` permission role editor-এ দেখা যাবে।

---

## C-04 Activity / Audit Log

**Purpose:** System-এর সব গুরুত্বপূর্ণ কাজের অপরিবর্তনীয় রেকর্ড।

**Actors:** Super Admin, Admin (view), System (লেখে)

**Data:** User, action, module, record type ও id, পুরনো ও নতুন মান, IP, user agent, সময়।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-LOG-01 | সব model event log হবে (FR-GEN-90) | BR-LOG-01 | Must |
| FR-LOG-02 | Authentication event: login, logout, failed login (চেষ্টা করা email সহ), password reset, 2FA পরিবর্তন | BR-LOG-02 | Must |
| FR-LOG-03 | System event: module ও theme install / activate / deactivate / update / uninstall, settings পরিবর্তন, role ও permission পরিবর্তন, backup | BR-LOG-03 | Must |
| FR-LOG-04 | Log list: user, module, action, তারিখ-পরিসর দিয়ে filter; প্রতিটি log-এর details-এ পুরনো বনাম নতুন মানের তুলনা (diff) | BR-LOG-08 | Must |
| FR-LOG-05 | Filter করা log CSV / Excel-এ export | BR-LOG-08 | Should |
| FR-LOG-06 | Retention setting: ন্যূনতম ৬০, default ৯০ দিন | BR-LOG-05 | Must |
| FR-LOG-07 | প্রতিদিনের scheduled job retention-এর পুরনো log মুছে ফেলবে, এবং cleanup নিজেও log হবে (কতগুলো মুছল) | BR-LOG-06 | Must |
| FR-LOG-08 | Archive setting চালু থাকলে, মুছে ফেলার আগে মাসভিত্তিক compressed file (JSON / CSV) storage-এ রাখবে | BR-LOG-07 | Could |
| FR-LOG-09 | Log-এর কোনো edit বা delete UI থাকবে না; database স্তরেও application শুধু insert করবে | BR-LOG-09 | Must |

### Business Rules

* **BRL-LOG-01:** Retention ৬০-এর কম দেওয়া যাবে না (form validation ও server validation)।
* **BRL-LOG-02:** Password, token, 2FA secret কখনো log হবে না।
* **BRL-LOG-03:** Scoped user Activity Log menu দেখবে না; শুধু নিজের record-এর Activity tab (FR-GEN-83) দেখবে।

### Permissions

`activity-log.view`, `activity-log.export`, `activity-log.settings` (শুধু Super Admin)

### Acceptance Criteria

* **AC-LOG-01:** একটি news-এর title পরিবর্তন করলে log-এ পুরনো ও নতুন title দেখাবে।
* **AC-LOG-02:** Retention ৩০ দিন set করার চেষ্টা validation error দেবে।
* **AC-LOG-03:** ৬১ দিন পুরনো log (retention ৬০) পরের দিনের cleanup-এর পর থাকবে না।

---

## C-05 Website Settings

**Purpose:** Website-এর global configuration এক জায়গায়।

**Actors:** Super Admin, Admin

**Data:** Key-value settings, group অনুযায়ী (module-রা নিজের group যোগ করতে পারে)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-SET-01 | **General:** site নাম, tagline, university-র পূর্ণ নাম, প্রতিষ্ঠার সাল, timezone, তারিখ format | BR-THM-05 | Must |
| FR-SET-02 | **Contact:** ঠিকানা (একাধিক campus), phone, email, Google Map embed, অফিস সময় | BR-THM-05 | Must |
| FR-SET-03 | **Social Media:** Facebook, YouTube, LinkedIn, X, Instagram ইত্যাদি link; প্রয়োজনে নতুন platform যোগ | BR-THM-05 | Must |
| FR-SET-04 | **Default images:** placeholder image, default OG image, default avatar | BR-THM-05 | Must |
| FR-SET-05 | **Module visibility:** কোনো active module-এর frontend লুকানো যাবে (admin-এ চালু থাকবে) | BR-THM-05 | Should |
| FR-SET-06 | **Media:** allowed file type, সর্বোচ্চ upload size, image quality | BR-MED-13 | Must |
| FR-SET-07 | **Mail:** SMTP configuration ও test email পাঠানোর button | — | Must |
| FR-SET-08 | **Maintenance mode:** চালু করলে visitor-রা বার্তা দেখবে, logged-in admin সাইট দেখতে পারবে | — | Should |
| FR-SET-09 | **Analytics ও Custom code:** Google Analytics / Tag Manager ID, header ও footer-এ custom script (শুধু Super Admin) | — | Should |
| FR-SET-10 | Module নিজের settings group register করবে, যা Settings page-এ আলাদা tab হিসেবে দেখাবে (যেমন "News" tab-এ per page সংখ্যা) | BR-MOD-05 | Must |
| FR-SET-11 | Settings-এর text মান multi-language হবে (যেমন site নাম, ঠিকানা) | BR-LANG-02 | Must |
| FR-SET-12 | Settings পরিবর্তনে cache automatically clear হবে | NFR-PERF-04 | Must |
| FR-SET-13 | **Admin Panel Branding:** default neutral admin theme; university নিজের panel name, logo (light / dark), favicon, primary ও অন্যান্য color, font, login page background ও বার্তা বদলাতে পারবে; website branding থেকে এক click-এ কপি করার option; "Reset to neutral" (Super Admin)। Admin-এর layout ও navigation স্থির থাকবে (UI/UX Part A §11) | BO-06 | Should |

### Business Rules

* **BRL-SET-01:** Settings table-এ soft delete ও `is_active` থাকবে না (system configuration table)।
* **BRL-SET-02:** Custom script field শুধু Super Admin দেখতে ও পরিবর্তন করতে পারবে।

### Permissions

`settings.view`, `settings.update`, `settings.advanced` (custom code, mail, maintenance)

### Acceptance Criteria

* **AC-SET-01:** Contact phone পরিবর্তন করলে footer ও contact page-এ সঙ্গে সঙ্গে নতুন নম্বর দেখাবে।
* **AC-SET-02:** News module-এর settings tab News deactivate হলে লুকিয়ে যাবে।

---

## C-06 Theme Management

**Purpose:** Frontend theme ও design configuration পরিচালনা।

**Actors:** Super Admin (install / uninstall), Admin (activate, customize)

**Data:** Theme (name, slug, version, screenshot, supported layout), design token (color, typography, spacing, radius), branding (logo, favicon), module layout selection।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-THEME-01 | Theme তালিকা screenshot সহ; একটি active থাকবে | BR-THM-01 | Must |
| FR-THEME-02 | ZIP upload করে theme install (validation: `theme.json`, file type, path traversal, `requires_core`), update ও uninstall (active theme uninstall করা যাবে না) | BR-THM-06 | Must |
| FR-THEME-03 | Activate করার আগে preview (admin নিজে নতুন theme দেখতে পারবে, visitor নয়) | BR-THM-01 | Should |
| FR-THEME-04 | **Colors:** Primary, Secondary, Accent, Background, Text, Success / Warning / Danger; color picker; WCAG contrast সতর্কবার্তা | BR-THM-03 | Must |
| FR-THEME-05 | **Typography:** heading ও body font (Google Fonts তালিকা বা upload করা font), base size, ভাষা অনুযায়ী font (যেমন বাংলার জন্য আলাদা font) | BR-THM-03 | Must |
| FR-THEME-06 | **Branding:** Logo (light ও dark), favicon, footer logo; প্রতিটি entity-র জন্য ঐচ্ছিক logo | BR-THM-03, BR-MS-06 | Must |
| FR-THEME-07 | Design token CSS variable হিসেবে তৈরি হবে এবং সব layout তা ব্যবহার করবে | BR-THM-03 | Must |
| FR-THEME-08 | **Module Layout Selection:** প্রতিটি module-এর listing ও detail page-এর জন্য available layout থেকে একটি select (যেমন News listing: Grid / List / Card / Magazine) | BR-THM-02 | Must |
| FR-THEME-09 | Layout-এর option (যেমন per row কয়টি item, sidebar দেখাবে কিনা) | BR-THM-02 | Should |
| FR-THEME-10 | View resolution ক্রম: active theme-এর override → module-এর default layout | BR-THM-02 | Must |
| FR-THEME-11 | Theme পরিবর্তন করলে content অক্ষত থাকবে; নতুন theme-এ কোনো layout না থাকলে module-এর default ব্যবহার হবে | BR-THM-01 | Must |
| FR-THEME-12 | Design setting "Reset to theme default" করা যাবে | — | Should |

### Business Rules

* **BRL-THEME-01:** অন্তত একটি default theme core-এর সাথে আসবে এবং uninstall করা যাবে না।
* **BRL-THEME-02:** Theme-এর ভেতরে PHP business logic থাকবে না; শুধু view, asset ও configuration।

### Permissions

`themes.view`, `themes.install`, `themes.activate`, `themes.customize`

### Acceptance Criteria

* **AC-THEME-01:** Primary color পরিবর্তন করলে সব module-এর button ও heading-এ নতুন রং দেখাবে।
* **AC-THEME-02:** News layout "Grid" থেকে "List" করলে `/news` page List আকারে দেখাবে, data অপরিবর্তিত।

---

## C-07 Media Library

**Purpose:** WordPress-এর মতো centralized file ভাণ্ডার (BRD Section 7.6)।

**Actors:** সব admin user (scope ও permission অনুযায়ী)

**Data:** File, নাম, mime type, size, dimension, folder, title / alt / caption / description (multi-language), uploader, scope, shared flag, conversion (thumbnail, medium, large, WebP), usage।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-MEDIA-01 | Grid view (thumbnail) ও List view (নাম, type, size, uploader, তারিখ) toggle | BR-MED-02 | Must |
| FR-MEDIA-02 | Image-এর thumbnail; PDF / Word / Excel / PowerPoint / video / audio / zip-এর জন্য type icon; PDF-এর প্রথম page preview (Could) | BR-MED-03 | Must |
| FR-MEDIA-03 | Drag & drop bulk upload, progress bar সহ; একসাথে ন্যূনতম ৫০টি file | BR-MED-04 | Must |
| FR-MEDIA-04 | **Media Picker** (Filament form component): modal-এ library browse, search, filter, select (single / multiple) অথবা সেখান থেকেই নতুন upload | BR-MED-05 | Must |
| FR-MEDIA-05 | Filter: type, মাস ও বছর, uploader, folder, scope; নাম ও title দিয়ে search | BR-MED-06 | Must |
| FR-MEDIA-06 | Details panel: preview, metadata (size, dimension, URL), title / alt / caption / description edit (ভাষা অনুযায়ী) | BR-MED-07 | Must |
| FR-MEDIA-07 | Copy URL, download, replace file (একই ID ও URL রেখে) | BR-MED-08 | Should |
| FR-MEDIA-08 | Folder create, rename, nested folder, drag করে file move | BR-MED-09 | Should |
| FR-MEDIA-09 | Usage tracking: কোন module-এর কোন record-এ ব্যবহৃত তা দেখাবে, link সহ | BR-MED-10 | Must |
| FR-MEDIA-10 | ব্যবহৃত file delete করলে usage তালিকা সহ সতর্কবার্তা; তারপরও delete করলে soft delete হবে | BR-MED-10, BR-MED-14 | Must |
| FR-MEDIA-11 | Image upload হলে queue-তে thumbnail (150px), medium (768px), large (1600px) ও WebP তৈরি; EXIF location data মুছে ফেলা | BR-MED-11 | Should |
| FR-MEDIA-12 | Crop, rotate, resize (আলাদা copy হিসেবে অথবা original replace) | BR-MED-12 | Could |
| FR-MEDIA-13 | Server-side validation: allowed extension ও প্রকৃত mime type দুটোই মিলতে হবে; executable / script নিষিদ্ধ; SVG sanitize | BR-MED-13 | Must |
| FR-MEDIA-14 | Access: Admin সব; scoped / Office / Lab user নিজের scope-এর, নিজের upload করা ও "Shared" media | BR-MED-15 | Must |
| FR-MEDIA-15 | "Unused media" filter, যাতে অব্যবহৃত file খুঁজে পরিষ্কার করা যায় | BR-MED-10 | Should |
| FR-MEDIA-16 | Duplicate file (একই hash) upload হলে সতর্ক করবে এবং বিদ্যমান file ব্যবহারের প্রস্তাব দেবে | — | Could |

### Business Rules

* **BRL-MEDIA-01:** Upload-এর সময় user-এর scope automatically media-তে বসবে।
* **BRL-MEDIA-02:** Private document (যেমন internal file) public URL ছাড়া signed URL দিয়ে দেওয়ার option থাকবে (Should)।
* **BRL-MEDIA-03:** File-এর নাম slugify করা হবে এবং collision হলে suffix যোগ হবে।

### Permissions

`media.view`, `media.upload`, `media.update`, `media.delete`, `media.manage-all` (scope উপেক্ষা করে সব দেখার জন্য)

### Acceptance Criteria

* **AC-MEDIA-01:** News form-এ featured image হিসেবে library থেকে বিদ্যমান image select করা যাবে, নতুন upload ছাড়াই।
* **AC-MEDIA-02:** ৩টি news-এ ব্যবহৃত একটি image delete করতে গেলে ৩টি news-এর link সহ সতর্কবার্তা দেখাবে।
* **AC-MEDIA-03:** `.php` file upload প্রত্যাখ্যাত হবে, এমনকি extension `.jpg` করে দিলেও।
* **AC-MEDIA-04:** CSE Department user EEE-এর upload করা (shared নয়) image দেখতে পাবে না।

---

## C-08 Menu Management

**Purpose:** Main site ও প্রতিটি microsite entity-র menu একটি একক system দিয়ে পরিচালনা (BR-MS-10)।

**Actors:** Admin, scoped user ও Office / Lab user (নিজের entity-র menu)

**Data:** Menu (নাম, location, owner entity বা main site, ভাষা), menu item (title, type, target, parent, ক্রম, icon, open in new tab, is_active)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-MENU-01 | Menu location: Primary (header), Top bar, Footer columns, Quick Links, Sidebar; theme নতুন location register করতে পারবে | BR-THM-05 | Must |
| FR-MENU-02 | Drag & drop tree builder, nested (ন্যূনতম ৩ স্তর), mega menu support (theme অনুযায়ী) | BR-THM-05 | Must |
| FR-MENU-03 | Menu item type: Page, Module page (যেমন News listing), Module record (যেমন একটি নির্দিষ্ট Program), Taxonomy, Custom URL, File (Media Library থেকে), Heading (link ছাড়া) | BR-THM-05 | Must |
| FR-MENU-04 | Module নিজের menu item type register করতে পারবে, ফলে menu builder-এ সেই module-এর record select করা যাবে | BR-MOD-05 | Must |
| FR-MENU-05 | Menu item-এর title multi-language | BR-LANG-02 | Must |
| FR-MENU-06 | প্রতিটি Faculty, Department, Program, Office ও Lab-এর জন্য ঐচ্ছিক নিজস্ব menu set | BR-MS-03 | Must |
| FR-MENU-07 | Hierarchical fallback resolution (BRL-MENU-01) | BR-MS-04 | Must |
| FR-MENU-08 | Entity-র menu settings-এ option: "নিজস্ব menu" / "Parent থেকে নিন (default)" / "Main site menu ব্যবহার করুন" | BR-MS-04 | Must |
| FR-MENU-09 | Main menu বা parent menu copy করে entity-র নিজস্ব menu শুরু করা যাবে | — | Should |
| FR-MENU-10 | Target record inactive, unpublished বা deleted হলে menu item frontend-এ automatically লুকাবে এবং admin-এ সতর্কতা চিহ্ন দেখাবে | BR-DATA-04 | Must |
| FR-MENU-11 | Target module inactive হলে তার menu item লুকাবে | BR-MOD-03 | Must |
| FR-MENU-12 | Menu cache; menu পরিবর্তনে cache clear | NFR-PERF-04 | Must |

### Business Rules

* **BRL-MENU-01 (Fallback):** Program → নিজের → Department → Faculty → Main site; Department → নিজের → Faculty → Main site; Faculty / Office / Lab → নিজের → Main site। প্রতিটি menu location আলাদাভাবে resolve হবে (যেমন Department-এর নিজস্ব Primary menu আছে কিন্তু Footer menu নেই, তাহলে Footer আসবে parent থেকে)।
* **BRL-MENU-02:** Scoped user শুধু নিজের scope-এর entity-র menu edit করতে পারবে; main site menu শুধু Admin।
* **BRL-MENU-03:** একটি entity-র একটি location-এ একটিই menu থাকবে।

### Permissions

`menus.view`, `menus.manage` (scope অনুযায়ী), `menus.manage-main`

### Acceptance Criteria

* **AC-MENU-01:** Engineering Faculty-র নিজস্ব menu আছে, CSE ও BSc in CSE-এর নেই → BSc in CSE page-এ Engineering-এর menu দেখাবে।
* **AC-MENU-02:** CSE-এর নিজস্ব menu তৈরি করলে CSE ও BSc in CSE page-এ CSE-এর menu দেখাবে; মুছে ফেললে Engineering-এর menu-তে ফিরে যাবে।
* **AC-MENU-03:** Menu-তে link করা একটি page unpublish করলে menu item frontend থেকে লুকাবে।

---

## C-09 Header & Footer

**Purpose:** Header ও footer-এর গঠন ও বিষয়বস্তু পরিচালনা, entity অনুযায়ী override সহ।

**Actors:** Admin, scoped user ও Office / Lab user (নিজের entity)

**Data:** Header / footer variant (theme থেকে), block (logo, menu, contact, social, text, newsletter, quick links), copyright text, top bar, owner entity।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-HF-01 | Active theme-এর available header ও footer variant থেকে select | BR-THM-05 | Must |
| FR-HF-02 | Top bar: phone, email, quick links, language switcher, social icon, login link — প্রতিটি দেখাবে কিনা toggle | BR-THM-05 | Must |
| FR-HF-03 | Footer column builder: column সংখ্যা ও প্রতিটি column-এ block (about text, menu, contact, social, newsletter form, custom HTML) | BR-THM-05 | Must |
| FR-HF-04 | Copyright text (multi-language, `{year}` placeholder সহ) | BR-THM-05 | Must |
| FR-HF-05 | Faculty / Department / Program / Office / Lab নিজস্ব header ও footer override করতে পারবে; না করলে BRL-MENU-01-এর একই fallback | BR-MS-08 | Should |
| FR-HF-06 | Header-এ notice ticker / announcement bar দেখানোর slot (Announcement Widgets module active থাকলে) | — | Should |

### Permissions

`header-footer.manage` (scope অনুযায়ী), `header-footer.manage-main`

### Acceptance Criteria

* **AC-HF-01:** Footer-এ একটি নতুন column যোগ করে Quick Links menu রাখলে সব page-এর footer-এ দেখাবে।

---

## C-10 Homepage Builder

**Purpose:** Main homepage ও প্রতিটি microsite homepage section দিয়ে গঠন।

**Actors:** Admin, scoped user ও Office / Lab user (নিজের microsite homepage)

**Data:** Homepage (owner: main site বা entity), section (type, source module, layout variant, settings, ক্রম, is_active, visibility schedule)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-HOME-01 | Section যোগ, মুছে ফেলা, drag & drop দিয়ে ক্রম পরিবর্তন, active / inactive | BR-THM-04 | Must |
| FR-HOME-02 | Core section type: Hero / Slider, Rich text, Image + text, Statistics counter (C-20), Call to action, Custom HTML, Spacer | BR-THM-04 | Must |
| FR-HOME-03 | Module section type (module register করবে): যেমন Latest News, Upcoming Events, Notice Board, Programs, Testimonials, Gallery, Partners | FR-GEN-102 | Must |
| FR-HOME-04 | প্রতিটি section-এর settings: heading ও subheading (multi-language), কয়টি item, filter (category, featured, related entity), layout variant, background, "View all" link | BR-THM-04 | Must |
| FR-HOME-05 | Microsite homepage-এর module section automatically ওই entity-র related content দেখাবে (যেমন CSE homepage-এর "Latest News" শুধু CSE-related news) | BR-MS-07 | Must |
| FR-HOME-06 | Section-এর visibility schedule (start ও end তারিখ) | — | Should |
| FR-HOME-07 | Save করার আগে preview | BR-THM-04 | Should |
| FR-HOME-08 | Microsite-এর homepage না সাজানো থাকলে theme-এর default microsite template ব্যবহার হবে (About, News, Events, Notice) | BR-MS-05 | Must |
| FR-HOME-09 | Section-এর source module inactive হলে section frontend-এ লুকাবে এবং admin-এ সতর্কতা দেখাবে | BR-MOD-03 | Must |
| FR-HOME-10 | একটি homepage layout template হিসেবে save করে অন্য microsite-এ ব্যবহার | — | Could |
| FR-HOME-11 | Module section-এর source mode: Latest / Highlighted only / Highlighted first, then latest (default) / Manual pick; highlight নেওয়া হবে ওই homepage-এর context থেকে ([Part 4, Section 1.1](04-SRS-Content.md#11-context-ভিত্তিক-highlight-বর্তমান-is_home-এর-উন্নত-রূপ)) | BR-CNT-07 | Must |
| FR-HOME-12 | নতুন installation-এ main homepage-এর default section: Hero Slider, **Statistics** (Main Site Stat Set), Programs, Latest News, Upcoming Events, Notice Board, Testimonials, Partners (Homepage Builder দিয়ে পরিবর্তনযোগ্য) | BR-THM-04, BR-STAT-01 | Must |

### Permissions

`homepage.manage` (scope অনুযায়ী), `homepage.manage-main`

### Acceptance Criteria

* **AC-HOME-01:** Main homepage-এ "Upcoming Events" section-কে "Latest News"-এর উপরে drag করলে frontend-এ ক্রম বদলাবে।
* **AC-HOME-02:** Events module deactivate করলে homepage ভাঙবে না; "Upcoming Events" section লুকিয়ে যাবে।

---

## C-11 Pages

**Purpose:** Static ও তথ্যমূলক page (About, History, Mission, Vision ইত্যাদি), এবং entity-র article page (বর্তমান `faculty_articles`, `department_articles`, `program_articles`, `registrar_offices` ইত্যাদির বদলে)।

**Actors:** Admin, Content Manager, scoped user, Office / Lab user

**Data:** Title, slug, content (block / rich text), excerpt, featured image, parent page, page template, owner entity, attachment, SEO, published_at।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-PAGE-01 | Page create / edit, rich text editor (heading, list, table, image, video embed, file link, button) | BR-THM-05 | Must |
| FR-PAGE-02 | Block-based content (Filament Builder): text, image gallery, accordion, tabs, people card, file list, embed | BR-THM-05 | Should |
| FR-PAGE-03 | Parent-child hierarchy (breadcrumb ও URL-এ প্রতিফলিত) | — | Must |
| FR-PAGE-04 | Page template select (theme থেকে): Default, Full width, With sidebar, Landing | BR-THM-02 | Must |
| FR-PAGE-05 | Owner entity: Main site, অথবা Faculty / Department / Program / Office / Lab। Entity-র page তার microsite URL-এ থাকবে (যেমন `/department/cse/about`) | BR-MS-01 | Must |
| FR-PAGE-06 | Page-এ attachment (Media Library থেকে) যোগ করে নিচে download তালিকা দেখানো | — | Should |
| FR-PAGE-07 | Revision history: শেষ ১০টি version রাখা ও আগের version-এ ফিরে যাওয়া | — | Should |
| FR-PAGE-08 | Page duplicate | — | Should |

### Permissions

`pages.view`, `pages.create`, `pages.update`, `pages.delete`

### Acceptance Criteria

* **AC-PAGE-01:** CSE Department user একটি "Lab Facilities" page তৈরি করলে তা `/department/cse/lab-facilities` URL-এ CSE-এর menu ও template সহ দেখাবে।

---

## C-12 SEO & Redirect

**Purpose:** Search engine optimization ও URL redirect পরিচালনা।

**Actors:** Admin

**Data:** Global SEO settings, per-record SEO meta (FR-GEN-70), redirect (source, destination, type 301 / 302, hit count), 404 log।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-SEO-01 | Global: default meta title pattern (যেমন `{title} | {site_name}`), default description, OG image, verification code (Google, Bing) | NFR-03 | Must |
| FR-SEO-02 | `sitemap.xml` automatically তৈরি, সব active module-এর published record সহ, hreflang সহ; module নিজের sitemap entry register করবে | NFR-SEO-02 | Must |
| FR-SEO-03 | `robots.txt` admin থেকে edit | NFR-SEO-02 | Should |
| FR-SEO-04 | Redirect CRUD: source path, destination (URL বা internal record), 301 / 302, active, hit count | NFR-03 | Must |
| FR-SEO-05 | Redirect CSV import (পুরনো DIU website-এর URL map করার জন্য) | — | Must |
| FR-SEO-06 | 404 log: কোন URL কতবার 404 হয়েছে; সেখান থেকে এক click-এ redirect তৈরি | — | Should |
| FR-SEO-07 | Slug পরিবর্তনে automatic redirect (FR-GEN-32) | NFR-03 | Should |
| FR-SEO-08 | JSON-LD structured data (NFR-SEO-03) | NFR-03 | Should |
| FR-SEO-09 | Redirect chain ও loop প্রতিরোধ (A → B → A save করা যাবে না) | — | Must |

### Permissions

`seo.manage`, `redirects.manage`

### Acceptance Criteria

* **AC-SEO-01:** একটি news-এর slug পরিবর্তনের পর পুরনো URL নতুন URL-এ 301 redirect করবে।
* **AC-SEO-02:** Deactivated module-এর URL sitemap-এ থাকবে না।

---

## C-13 Content Relation Engine

**Purpose:** Module-গুলোকে একে অপরের উপর সরাসরি নির্ভর না করিয়ে content ও entity-র মধ্যে polymorphic সম্পর্ক তৈরি।

**Actors:** System (অন্য module ব্যবহার করে); end user Related To field-এর মাধ্যমে

**Data:** Registry (কোন module target, কোন module attachable), relation (content type ও id ↔ target type ও id)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-REL-01 | Module নিজেকে **Relation Target** হিসেবে register করবে: label, model, hierarchy-তে parent (যেমন Department-এর parent Faculty), scope-যোগ্য কিনা, microsite আছে কিনা | BR-CNT-02 | Must |
| FR-REL-02 | Module নিজেকে **Attachable** হিসেবে register করবে | BR-CNT-02 | Must |
| FR-REL-03 | একটি reusable "Related To" form component (FR-GEN-50), শুধু active target দেখাবে, search সহ | BR-CNT-02 | Must |
| FR-REL-04 | Query API: "এই target-এর সব related content" (যেমন CSE-এর সব news), অধীন entity সহ বা ছাড়া (option) | BR-CNT-03 | Must |
| FR-REL-05 | Target module inactive হলে relation data থাকবে কিন্তু frontend ও Related To field-এ দেখাবে না | BR-MOD-03 | Must |
| FR-REL-06 | Target record permanent delete হলে সংশ্লিষ্ট relation row মুছে যাবে (content থাকবে) | — | Must |
| FR-REL-07 | Entity hierarchy service: কোনো entity-র ancestor ও descendant বের করা (scope engine ও menu fallback এটি ব্যবহার করবে) | Q-11, Q-12 | Must |
| FR-REL-08 | Option: Faculty page-এ শুধু Faculty-তে tag করা content, নাকি Department ও Program-এর content-ও অন্তর্ভুক্ত (per section setting) | BR-CNT-03 | Should |
| FR-REL-09 | **Person Source registry** ও reusable Person Picker (বিস্তারিত: [Part 3, Section 1](03-SRS-People.md#1-মূল-design-ধারণা-person-source-ও-assignment)) | Q-08 | Must |
| FR-REL-10 | **Assignment:** ব্যক্তি ↔ Assignable (Relation Target থেকে আলাদা registry), ভূমিকা, ক্রম, মেয়াদ সহ (বিস্তারিত: Part 3) | Q-08 | Must |
| FR-REL-11 | **Standard Role Catalog:** প্রতিটি Assignable-এর ভূমিকার স্থির key (যেমন `dean`, `head`, `officer`, `coordinator`) module register করবে; Admin শুধু label (multi-language), ক্রম ও active বদলাতে পারবেন, key নয় — যাতে সব university-তে data ও report একই রকম থাকে (DB-01 Section 8.4) | BO-06 | Must |

### Business Rules

* **BRL-REL-01:** Relation table একটি pivot table; তাই soft delete ও `is_active` নেই (Q-06)।
* **BRL-REL-02:** কোনো module অন্য module-এর model সরাসরি import করবে না; শুধু registry-র মাধ্যমে।

### Acceptance Criteria

* **AC-REL-01:** একটি news Engineering Faculty ও CSE Department দুটোর সাথে যুক্ত করলে উভয় microsite-এ দেখাবে।
* **AC-REL-02:** নতুন "Research Center" module install করে target হিসেবে register করলে News form-এর Related To-তে কোনো code পরিবর্তন ছাড়াই তা দেখাবে।

---

## C-14 API Management

**Purpose:** External system ও ভবিষ্যৎ mobile app-এর জন্য API।

**Actors:** Super Admin, Admin, external consumer

**Data:** API token (নাম, owner, ability, মেয়াদ, শেষ ব্যবহার), rate limit, API log।

> **Release 2 (API Spec APIQ-02):** Release 1-এ কোনো API নেই — website Blade + Livewire। নিচের সব FR-API Release 2-এ; Release 1-এ module-গুলো শুধু API Resource class-বান্ধব হবে। Contract: [API Specification](../06-API/01-API-Specification.md)। API documentation public নয় (APIQ-03)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-API-01 | Public read-only REST API: published content (news, events, notices, programs ইত্যাদি), JSON, versioned (`/api/v1/...`) | Scope 4.1 | Must |
| FR-API-02 | Module নিজের API resource register করবে; module inactive হলে endpoint 404 | BR-MOD-03 | Must |
| FR-API-03 | `?locale=` দিয়ে ভাষা নির্বাচন; pagination, filter (related entity, category, তারিখ) | BR-LANG-02 | Must |
| FR-API-04 | Admin API (Sanctum token): token ability ও user-এর permission ও scope মেনে চলবে | BR-SEC-04 | Could |
| FR-API-05 | Token তৈরি, মেয়াদ, revoke, শেষ ব্যবহারের সময় দেখা | — | Should |
| FR-API-06 | Rate limit: public ৬০ req/min প্রতি IP (configurable) | NFR-SEC | Must |
| FR-API-07 | OpenAPI documentation automatically তৈরি; শুধু Admin / Super Admin login-এর পর দেখা যাবে (public নয়) | BR-MOD-08 | Should |
| FR-API-08 | CORS allowed origin settings | — | Should |

### Permissions

`api.manage-tokens`, `api.settings`

### Acceptance Criteria

* **AC-API-01:** `/api/v1/news?related=department:cse&locale=bn` শুধু CSE-related published news বাংলায় দেবে।

---

## C-15 Localization

**Purpose:** Multi-language website (BRD Section 7.5)।

**Actors:** Super Admin, Admin (ভাষা পরিচালনা); সব editor (translation লেখা)

**Data:** Language (code, নাম, native নাম, direction, flag, default, active, ক্রম), interface string translation।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-LANG-01 | ভাষা যোগ, activate / deactivate, ক্রম, default নির্ধারণ। প্রাথমিকভাবে English ও বাংলা | BR-LANG-01 | Must |
| FR-LANG-02 | Translatable field-এর জন্য core trait ও form component (ভাষা tab) | BR-LANG-05 | Must |
| FR-LANG-03 | Frontend interface string (button, label, "Read more" ইত্যাদি) admin থেকে translate করার UI | BR-LANG-02 | Should |
| FR-LANG-04 | Language switcher: একই record-এর অন্য ভাষার URL-এ নিয়ে যাবে | BR-LANG-04 | Must |
| FR-LANG-05 | Fallback: translation না থাকলে default ভাষা (FR-GEN-42) | BR-LANG-03 | Must |
| FR-LANG-06 | Browser ভাষা দেখে প্রথম visit-এ redirect (setting, default বন্ধ) | — | Could |
| FR-LANG-07 | Translation অসম্পূর্ণ record-এর report (কোন module-এ কতগুলো) | — | Could |
| FR-LANG-08 | তারিখ ও সংখ্যা ভাষা অনুযায়ী format (বাংলা সংখ্যা option) | — | Should |

### Business Rules

* **BRL-LANG-01:** Default ভাষা deactivate বা delete করা যাবে না।
* **BRL-LANG-02:** কোনো ভাষা deactivate করলে তার translation data থাকবে, শুধু frontend-এ দেখাবে না।

### Acceptance Criteria

* **AC-LANG-01:** একটি news-এর শুধু English আছে; `/bn/news/...` খুললে English content দেখাবে, interface বাংলায়।

---

## C-16 Taxonomy

**Purpose:** Module-গুলোর জন্য একটি shared category ও tag system।

**Actors:** Admin, Content Manager

**Data:** Vocabulary (যেমন "News Category", "Notice Category", "Tags"; owner module), term (নাম, slug, parent, description, color / icon, ক্রম), term ↔ content (polymorphic)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-TAX-01 | Module নিজের vocabulary register করবে (hierarchical কিনা, single / multiple select) | BR-MOD-05 | Must |
| FR-TAX-02 | Term CRUD, nested (hierarchical vocabulary-তে), drag & drop ক্রম, multi-language নাম | BR-LANG-02 | Must |
| FR-TAX-03 | Shared "Tags" vocabulary, যা সব attachable content ব্যবহার করতে পারবে | — | Should |
| FR-TAX-04 | Content form-এ reusable term select component; tag-এর ক্ষেত্রে type করে নতুন tag তৈরি | — | Must |
| FR-TAX-05 | Frontend-এ term archive page (যেমন `/news/category/achievement`) | NFR-03 | Must |
| FR-TAX-06 | Term merge (দুটি term একত্র করা, content সহ) | — | Could |

### Business Rules

* **BRL-TAX-01:** ব্যবহৃত term delete করলে content থেকে সম্পর্ক সরে যাবে; সতর্কবার্তায় কতগুলো content প্রভাবিত হবে তা দেখাবে।

### Permissions

`taxonomy.manage`

---

## C-17 Global Search

**Purpose:** সব active module জুড়ে website search।

**Actors:** Visitor, admin user

**Data:** Search index (module, record, title, excerpt, URL, ভাষা, related entity)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-SRCH-01 | Module নিজের searchable model register করবে (কোন field, কোন ওজন) | BR-MOD-05 | Must |
| FR-SRCH-02 | Frontend search page: module অনুযায়ী grouped result, module filter, pagination, highlight | Q-07 | Must |
| FR-SRCH-03 | শুধু frontend-visible record (FR-GEN-12) search result-এ আসবে | BR-DATA-04 | Must |
| FR-SRCH-04 | Current ভাষায় search; বাংলা text search সমর্থন | BR-LANG-01 | Must |
| FR-SRCH-05 | Header-এ instant search (type করার সময় শীর্ষ ৫টি result) | — | Should |
| FR-SRCH-06 | Microsite-এর ভেতরে search করলে "শুধু এই Department-এ" option | BR-MS-07 | Could |
| FR-SRCH-07 | Admin Panel global search (Filament), scope মেনে | BR-SEC-04 | Should |
| FR-SRCH-08 | জনপ্রিয় ও result-হীন search term-এর report | — | Could |
| FR-SRCH-09 | Index update queue-তে; record publish, update, unpublish বা delete হলে index update | NFR-PERF-06 | Must |

### Acceptance Criteria

* **AC-SRCH-01:** "Cyber Security" search করলে সংশ্লিষ্ট Program, News ও Notice আলাদা group-এ দেখাবে; draft news দেখাবে না।

---

## C-18 Notification

**Purpose:** Admin user-দের system notification ও email।

**Actors:** System, সব admin user

**Data:** Notification (type, বিষয়বস্তু, link, পড়া হয়েছে কিনা), user-এর notification preference, email template।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-NOTI-01 | Admin Panel-এ bell icon-এ in-app notification, unread count সহ | Q-07 | Must |
| FR-NOTI-02 | Email notification (queue-তে) | Q-07 | Must |
| FR-NOTI-03 | Module নিজের notification event register করবে (যেমন "নতুন inquiry", "নতুন newsletter subscriber") | BR-MOD-05 | Must |
| FR-NOTI-04 | Core event: module install / update ফলাফল, backup ব্যর্থ, user তৈরি, Content Update Reminder (approval event বাদ — BRD Q-27) | BR-MOD-02 | Must |
| FR-NOTI-05 | User নিজে প্রতিটি notification type-এর জন্য in-app / email চালু বা বন্ধ করতে পারবে | — | Should |
| FR-NOTI-06 | Admin email template (subject ও body, multi-language, placeholder সহ) edit করতে পারবে | — | Should |
| FR-NOTI-07 | Recipient নির্ধারণ role, permission ও scope অনুযায়ী (যেমন CSE-এর inquiry শুধু CSE user-রা পাবে) | BR-SEC-04 | Must |
| FR-NOTI-08 | ৯০ দিনের পুরনো পড়া notification automatically মুছে যাবে | — | Should |

### Content Update Reminder (BRD Section 7.10)

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-NOTI-09 | Reminder rule CRUD: module, entity ধরন, সর্বোচ্চ দিন, reminder-এর ব্যবধান (দিন), mandatory, check mode (Latest / Each record), চারটি বার্তা (multi-language), active | BR-REM-01 থেকে 04 | Must |
| FR-NOTI-10 | Module নিজেকে **freshness source** হিসেবে register করবে (Registrar `freshnessSource()`): entity-র সাথে content কীভাবে যুক্ত (Content Relation, owner বা direct column) এবং কোন সময় "update" ধরা হবে (`updated_at` বা `published_at`)। শুধু register করা module rule-এ নির্বাচন করা যাবে | BR-REM-01 | Must |
| FR-NOTI-11 | প্রতিদিনের scheduled job প্রতিটি active rule ও প্রতিটি active entity-র জন্য অবস্থা হিসাব করবে: **OK** / **Empty** (content নেই, mandatory হলে) / **Stale** (সর্বশেষ update সর্বোচ্চ দিনের বেশি পুরনো, অথবা Each-record mode-এ কোনো record পুরনো) | BR-REM-02/03 | Must |
| FR-NOTI-12 | Empty বা Stale হলে, এবং শেষ reminder-এর পর ব্যবধান-দিন পার হলে, entity-র দায়িত্বপ্রাপ্ত user-দের কাছে reminder (email + in-app); Each-record mode-এ বার্তায় পুরনো record-এর তালিকা ও edit link | BR-REM-05 | Must |
| FR-NOTI-13 | Admin-কে সাপ্তাহিক একটি সারাংশ email (সব Empty / Stale entity), প্রতিটি entity-র জন্য আলাদা email নয় | BR-REM-04 | Should |
| FR-NOTI-14 | Content update হলে সেই entity-র অবস্থা পরের হিসাবে OK হবে এবং reminder বন্ধ হবে | BR-REM-01 | Must |
| FR-NOTI-15 | "Content Freshness" report: entity × module matrix — OK / Empty / Stale, কত দিন, reminder সংখ্যা; filter ও export | BR-REM-06 | Should |
| FR-NOTI-16 | Entity user নিজের entity-র অবস্থা Admin dashboard widget-এ দেখবে ("আপনার Department-এর Notice ৯৫ দিন update হয়নি") | BR-REM-05 | Should |

### Business Rules

* **BRL-NOTI-01:** Notification table-এ soft delete ও `is_active` থাকবে না (system table)।
* **BRL-NOTI-02:** Inactive entity, inactive module বা inactive rule-এর জন্য reminder যাবে না।
* **BRL-NOTI-03:** কোনো entity-র দায়িত্বপ্রাপ্ত user না থাকলে reminder Admin সারাংশে "No responsible user" হিসেবে দেখাবে।

### Acceptance Criteria

* **AC-NOTI-01:** Notice rule: Department, ৯০ দিন, ৭ দিন ব্যবধান, mandatory। CSE-এর সর্বশেষ notice ৯৫ দিন আগে → আজ CSE-এর scoped user-রা "Stale" reminder পাবেন; ৭ দিন পর আবার, যতক্ষণ না নতুন notice দেওয়া হয়।
* **AC-NOTI-02:** EEE-এর কোনো notice নেই এবং rule mandatory → "Empty" reminder।
* **AC-NOTI-03:** Slider rule Each-record mode, ৯০ দিন: CSE-এর ৩টি slider-এর ১টি ১০০ দিন পুরনো → reminder-এ সেই একটি slider-এর নাম ও edit link।

---

## C-19 Backup

**Purpose:** Database ও file-এর নিয়মিত ও প্রয়োজনভিত্তিক backup।

**Actors:** Super Admin, System

**Data:** Backup (তারিখ, ধরন, size, location, অবস্থা), schedule ও retention settings।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-BKP-01 | Scheduled backup: database (প্রতিদিন), file / media (প্রতি সপ্তাহে); সময় configurable | NFR-REL-02 | Must |
| FR-BKP-02 | Manual "Backup now" (database only / full) | Q-07 | Must |
| FR-BKP-03 | Module ও theme install / update-এর আগে automatic database backup | BR-MOD-07 | Must |
| FR-BKP-04 | Backup destination: local, এবং ঐচ্ছিক S3-compatible / Google Drive / FTP | Q-07 | Should |
| FR-BKP-05 | Retention: ন্যূনতম ১৪ দিন (configurable); পুরনো backup automatically মুছে যাবে | NFR-REL-02 | Must |
| FR-BKP-06 | Backup তালিকা, download (Super Admin), এবং health status (শেষ সফল backup কখন) | — | Must |
| FR-BKP-07 | Backup ব্যর্থ হলে Super Admin-কে notification | FR-NOTI-04 | Must |
| FR-BKP-08 | Admin Panel থেকে restore (database), দুই-ধাপ confirmation সহ | — | Could |

### Business Rules

* **BRL-BKP-01:** Backup file public URL-এ পাওয়া যাবে না।
* **BRL-BKP-02:** Backup ও restore action activity log-এ যাবে।

### Permissions

`backup.view`, `backup.create`, `backup.download`, `backup.restore` (সব শুধু Super Admin default-এ)

---

## C-20 Statistics

**Purpose:** Main homepage এবং প্রতিটি Faculty, Department, Program, Office ও Lab-এর জন্য আলাদা statistics counter (যেমন "12,000+ Students", "45 Programs", "350 Faculty Members"), যা বর্তমান website-এ আছে।

**Actors:** Admin (Main Site), Scoped / Office / Lab user (নিজের entity)

**Data:**

| অংশ | তথ্য |
|---|---|
| Stat Set | owner context (Main Site বা একটি entity), নাম (যেমন "Homepage counters") |
| Stat Item | label (multi-language), value source (Manual / Automatic), manual মান, automatic metric, prefix / suffix (যেমন "+", "%"), icon, "as of" তারিখ (ঐচ্ছিক), ক্রম, is_active |
| Metric Provider | Module-এর register করা automatic metric (যেমন "Program সংখ্যা", "Faculty Member সংখ্যা", "Alumni সংখ্যা", "Publication সংখ্যা") |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-STAT-01 | প্রতিটি context-এর (Main Site, Faculty, Department, Program, Office, Lab) জন্য এক বা একাধিক Stat Set | BR-STAT-01 | Must |
| FR-STAT-02 | Stat Item: label, icon, prefix / suffix, ক্রম; value **Manual** (যেমন ছাত্র সংখ্যা) অথবা **Automatic** | BR-STAT-01 | Must |
| FR-STAT-03 | **Metric Provider registry:** module নিজের countable metric register করবে। Automatic মান ওই context-এর scope অনুযায়ী গণনা হবে (যেমন Engineering Faculty-র "Program সংখ্যা" = তার সব Department-এর active Program) | BR-STAT-02 | Must |
| FR-STAT-04 | Metric provider-এর module inactive হলে item লুকাবে এবং admin-এ সতর্কতা দেখাবে | BR-MOD-03 | Must |
| FR-STAT-05 | Automatic মান cache হবে এবং সংশ্লিষ্ট data পরিবর্তন হলে (বা প্রতি ঘণ্টায়) update হবে | NFR-PERF-04 | Must |
| FR-STAT-06 | Automatic মানের উপর manual override বা rounding ("1,234" → "1,200+") করা যাবে | BR-STAT-01 | Should |
| FR-STAT-07 | Homepage Builder ও Page block-এ "Statistics" section: কোন Stat Set, layout (Counter animation, Cards, Inline bar, Icon grid) | BR-THM-02, BR-THM-04 | Must |
| FR-STAT-08 | Entity-র Stat Set না থাকলে theme-এর default (ঐ entity-র automatic metric থেকে) অথবা section লুকানো (setting) | BR-MS-05 | Should |
| FR-STAT-09 | সংখ্যা ভাষা অনুযায়ী format (বাংলা অঙ্ক option, FR-LANG-08) | BR-LANG-02 | Should |

### প্রাথমিক Metric Provider (module অনুযায়ী)

| Module | Metric |
|---|---|
| Faculty | Faculty সংখ্যা |
| Department | Department সংখ্যা |
| Program | Program সংখ্যা (Level অনুযায়ী filter সহ); প্রতিটি Program-এর Major ও Industry Expert / Facilitator সংখ্যা |
| Course | Course সংখ্যা |
| Faculty Member | শিক্ষক সংখ্যা (পদবি অনুযায়ী filter সহ, যেমন PhD-ধারী) |
| Alumni | Alumni সংখ্যা |
| Publications | Publication সংখ্যা (বছর / indexing filter) |
| Research | চলমান project সংখ্যা |
| Achievements | Achievement সংখ্যা (পর্যায় filter) |
| International Linkage | Partner university সংখ্যা, দেশ সংখ্যা |
| Lab | Lab সংখ্যা; প্রতিটি Lab-এর equipment, project, product সংখ্যা |
| Office | Office সংখ্যা; প্রতিটি Office-এর service ও team সদস্য সংখ্যা |

### কোন Context-এ কী Statistics (প্রাথমিক Stat Set)

নতুন installation-এ প্রতিটি context-এর জন্য নিচের default Stat Set তৈরি থাকবে; admin বা entity user পরিবর্তন করতে পারবে।

| Context | Default Stat Item (A = Automatic, M = Manual) | SRS Ref |
|---|---|---|
| **Main Site** | Students (M), Programs (A), Faculty Members (A), Alumni (A), Partner Universities (A) | FR-HOME-12 |
| **Faculty** | Departments (A), Programs (A), Faculty Members (A), Students (M) | FR-FAC-04 |
| **Department** | Programs (A), Faculty Members (A), Students (M), Labs (A) | FR-DEPT-05 |
| **Program** | Majors (A), Industry Experts (A), Faculty Members (M — শিক্ষক Department-এর সাথে যুক্ত, Program-এর সাথে নয়), Approved Alumni (M / A) | FR-PROG-11 |
| **Office** | Services (A), Team সদস্য (A), অন্যান্য (M) | FR-OFF-14 |
| **Lab** | Equipment (A), Projects (A), Products (A) | FR-LAB-11 |

### Business Rules

* **BRL-STAT-01:** Main Site-এর Stat Set শুধু Admin; entity-র Stat Set সেই entity-র scope / Office / Lab permission থাকা user পরিচালনা করবে।
* **BRL-STAT-02:** Automatic metric শুধু frontend-visible record গণনা করবে (FR-GEN-12)।
* **BRL-STAT-03:** উপরের "প্রাথমিক Metric Provider" তালিকার module-গুলোর জন্য metric register করা বাধ্যতামূলক; নতুন module ঐচ্ছিকভাবে register করতে পারবে।

### Permissions

`statistics.manage` (scope অনুযায়ী), `statistics.manage-main`

### Acceptance Criteria

* **AC-STAT-01:** CSE-তে একটি নতুন Program publish করলে CSE ও Engineering Faculty-র "Programs" counter এক বাড়বে, main homepage-এরটিও (automatic হলে)।
* **AC-STAT-02:** Main homepage-এ "Students: 22,000+" (manual) এবং "Programs: 45" (automatic) একসাথে দেখাবে।
* **AC-STAT-03:** Alumni module deactivate করলে "Alumni" counter লুকাবে, অন্য counter দেখাবে।
* **AC-STAT-04:** Main homepage, Engineering Faculty, CSE Department ও BSc in CSE Program — চারটি page-এ চারটি আলাদা counter set দেখাবে, প্রতিটির automatic মান নিজের scope অনুযায়ী (যেমন "Faculty Members": Main = সব, Engineering = Engineering-এর সব Department-এর, CSE = শুধু CSE-এর)।
* **AC-STAT-05:** CSE Department user CSE-এর Stat Set edit করতে পারবে, কিন্তু Main Site বা EEE-এর Stat Set পারবে না।

---

## Traceability Summary (BRD → SRS Part 1)

| BRD Requirement | SRS Requirement |
|---|---|
| BR-MOD-01 থেকে 08 | FR-MODM-01 থেকে 15 |
| BR-THM-01 থেকে 06 | FR-THEME, FR-MENU-01/02, FR-HF, FR-HOME, FR-PAGE-04 |
| BR-MS-01 থেকে 10 | FR-MENU-06 থেকে 09, FR-HF-05, FR-HOME-05/08, FR-PAGE-05, FR-REL-07 |
| BR-LANG-01 থেকে 05 | FR-LANG, FR-GEN-40 থেকে 43, FR-SET-11 |
| BR-MED-01 থেকে 16 | FR-MEDIA-01 থেকে 16, FR-GEN-60 |
| BR-DATA-01 থেকে 06 | FR-GEN-01 থেকে 08, FR-GEN-12 |
| BR-LOG-01 থেকে 09 | FR-LOG-01 থেকে 09, FR-GEN-90/91 |
| BR-SEC-01 থেকে 05 | FR-ROLE, FR-USER, FR-GEN-52, FR-MEDIA-13/14 |
| BR-CNT-02/03 | FR-REL |
| Q-07 (Taxonomy, Search, Notification, Backup) | FR-TAX, FR-SRCH, FR-NOTI, FR-BKP |
| BR-STAT-01/02 (Statistics) | FR-STAT-01 থেকে 09 |
| BR-REM-01 থেকে 06 (Content Update Reminder) | FR-NOTI-09 থেকে 16 |
| BR-CNT-07 (Context highlight) | FR-HOME-11 |
