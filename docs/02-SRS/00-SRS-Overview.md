# Software Requirements Specification (SRS)
## Part 0 — Overview ও Common Requirements

| বিষয় | তথ্য |
|---|---|
| Document | SRS Part 0: Overview ও Common Requirements |
| Version | 1.0 — সব part confirmed; consistency review সম্পন্ন (Section 7) |
| Date | 2026-10-08 |
| Status | **Approved** |
| Based on | [BRD v1.3 (Approved)](../01-BRD.md) |

---

## 1. Introduction

### 1.1 Purpose

এই SRS BRD-তে বর্ণিত business requirement-কে **testable software requirement**-এ রূপান্তর করে। Developer, tester ও module developer এই document অনুযায়ী system তৈরি ও যাচাই করবে।

### 1.2 SRS-এর গঠন

SRS কয়েকটি part-এ ভাগ করা হয়েছে। প্রতিটি part confirm হওয়ার পর পরেরটি শুরু হবে।

| Part | File | বিষয় | Status |
|---|---|---|---|
| 0 | `00-SRS-Overview.md` | Overview, common requirement (সব module-এ প্রযোজ্য), NFR | ✔ Confirmed |
| 1 | `01-SRS-Core.md` | ২০টি Core module (C-20 Statistics পরে যোগ) | ✔ Confirmed |
| 2 | `02-SRS-Academic.md` | Academic module (M-01 থেকে M-11) | ✔ Confirmed |
| 3 | `03-SRS-People.md` | Administration ও People module (M-12 থেকে M-17) | ✔ Confirmed |
| 4 | `04-SRS-Content.md` | Website Content module (M-18 থেকে M-30) | ✔ Confirmed |
| 5 | `05-SRS-Services.md` | Campus, Reputation ও Communication module (M-31 থেকে M-39) | ✔ Confirmed |

### 1.3 Requirement ID Convention

| Prefix | অর্থ | উদাহরণ |
|---|---|---|
| `FR-GEN-xx` | Common functional requirement (সব module) | FR-GEN-03 |
| `FR-{MODULE}-xx` | নির্দিষ্ট module-এর functional requirement | FR-MODM-05 (Module Manager) |
| `BRL-{MODULE}-xx` | Business rule | BRL-MENU-02 |
| `AC-{MODULE}-xx` | Acceptance criteria | AC-LOG-01 |
| `NFR-xx` | Non-functional requirement | NFR-PERF-01 |

প্রতিটি requirement BRD-এর কোন requirement থেকে এসেছে তা "BRD Ref" column-এ দেখানো থাকবে (traceability)।

**Priority (MoSCoW):** Must (প্রথম release-এ বাধ্যতামূলক) · Should (প্রথম release-এ রাখার চেষ্টা) · Could (সময় থাকলে) · Won't (এই release-এ নয়)

### 1.4 Definitions

| Term | অর্থ |
|---|---|
| Core | সবসময় চালু থাকা system অংশ, যা deactivate করা যায় না |
| Module | nwidart/laravel-modules ভিত্তিক installable feature package |
| Theme | Frontend design package (view, CSS, layout) |
| Entity | Faculty, Department, Program, Office, Lab ইত্যাদি প্রাতিষ্ঠানিক একক |
| Microsite | একটি entity-র নিজস্ব website অংশ (নিজস্ব menu, template, homepage) |
| Scope | একজন user কোন entity-র data access করতে পারবে |
| Relation Target | যে entity-র সাথে content attach করা যায় |
| Attachable | যে content কোনো target-এর সাথে attach হতে পারে |
| Layout | একটি module-এর frontend প্রদর্শনের ধরন (Grid, List, Card ইত্যাদি) |
| Business table | Module-এর মূল data table (pivot, log ও system table বাদে) |
| Trash | Soft delete হওয়া record-এর তালিকা |

---

## 2. Overall Description

### 2.1 Product Perspective

```
┌──────────────────────────────────────────────────────────────┐
│                       Public Website                          │
│   Active Theme → Layout → Module views (multi-language)      │
├──────────────────────────────────────────────────────────────┤
│                  Admin Panel (Filament 5)                     │
├──────────────────────────────────────────────────────────────┤
│  Installable Modules (৩৮টি)                                   │
│  Academic · People · Content · Services · Reputation · Comm. │
├──────────────────────────────────────────────────────────────┤
│  Core (২০টি)                                                  │
│  Module Manager · Auth/User · Role/Scope · Settings · Theme  │
│  Media · Menu · Pages · SEO · Relation Engine · Localization │
│  Taxonomy · Search · Notification · Backup · Log · API       │
│  Statistics                                                  │
├──────────────────────────────────────────────────────────────┤
│        Laravel 13 · MySQL 8 · Queue · Scheduler · Storage    │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 User Classes

| User Class | বর্ণনা | Technical দক্ষতা |
|---|---|---|
| Super Admin | System-এর সম্পূর্ণ নিয়ন্ত্রণ, module ও theme পরিচালনা | মাঝারি |
| Admin | সব content ও settings | কম থেকে মাঝারি |
| Content Manager / Editor | নির্দিষ্ট module-এর content | কম |
| Scoped User | নির্দিষ্ট Faculty / Department / Program | কম |
| Office / Lab User | নির্দিষ্ট Office বা Lab | কম |
| Module / Theme Developer | নতুন module বা theme তৈরি | উচ্চ (Laravel) |
| Visitor | Public website ব্যবহারকারী | যেকোনো |

### 2.3 Operating Environment

| বিষয় | Requirement |
|---|---|
| PHP | 8.4 বা উপরে (ন্যূনতম 8.3) |
| Framework | Laravel 13.x |
| Admin Panel | Filament 5.x (Livewire 4) |
| Module System | nwidart/laravel-modules (Laravel 13-compatible সর্বশেষ version) |
| Database | MySQL 8.0+ (অথবা MariaDB 10.11+) |
| Cache / Queue | Redis (প্রস্তাবিত), অথবা database driver |
| Web Server | Nginx বা Apache, HTTPS বাধ্যতামূলক |
| Scheduler | Cron (প্রতি মিনিটে `schedule:run`) |
| Storage | Local disk, অথবা S3-compatible (configurable) |
| Browser (Admin ও Public) | Chrome, Firefox, Edge, Safari-এর সর্বশেষ ২টি version |

### 2.4 প্রধান Third-party Package (প্রস্তাবিত)

| Package | ব্যবহার |
|---|---|
| nwidart/laravel-modules | Module structure ও activation |
| filament/filament | Admin Panel |
| spatie/laravel-permission | Role ও Permission |
| spatie/laravel-activitylog | Activity Log |
| spatie/laravel-medialibrary | Media Library (file, conversions) |
| spatie/laravel-translatable | Multi-language field |
| spatie/laravel-backup | Backup |
| spatie/laravel-sitemap | Sitemap |
| laravel/sanctum | API authentication |
| laravel/scout (database driver) | Global Search |

> Final package তালিকা System Architecture Document-এ নির্ধারণ হবে।

### 2.5 Design Constraints

* Core কখনো কোনো installable module-এর উপর নির্ভর করবে না।
* Module অন্য module-এর table বা code সরাসরি পরিবর্তন করবে না। যোগাযোগ হবে Event, Contract ও Relation Engine-এর মাধ্যমে।
* প্রতিটি module-এর সবকিছু (table, route, permission, view, config, event) তার `slug` দিয়ে prefix হবে।
* সব business table core-এর base model / trait ব্যবহার করবে (FR-GEN-01 থেকে FR-GEN-06)।

---

## 3. Common Functional Requirements (সব Module-এ প্রযোজ্য)

এই requirement-গুলো প্রতিটি core ও installable module-এ প্রযোজ্য। Module-ভিত্তিক SRS-এ এগুলো আবার লেখা হবে না, শুধু ব্যতিক্রম থাকলে উল্লেখ করা হবে।

### 3.1 Data Lifecycle

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-01 | প্রতিটি business table-এ `deleted_at` থাকবে। Delete করলে record soft delete হবে | BR-DATA-01 | Must |
| FR-GEN-02 | প্রতিটি module-এর Admin list-এ "Trash" tab থাকবে, যেখান থেকে record restore করা যাবে। Bulk restore-ও সম্ভব হবে | BR-DATA-01 | Must |
| FR-GEN-03 | Trash থেকে permanent delete শুধু Super Admin করতে পারবে, confirmation dialog-এর পর | BR-DATA-02 | Must |
| FR-GEN-04 | প্রতিটি business table-এ `is_active` (boolean, default `true`) থাকবে। Admin list-এ toggle switch দিয়ে পরিবর্তন করা যাবে, bulk activate / deactivate সহ | BR-DATA-03 | Must |
| FR-GEN-05 | প্রতিটি business table-এ `created_by`, `updated_by`, `deleted_by` (user id) automatically set হবে | BR-DATA-05 | Should |
| FR-GEN-06 | উপরের নিয়মগুলো core-এর base model ও trait দিয়ে প্রয়োগ হবে। নতুন module শুধু base model extend করলেই এগুলো পাবে | BR-DATA-06 | Must |
| FR-GEN-07 | কোনো record restore করলে, তার parent record trash-এ থাকলে system সতর্ক করবে (যেমন Department restore করার সময় Faculty trash-এ) | BR-DATA-01 | Should |
| FR-GEN-08 | Parent record delete করার সময় active child record থাকলে system সতর্ক করবে এবং child-এর তালিকা দেখাবে | BR-DATA-01 | Must |

### 3.2 Publishing ও Visibility

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-10 | Content-type module-এ `published_at` (তারিখ ও সময়) থাকবে; form-এ default বর্তমান সময়। Website-এ এই তারিখ দেখাবে এবং listing এই অনুযায়ী সাজানো হবে। আলাদা `status` (Draft / Review / Published / Scheduled) নেই — চালু / বন্ধ শুধু `is_active` *(BRD Q-27)* | BR-CNT-04 | Must |
| FR-GEN-11 | `published_at` ভবিষ্যতের হলে content সেই সময় পর্যন্ত frontend-এ দেখাবে না এবং সময় হলে automatically দেখাবে (schedule); admin list-এ "Scheduled" চিহ্ন (হিসাব করা, column নয়) | BR-CNT-04 | Must |
| FR-GEN-12 | Frontend-এ একটি record তখনই দেখাবে যখন: `is_active = true` **এবং** not soft-deleted **এবং** module active **এবং** (`published_at` খালি বা ≤ এখন) **এবং** (প্রযোজ্য হলে) expiry তারিখ আসেনি | BR-DATA-04 | Must |
| FR-GEN-13 | যেখানে মেয়াদ অর্থবহ (Notice, Slider, Announcement), সেখানে ঐচ্ছিক `expires_at` | BR-CNT-04 | Should |
| FR-GEN-14 | List-এ `sort_order` দিয়ে drag & drop করে ক্রম পরিবর্তন করা যাবে (যেখানে ক্রম গুরুত্বপূর্ণ) | — | Should |
| FR-GEN-15 | Content প্রতিটি context-এ (Main Site ও related entity) আলাদাভাবে highlight করা যাবে, ক্রম ও মেয়াদ সহ; এবং content main site-এ প্রকাশ হবে কিনা তা আলাদা সিদ্ধান্ত, শুধু Admin-এর (বর্তমান `is_home`-এর বদলে; বিস্তারিত [Part 4, Section 1.1](04-SRS-Content.md#11-context-ভিত্তিক-highlight-বর্তমান-is_home-এর-উন্নত-রূপ), FR-CNT-H07) | BR-CNT-07, BR-CNT-08 | Must |

### 3.3 Approval Workflow — *বাদ (BRD Q-27)*

FR-GEN-20 থেকে 23 বাতিল। Content-এর চালু / বন্ধ সরাসরি `is_active` দিয়ে, update permission থাকা user-ই করবেন। ID-গুলো পুনর্ব্যবহার হবে না।

### 3.4 URL ও Slug

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-30 | Frontend-এ দেখানো প্রতিটি record-এর SEO-friendly `slug` থাকবে, title থেকে automatically তৈরি হবে এবং edit করা যাবে | NFR-03 | Must |
| FR-GEN-31 | একই module-এ slug unique হবে (প্রতিটি ভাষায়) | NFR-03 | Must |
| FR-GEN-32 | Slug পরিবর্তন করলে পুরনো URL থেকে নতুন URL-এ automatically 301 redirect তৈরি হবে | NFR-03 | Should |
| FR-GEN-33 | URL-এ ভাষা prefix থাকবে: `/{locale}/{module-route}/{slug}` (যেমন `/en/news/convocation-2026`)। Default ভাষার জন্য prefix ঐচ্ছিক (setting) | BR-LANG-04 | Must |

### 3.5 Multi-language

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-40 | Text field (title, content, excerpt, SEO meta ইত্যাদি) translatable হবে। Admin form-এ ভাষা অনুযায়ী tab থাকবে | BR-LANG-02 | Must |
| FR-GEN-41 | Default ভাষার field বাধ্যতামূলক। অন্য ভাষা ঐচ্ছিক | BR-LANG-03 | Must |
| FR-GEN-42 | Frontend-এ কোনো field-এর translation না থাকলে default ভাষার মান দেখাবে | BR-LANG-03 | Must |
| FR-GEN-43 | Admin list-এ প্রতিটি record-এর কোন কোন ভাষায় translation আছে তা indicator দিয়ে দেখাবে | BR-LANG-02 | Should |

### 3.6 Entity Scope ও Relation

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-50 | Attachable content-এর form-এ "Related To" field থাকবে, যেখানে এক বা একাধিক target (Faculty, Department, Program ইত্যাদি) select করা যাবে। শুধু active target module-গুলো দেখাবে | BR-CNT-02 | Must |
| FR-GEN-51 | Scoped user-এর form-এ "Related To" field-এ শুধু তার scope-এর entity দেখাবে, এবং অন্তত একটি বাধ্যতামূলক। Default হিসেবে তার নিজের entity select থাকবে | BR-SEC-04 | Must |
| FR-GEN-52 | Admin list ও query automatically user-এর scope দিয়ে filter হবে। Scope-এর বাইরের record URL দিয়ে সরাসরি খুললে **404** দেখাবে — record আছে কিনা তাও প্রকাশ পাবে না (permission-ই না থাকলে 403) *(v1.1, 2026-10-10)* | BR-SEC-04 | Must |
| FR-GEN-53 | Entity scope-এর অধীন entity-ও scope-এ গণ্য হবে (Faculty → Department → Program) | Q-12 | Must |

### 3.7 Media ও Files

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-60 | সব file ও image field core-এর Media Picker component ব্যবহার করবে। কোনো module নিজের upload ব্যবস্থা বানাবে না | BR-MED-01, BR-MED-16 | Must |
| FR-GEN-61 | Image field-এ alt text বাধ্যতামূলক (accessibility), Media Library-র alt text default হিসেবে আসবে | NFR-04 | Should |

### 3.8 SEO

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-70 | Frontend-এ দেখানো প্রতিটি record-এ SEO panel থাকবে: meta title, meta description, OG image, canonical URL, noindex | BR-CNT-05 | Should |
| FR-GEN-71 | SEO field খালি থাকলে title, excerpt ও featured image থেকে automatically তৈরি হবে | BR-CNT-05 | Must |

### 3.9 Admin UI Convention

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-80 | প্রতিটি module-এর list page-এ search, filter (active, scope, তারিখ, scheduled), sort, pagination ও column toggle থাকবে | NFR-08 | Must |
| FR-GEN-81 | Bulk action: activate, deactivate, delete, restore (permission অনুযায়ী) | NFR-08 | Must |
| FR-GEN-82 | User যে action-এর permission রাখে না, সেই button ও menu দেখাবে না | BR-SEC-01 | Must |
| FR-GEN-83 | প্রতিটি record-এর edit page-এ "Activity" tab থাকবে, যেখানে ওই record-এর পরিবর্তনের ইতিহাস দেখা যাবে | BR-LOG-08 | Should |
| FR-GEN-84 | Form-এ অসংরক্ষিত পরিবর্তন রেখে page ছাড়তে চাইলে সতর্কবার্তা দেখাবে | NFR-08 | Should |
| FR-GEN-85 | Admin Panel-এর interface ভাষা (বাংলা / English) user নিজে নির্বাচন করতে পারবে | BR-LANG-01 | Should |

### 3.10 Logging

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-90 | প্রতিটি business model-এর create, update, delete, restore, force delete, activate ও deactivate automatically log হবে (base trait-এর মাধ্যমে) | BR-LOG-01 | Must |
| FR-GEN-91 | Update log-এ শুধু পরিবর্তিত field-এর পুরনো ও নতুন মান থাকবে। Password ও sensitive field কখনো log হবে না | BR-LOG-04 | Must |

### 3.11 Layout ও Frontend

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GEN-100 | Frontend-এ দেখানো প্রতিটি module অন্তত একটি default layout দেবে, এবং বিকল্প layout register করতে পারবে | BR-THM-02 | Must |
| FR-GEN-101 | Layout শুধু theme-এর CSS variable (color, font, spacing) ব্যবহার করবে, কোনো hardcoded color বা font নয় | BR-THM-03 | Must |
| FR-GEN-102 | Module homepage section দিতে পারবে (যেমন "Latest News"), যা Homepage Builder ও microsite homepage-এ যোগ করা যাবে | BR-THM-04, BR-MS-07 | Must |

---

## 4. Non-Functional Requirements (বিস্তারিত)

### 4.1 Performance

| ID | Requirement |
|---|---|
| NFR-PERF-01 | Cache সহ public page-এর server response time (TTFB) ৫০০ms-এর কম (৯৫th percentile) |
| NFR-PERF-02 | Public page সম্পূর্ণ load (4G network) ৩ সেকেন্ডের কম; Lighthouse Performance score ≥ ৮০ |
| NFR-PERF-03 | Admin list page (১০,০০০ record পর্যন্ত) ২ সেকেন্ডের মধ্যে load হবে |
| NFR-PERF-04 | Menu, settings, theme configuration ও homepage section cache হবে, এবং সংশ্লিষ্ট data পরিবর্তন হলে cache automatically clear হবে |
| NFR-PERF-05 | Image lazy loading ও responsive image (srcset) ব্যবহার হবে |
| NFR-PERF-06 | ভারী কাজ (image conversion, backup, email, log cleanup, search indexing) queue-তে চলবে |

### 4.2 Security

| ID | Requirement |
|---|---|
| NFR-SEC-01 | সব traffic HTTPS-এ |
| NFR-SEC-02 | Password: ন্যূনতম ৮ অক্ষর, বড় ও ছোট হাতের অক্ষর, সংখ্যা; bcrypt / argon2 hashing |
| NFR-SEC-03 | Login-এ rate limit: ৫ বার ভুল হলে ১৫ মিনিটের জন্য lock |
| NFR-SEC-04 | Admin user-দের জন্য 2FA (TOTP) ঐচ্ছিক; Super Admin-এর জন্য বাধ্যতামূলক করার setting |
| NFR-SEC-05 | CSRF, XSS (rich text sanitize), SQL injection (Eloquent / prepared statement) থেকে সুরক্ষা |
| NFR-SEC-06 | Public form-এ (Contact, Newsletter) spam সুরক্ষা: honeypot ও rate limit; captcha ঐচ্ছিক |
| NFR-SEC-07 | Session নির্দিষ্ট সময় নিষ্ক্রিয় থাকলে expire হবে (default ১২০ মিনিট, configurable) |
| NFR-SEC-08 | Security header: HSTS, X-Frame-Options, X-Content-Type-Options, Content-Security-Policy |

### 4.3 Usability ও Accessibility

| ID | Requirement |
|---|---|
| NFR-USE-01 | Admin Panel responsive (tablet-এ ব্যবহারযোগ্য) |
| NFR-USE-02 | Public website WCAG 2.1 AA: keyboard navigation, alt text, পর্যাপ্ত color contrast, semantic HTML |
| NFR-USE-03 | প্রতিটি error message বোধগম্য ভাষায়, technical stack trace ছাড়া |
| NFR-USE-04 | একজন নতুন Content Editor ২ ঘণ্টার training-এর পর news, notice ও event publish করতে পারবে |

### 4.4 Reliability ও Availability

| ID | Requirement |
|---|---|
| NFR-REL-01 | ৯৯% uptime (hosting-নির্ভর) |
| NFR-REL-02 | Daily automatic database backup; সাপ্তাহিক file backup; ন্যূনতম ১৪ দিনের backup রাখা হবে (configurable) |
| NFR-REL-03 | Module install / update fail হলে আগের অবস্থায় rollback |
| NFR-REL-04 | কোনো একটি module-এর frontend error পুরো page ভেঙে দেবে না; ওই section লুকিয়ে যাবে এবং error log হবে |

### 4.5 Maintainability ও Extensibility

| ID | Requirement |
|---|---|
| NFR-MNT-01 | Code PSR-12 coding standard ও Laravel convention অনুসরণ করবে (Laravel Pint দিয়ে যাচাই) |
| NFR-MNT-02 | Core-এর প্রতিটি public extension point (hook, contract, event) documented থাকবে |
| NFR-MNT-03 | Core-এর জন্য automated test coverage ন্যূনতম ৭০% |
| NFR-MNT-04 | Core-এর version semantic versioning অনুসরণ করবে; breaking change শুধু major version-এ |

### 4.6 SEO

| ID | Requirement |
|---|---|
| NFR-SEO-01 | Server-side rendered HTML (search engine সহজে পড়তে পারবে) |
| NFR-SEO-02 | `sitemap.xml` (multi-language, hreflang সহ) ও `robots.txt` automatically তৈরি |
| NFR-SEO-03 | Structured data (JSON-LD): Organization, CollegeOrUniversity, Event, NewsArticle, BreadcrumbList |

### 4.7 Scalability ও Capacity

| ID | Requirement |
|---|---|
| NFR-CAP-01 | System ন্যূনতম ৫০,০০০ content record ও ১,০০,০০০ media file সামলাতে পারবে (বর্তমান DIU data-র কয়েক গুণ) |
| NFR-CAP-02 | ৫০০ জন একসাথে public visitor (cache সহ) |

---

## 5. Module SRS Template

Part 1 থেকে Part 5-এ প্রতিটি module নিচের কাঠামোয় লেখা হবে:

1. **Purpose** — module কী করে
2. **Actors** — কে ব্যবহার করে
3. **Data (Business level)** — কী কী তথ্য রাখে (বিস্তারিত column Database Design-এ)
4. **Functional Requirements** — `FR-{MODULE}-xx`
5. **Business Rules** — `BRL-{MODULE}-xx`
6. **Permissions** — module যে permission register করবে
7. **Frontend** — URL, layout, homepage section
8. **Acceptance Criteria** — `AC-{MODULE}-xx`
9. **BRD Traceability**

---

## 6. Appendix: Polymorphic Relation Inventory

পুরো SRS (Part 1 থেকে 5) জুড়ে polymorphic relation-এর পূর্ণ তালিকা। Table নাম ও column চূড়ান্ত হবে Database Design-এ; এখানে relation-গুলো চিহ্নিত করা হলো।

> **মোট ২৯টি polymorphic relation = ২২টি system-এর নিজস্ব design + ৭টি framework / package-এর** (P-21 যোগ SAD ADR-11 থেকে, P-22 DB-02 থেকে, P-23 UI/UX UXQ-02 থেকে; P-19 বাদ BRD Q-27 থেকে)

### 6.1 Group A — মূল Engine (৩টি)

| # | Relation | ধরন | এক দিক | অন্য দিক | SRS Ref |
|---|---|---|---|---|---|
| P-01 | **Content Relation** (+ Context Highlight) | Many-to-many polymorphic | Attachable content (তালিকা 6.5) | Relation Target (তালিকা 6.6) অথবা Main Site context | FR-REL-01 থেকে 08, FR-CNT-H01 থেকে H06 |
| P-02 | **Person Assignment** | Many-to-many polymorphic | Person Source (তালিকা 6.7) অথবা manual person | Assignable (তালিকা 6.8) | FR-REL-09/10 |
| P-03 | **User Scope** | Many-to-many polymorphic | User | Faculty, Department, Program | FR-ROLE-05/06, FR-USER-03 |

### 6.2 Group B — Microsite Ownership (৭টি)

প্রতিটির owner হবে Faculty, Department, Program, Office বা Lab; Main Site-এর owner `('site', 0)` (NULL নয় — unique index-এর জন্য; DB-02 Section 1.2)।

| # | Relation | ধরন | SRS Ref |
|---|---|---|---|
| P-04 | Menu → owner entity | morphTo (nullable) | FR-MENU-06 |
| P-05 | Header / Footer configuration → owner entity | morphTo (nullable) | FR-HF-05 |
| P-06 | Homepage (section set) → owner entity | morphTo (nullable) | FR-HOME-05/08 |
| P-07 | Stat Set → owner entity | morphTo (nullable) | FR-STAT-01 |
| P-08 | Page → owner entity | morphTo (nullable) | FR-PAGE-05 |
| P-09 | Microsite settings (template, logo, banner, fallback mode) → owner entity | morphOne | BR-MS-05/06, FR-MENU-08 |
| P-10 | Entity dynamic permission → Office / Lab | morphTo | FR-ROLE-07, FR-OFF-03, FR-LAB-03 |

### 6.3 Group C — Shared Service (১০টি; P-19 বাদ)

| # | Relation | ধরন | কারা ব্যবহার করবে | SRS Ref |
|---|---|---|---|---|
| P-11 | Menu Item → linked record (Page, Program, News ইত্যাদি) | morphTo | Menu | FR-MENU-03/04 |
| P-12 | Taxonomy term ↔ content | Many-to-many polymorphic | Category / Tag ব্যবহারকারী সব module | FR-TAX-01/04 |
| P-13 | Media usage (Media ↔ যেকোনো record, field / collection সহ) | Many-to-many polymorphic | Media Picker ব্যবহারকারী সব module | FR-MEDIA-04/09 |
| P-14 | SEO meta → record | morphOne | Frontend-এ দেখানো সব record | FR-GEN-70 |
| P-15 | Redirect → internal target record | morphTo (nullable) | SEO & Redirect, slug পরিবর্তন | FR-SEO-04, FR-GEN-32 |
| P-16 | Search index → record | morphTo | Global Search | FR-SRCH-01/09 |
| P-17 | View counter → record | morphTo | News, Notice, Events, Documents ইত্যাদি | FR-CNT-C08, FR-DOC-06 |
| P-18 | Revision → record | morphTo | Pages (প্রয়োজনে অন্যরা) | FR-PAGE-07 |
| ~~P-19~~ | ~~Approval review → record~~ — **বাদ** (BRD Q-27: approval workflow নেই) | — | — | — |
| P-21 | Slug (locale অনুযায়ী, history সহ) → record | morphTo | Frontend URL আছে এমন সব module | FR-GEN-30 থেকে 32, SAD ADR-11 |
| P-22 | Media item / folder → owner scope (upload-কারীর entity) | morphTo | Media Library | FR-MEDIA-14, BRL-MEDIA-01, DB-02 |

### 6.4 Group D — Module-নির্দিষ্ট ও অন্যান্য (২টি)

| # | Relation | ধরন | SRS Ref |
|---|---|---|---|
| P-20 | Inquiry → source entity (যে microsite থেকে এসেছে) | morphTo (nullable) | FR-INQ-03/08 |
| P-23 | Main site অনুরোধ → content | morphTo | FR-CNT-H09, DB-01 §8.1.1 |

### 6.4.1 Framework / Package-এর Polymorphic Relation (৭টি)

এগুলো package নিজে তৈরি করে; আলাদা design লাগবে না।

| # | Relation | Package |
|---|---|---|
| F-01 | Activity log → subject (কোন record) | spatie/laravel-activitylog |
| F-02 | Activity log → causer (কে করেছে) | spatie/laravel-activitylog |
| F-03 | Role ↔ model (`model_has_roles`) | spatie/laravel-permission |
| F-04 | Permission ↔ model (`model_has_permissions`) | spatie/laravel-permission |
| F-05 | Notification → notifiable | Laravel |
| F-06 | API token → tokenable | laravel/sanctum |
| F-07 | Media file → model | spatie/laravel-medialibrary |

> Multi-language translation polymorphic নয়; translatable field JSON column-এ থাকবে (spatie/laravel-translatable)।

### 6.5 Attachable Content (P-01-এর এক দিক) — ২২টি

| Module | | Module | | Module |
|---|---|---|---|---|
| News | | Achievements | | Accreditation |
| Events | | Publications | | International Partner |
| Notice | | Research | | Conference |
| Testimonials | | Documents | | International Visitor |
| Gallery (Album) | | FAQ | | CSR |
| Slider / Banner | | Announcement Widgets | | Partners |
| Facilities | | Committee | | Alumni |
| Admission Circular | | | | |

### 6.6 Relation Target (P-01-এর অন্য দিক) — ৭টি + Main Site

Faculty, Department, Program, Course, Office, Lab, Campus, এবং বিশেষ context **Main Site** (Highlight-এর জন্য)।

### 6.7 Person Source (P-02-এর এক দিক) — ৩টি + manual

Faculty Member, Administration (BRD Q-25: আগের Administration + Support Staff), Alumni, এবং kernel-এর **Manual person** (`core_manual_persons`, ADR-14 — কোনো directory-তে নেই এমন ব্যক্তি)। *(সংশোধিত DB-04: Leadership আর Person Source নয়; Leadership Position একটি Assignable।)*

### 6.8 Assignable (P-02-এর অন্য দিক) — ১৬টি

| Assignable | ভূমিকার উদাহরণ |
|---|---|
| Faculty | Dean |
| Department | Head, Coordinator |
| Program | Program Coordinator |
| Office | Head, Team member |
| Office Section | Team member |
| Office Service | দায়িত্বপ্রাপ্ত ব্যক্তি (FR-OFF-06) |
| Office Support Contact | Contact person (FR-OFF-07) |
| Lab | দায়িত্বপ্রাপ্ত ব্যক্তি |
| Committee | Chairman, Member, Member Secretary |
| Leadership Position | Vice Chancellor, Treasurer |
| Event | Speaker |
| Publication | Author (ক্রম সহ) |
| Research | PI, Co-PI, Member |
| Admission Contact Group | Contact person |
| Testimonial | ব্যক্তি |
| Transport Route | Driver |

### 6.9 বর্তমান System-এর সাথে তুলনা

| | বর্তমান `project_diu` | নতুন system |
|---|---|---|
| Polymorphic relation | ২টি (`testimonials.testimonialable`, `facilities.facilityable`) | ২২টি নিজস্ব + ৭টি package |
| Entity-র সাথে content যুক্ত করা | প্রতিটি table-এ আলাদা `department_id`, `lab_facility_id`, `faculty_id`, `is_faculty`, `is_department` ইত্যাদি | একটি Content Relation (P-01) |
| Entity-র menu | ৪টি আলাদা table (`faculty_menus`, `department_menus`, `program_menus`, `lab_menus`) | একটি Menu + owner (P-04) |
| ব্যক্তি-তথ্য | ৭টি table-এ copy | Person Assignment (P-02) |

---

## 7. Consistency Review (2026-10-08)

পুরো BRD ও SRS (Part 0 থেকে 5) মিলিয়ে যাচাই করা হয়েছে।

### 7.1 যা মিলেছে

| যাচাই | ফলাফল |
|---|---|
| Module সংখ্যা: BRD Section 6 ↔ SRS Part 1–5 | ৫৮ = ২০ Core (C-01–C-20) + ৩৮ Installable (M-01–M-39, M-14 খালি) ✔ |
| Installable group সংখ্যা | Academic ১১ + People ৫ + Content ১৩ + Services ৩ + Reputation ৪ + Communication ২ = ৩৮ ✔ (BRD v1.5: M-13 ও M-14 একত্রিত) |
| Microsite entity | BRD 6.4 ↔ Menu / Header / Homepage / Statistics fallback: Faculty, Department, Program, Office, Lab ✔ |
| Relation Target | BRD 6.3 ↔ Appendix 6.6: ৭টি ✔ |
| Statistics | Main, Faculty, Department, Program, Office, Lab — প্রতিটিতে Stat Set requirement ও default section ✔ |
| Dynamic permission | Office ও Lab উভয়ে ✔ |
| Optional module | Publications, Research (BRD 6.2.1 ↔ FR-MODM-16 ↔ Part 4) ✔ |

### 7.2 যা সংশোধন করা হয়েছে

| # | সমস্যা | সংশোধন |
|---|---|---|
| 1 | Contact module-এর code `CNT` Content-এর `FR-CNT-C/H` ও `BR-CNT`-এর সাথে সংঘর্ষ | Contact → `INQ` (FR-INQ-xx); Newsletter `NWS` → `NLT` (`NEWS`-এর সাথে বিভ্রান্তি এড়াতে) |
| 2 | Faculty Member-কে Person Source হিসেবে register করার requirement ছিল না | FR-FM-10 যোগ |
| 3 | Program-এর "Faculty Members" counter automatic ধরা হয়েছিল, কিন্তু শিক্ষক Program-এর সাথে নয়, Department-এর সাথে যুক্ত | Manual করা হয়েছে (FR-PROG-11, C-20 default table) |
| 4 | Assignment-এর অন্য দিককে "Relation Target" বলা হয়েছিল (Section সহ) — content target-এর সাথে গুলিয়ে যায় | আলাদা "Assignable" registry (FR-REL-10, FR-OFF-04) |
| 5 | Dean নির্বাচন Assignment-এর বাইরে আলাদাভাবে লেখা ছিল | FR-FAC-03 Assignment দিয়ে |
| 6 | FR-HOME-12 FR-HOME-11-এর আগে | ক্রম ঠিক করা হয়েছে |
| 7 | SRS-এর ভিত্তি BRD v1.1 লেখা ছিল | BRD v1.3 |
