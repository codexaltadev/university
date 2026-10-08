# API Specification

| বিষয় | তথ্য |
|---|---|
| Document | API Specification |
| Version | 1.0 |
| Date | 2026-10-08 |
| Status | ✔ Approved — **Release 2-এর contract** (Release 1-এ API নেই — APIQ-02) |

> **Release পরিকল্পনা (APIQ-01 থেকে 03, confirmed):**
> * Public website = **Laravel Blade theme + Livewire** (server-rendered; interactive অংশ Livewire component)। Website নিজে এই API ব্যবহার করে না।
> * **Release 1-এ কোনো API (public বা admin) তৈরি হবে না।** Section 8-এর form-এর কাজ (waiver calculator, result lookup, inquiry, newsletter ইত্যাদি) Release 1-এ Livewire action হিসেবে; validation, rate limit ও captcha-র নিয়ম একই।
> * এই document Release 2-এর জন্য contract হিসেবে থাকবে (mobile app, integration, headless frontend)। Module-গুলো এখনই API Resource class-বান্ধব করে লিখবে (SAD-এর `apiResource()` extension point থাকবে, কিন্তু Release 1-এ route register হবে না)।
> * Documentation (`/api/docs`) **public নয়** — শুধু Admin / Super Admin login-এর পর।
| Based on | SRS C-14 (FR-API-01 থেকে 08) ও module requirement, SAD Part A §7.2 (`apiResource()`), SAD Part B §9.3, DB-01 থেকে DB-06, DIU বর্তমান public API (`webbackend.daffodilvarsity.edu.bd/api/v1/public`) |
| পাঠক | Backend developer, frontend / mobile developer, external integrator, tester |

> এই document **contract** নির্ধারণ করে — endpoint, parameter, response-এর আকার ও নিয়ম। চূড়ান্ত machine-readable OpenAPI file code থেকে automatically তৈরি হবে (Section 11)।

## সূচি

1. [সাধারণ নীতি](#1-সাধারণ-নীতি)
2. [Request Convention](#2-request-convention)
3. [Response Convention](#3-response-convention)
4. [Common Schema](#4-common-schema)
5. [Context (Main Site ও Microsite)](#5-context-main-site-ও-microsite)
6. [Public API — Endpoint তালিকা](#6-public-api--endpoint-তালিকা)
7. [গুরুত্বপূর্ণ Endpoint-এর উদাহরণ](#7-গুরুত্বপূর্ণ-endpoint-এর-উদাহরণ)
8. [Public Form Endpoint (POST)](#8-public-form-endpoint-post)
9. [Admin API](#9-admin-api)
10. [Security, Rate Limit ও Caching](#10-security-rate-limit-ও-caching)
11. [Documentation ও Versioning](#11-documentation-ও-versioning)
12. [বর্তমান DIU API থেকে পরিবর্তন](#12-বর্তমান-diu-api-থেকে-পরিবর্তন)
13. [খোলা প্রশ্ন](#13-খোলা-প্রশ্ন)

---

## 1. সাধারণ নীতি

| # | নীতি | Ref |
|---|---|---|
| A-1 | **দুটি API:** Public (read-only + কয়েকটি form POST, token লাগে না) এবং Admin (token, permission ও scope মেনে) | FR-API-01/04 |
| A-2 | **Module-চালিত:** প্রতিটি module নিজের endpoint register করে (`apiResource()`); module inactive হলে তার সব endpoint `404 module_inactive` | FR-API-02, SAD Part A §7.2 |
| A-3 | **একই visibility নিয়ম:** Public API শুধু `frontendVisible()` record দেয় — `is_active`, soft delete, `published_at`, `expires_at`, module active (SAD Part A §6.4) | FR-GEN-12 |
| A-4 | **একই permission ও scope:** Admin API Admin Panel-এর মতোই Shield permission + Scope engine + entity permission মানে | BR-SEC-04, SAD Part A §7.4 |
| A-5 | **ভাষা-সচেতন:** প্রতিটি text field request-এর locale-এ resolve হয়, না থাকলে default ভাষা | FR-API-03, BR-LANG-03 |
| A-6 | **Stable identifier:** URL-এ slug, response-এ `id` ও `slug` দুটোই; role key, level key, morph alias স্থির (Standard Role Catalog) | ADR-17, FR-REL-11 |
| A-7 | **University-নিরপেক্ষ:** কোনো endpoint বা field DIU-নির্দিষ্ট নয়; সব university-র installation-এ একই contract | BO-06 |

---

## 2. Request Convention

### 2.1 Base URL ও Version

```
Public : https://{site}/api/v1/...
Admin  : https://{site}/api/v1/admin/...
```

Version path-এ (`v1`)। Breaking change হলে `v2`; পুরনো version ন্যূনতম ১২ মাস চালু থাকবে (Section 11)।

### 2.2 Query Parameter (সব list endpoint-এ)

| Parameter | উদাহরণ | অর্থ |
|---|---|---|
| `locale` | `?locale=bn` | ভাষা। না দিলে `Accept-Language` header, তারপর default ভাষা |
| `page`, `per_page` | `?page=2&per_page=20` | Pagination; `per_page` default 12, সর্বোচ্চ 100 |
| `sort` | `?sort=-published_at` | `-` = descending; প্রতিটি endpoint-এ অনুমোদিত field তালিকাভুক্ত |
| `filter[...]` | `?filter[category]=exam&filter[year]=2026` | Endpoint-নির্দিষ্ট filter |
| `in` | `?in=department:cse` | Context (Section 5) |
| `descendants` | `?in=faculty:fsit&descendants=1` | Context-এর অধীন entity-র content-ও (FR-REL-08) |
| `highlighted` | `?highlighted=1` | শুধু সেই context-এ highlight করা (FR-CNT-H01) |
| `fields` | `?fields=id,title,slug,published_at` | হালকা response (sparse fieldset) |
| `include` | `?include=categories,related` | ঐচ্ছিক সম্পর্কিত data |
| `q` | `?q=cyber` | List-এর ভেতরে text search |

### 2.3 Header

| Header | ব্যবহার |
|---|---|
| `Accept: application/json` | বাধ্যতামূলক |
| `Accept-Language: bn` | `locale` না দিলে |
| `Authorization: Bearer {token}` | শুধু Admin API |
| `If-None-Match: "{etag}"` | Caching (Section 10.3) |

---

## 3. Response Convention

### 3.1 একক record

```json
{
  "data": { "id": 881, "type": "news.post", "slug": "icpc-regional-2026", "...": "..." },
  "meta": { "locale": "en", "fallback_locale_used": false }
}
```

### 3.2 তালিকা

```json
{
  "data": [ { "...": "..." } ],
  "meta": {
    "locale": "en",
    "context": { "type": "department.department", "slug": "cse", "name": "Computer Science and Engineering" },
    "pagination": { "page": 1, "per_page": 12, "total": 214, "last_page": 18 }
  },
  "links": { "self": "/api/v1/news?page=1", "next": "/api/v1/news?page=2", "prev": null }
}
```

### 3.3 Error

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "details": { "email": ["The email field is required."] }
  }
}
```

| HTTP | `code` | কখন |
|---|---|---|
| 400 | `bad_request` | ভুল parameter |
| 401 | `unauthenticated` | Admin API-তে token নেই / মেয়াদোত্তীর্ণ |
| 403 | `forbidden` | Permission বা scope নেই (A-4) |
| 404 | `not_found` | Record নেই বা frontend-এ দৃশ্যমান নয় |
| 404 | `module_inactive` | Module বন্ধ (A-2) |
| 409 | `conflict` | যেমন duplicate subscription |
| 410 | `gone` | Slug পরিবর্তিত — `meta.redirect_to`-তে নতুন URL (FR-GEN-32) |
| 422 | `validation_failed` | Form data ভুল |
| 429 | `rate_limited` | Section 10.2; `Retry-After` header |
| 500 | `server_error` | Stack trace কখনো নয় (NFR-USE-03) |

### 3.4 Field নিয়ম

* তারিখ ISO 8601, UTC (`2026-01-03T00:00:00Z`); শুধু তারিখ হলে `2026-01-03`।
* টাকা: `{ "amount": 189025, "currency": "BDT", "formatted": "৳189,025" }` — `formatted` locale অনুযায়ী (বাংলা অঙ্ক setting মানে)।
* Translatable field locale অনুযায়ী **একটি string** হিসেবে আসে (সব ভাষা নয়); Admin API-তে সব ভাষার object (Section 9)।
* খালি মান `null`, বাদ দেওয়া নয় (field সবসময় থাকে)।

---

## 4. Common Schema

### 4.1 `Media`

```json
{
  "id": 5521,
  "kind": "image",
  "url": "https://{site}/storage/media/5521/convocation.jpg",
  "alt": "Graduates at the 2026 convocation",
  "caption": null,
  "width": 1600, "height": 900,
  "sizes": { "thumbnail": "…/thumb.webp", "medium": "…/medium.webp", "large": "…/large.webp" }
}
```

Document-এর ক্ষেত্রে `kind: "document"`, `mime_type`, `size_bytes`, `title`।

### 4.2 `EntityRef` (সম্পর্কিত entity)

```json
{ "type": "department.department", "id": 12, "slug": "cse", "name": "Computer Science and Engineering", "url": "/department/cse" }
```

### 4.3 `PersonRef` (Assignment থেকে)

```json
{
  "role_key": "head",
  "role_label": "Head",
  "name": "[Name]",
  "designation": "Professor",
  "photo": { "...Media" },
  "email": "[shown only if public]",
  "phone": null,
  "profile_url": "/faculty-member/[slug]"
}
```

`email` / `phone` শুধু public visibility থাকলে (FR-ADMN-07, FR-ALUM-06)।

### 4.4 `Term`

```json
{ "id": 7, "vocabulary": "notice.category", "slug": "exam", "name": "Exam" }
```

### 4.5 `Seo`

```json
{ "title": "…", "description": "…", "canonical": "…", "og_image": { "...Media" }, "noindex": false, "alternates": { "en": "/en/news/x", "bn": "/bn/news/x" } }
```

---

## 5. Context (Main Site ও Microsite)

Content-এর তালিকা কোন context-এর তা `in` parameter ঠিক করে (SAD Part B §3, DB-05 §1):

| Request | কী দেয় |
|---|---|
| `GET /news` | **Main site-এ প্রকাশিত** news (`site:0` relation — Q-26) |
| `GET /news?in=department:cse` | CSE-related সব visible news |
| `GET /news?in=faculty:fsit&descendants=1` | FSIT ও তার সব Department / Program-এর news |
| `GET /news?highlighted=1` | Main homepage-এর highlight (মেয়াদসহ), ক্রম অনুযায়ী |
| `GET /news?in=department:cse&highlighted=1` | CSE homepage-এর highlight |

`in`-এর মান `{entity}:{slug}`; entity = `faculty`, `department`, `program`, `office`, `lab`, `campus`, `course`। অজানা বা inactive entity → `404`।

### 5.1 Microsite Resolve

একটি entity-র microsite-এর সব কাঠামো একবারে (fallback resolver-এর ফল — SAD Part B §6.2):

```
GET /api/v1/microsites/{entity}/{slug}
```

Response-এ `menus` (location অনুযায়ী, প্রতিটিতে `source`: `own` / `inherited:faculty:fsit` / `main`), `header`, `footer`, `template`, `branding` (logo / banner), `homepage.sections`, `stats`। Frontend এক call-এ microsite render করতে পারে।

---

## 6. Public API — Endpoint তালিকা

সব path `/api/v1` দিয়ে শুরু। "Module" কলামের module inactive হলে endpoint `404 module_inactive`।

### 6.1 Site ও Core

| Method | Path | কাজ | Module / Ref |
|---|---|---|---|
| GET | `/site` | Website settings (নাম, contact, social, logo, ভাষা তালিকা, analytics ID নয়) | C-05 |
| GET | `/site/theme` | Design token (color, font) — headless frontend-এর জন্য | C-06 |
| GET | `/menus/{location}` | Main site menu (যেমন `primary`, `footer-1`) | C-08 |
| GET | `/microsites/{entity}/{slug}` | Section 5.1 | C-08, C-09, C-10, C-20 |
| GET | `/homepage` | Main homepage-এর section তালিকা ও প্রতিটির data (source mode প্রয়োগ করা) | C-10 |
| GET | `/pages/{path}` | Page (nested path সহ: `about/history`); `?in=department:cse` দিলে সেই entity-র page | C-11 |
| GET | `/search` | `?q=&module=&in=` — module অনুযায়ী grouped result | C-17 |
| GET | `/statistics` | `?in=` — context-এর Stat Set (মান হিসাব করা) | C-20 |
| GET | `/languages` | Active ভাষা | C-15 |
| GET | `/redirects/resolve` | `?path=/old-url` → নতুন URL (headless frontend-এর জন্য) | C-12 |
| GET | `/sitemap.xml` | Sitemap (API নয়, তবে তালিকাভুক্ত) | C-12 |

### 6.2 Academic

| Method | Path | কাজ | Module |
|---|---|---|---|
| GET | `/faculties` | তালিকা (`sort=sort_order`) | M-01 |
| GET | `/faculties/{slug}` | Detail: overview, dean (PersonRef), departments, stats | M-01 |
| GET | `/departments` | `filter[faculty]=` | M-02 |
| GET | `/departments/{slug}` | Detail: about, head, programs, stats | M-02 |
| GET | `/programs` | `filter[level]=`, `filter[faculty]=`, `filter[department]=`, `filter[shift]=`, `filter[tag]=`, `q=` | M-03 |
| GET | `/programs/{slug}` | Program page-এর পুরো data (Section 7.2) | M-03 |
| GET | `/programs/{slug}/curriculum` | Current curriculum, term অনুযায়ী | M-06 |
| GET | `/programs/{slug}/tuition` | Fee category অনুযায়ী fee line | M-10 |
| GET | `/courses/{department}/{code}` | Course detail | M-04 |
| GET | `/sessions` | Session তালিকা, current ও admission flag | M-05 |
| GET | `/faculty-members` | `filter[department]=`, `filter[designation]=`, `q=` | M-07 |
| GET | `/faculty-members/{slug}` | Profile + publications (M-25 active হলে) | M-07 |
| GET | `/campuses`, `/campuses/{slug}` | Campus ও building | M-08 |
| GET | `/admission` | Admission landing: চলমান circular, গুরুত্বপূর্ণ তারিখ, highlight, step, contact group | M-09 |
| GET | `/admission/circulars`, `/admission/circulars/{slug}` | Circular | M-09 |
| GET | `/tuition-fees` | `filter[level]=`, `filter[faculty]=`, `filter[category]=local` | M-10 |
| GET | `/waiver/options` | Calculator-এর dropdown data (level, board, gender, waiver category) | M-10 |
| GET | `/scholarships`, `/scholarships/{slug}` | Scholarship | M-10 |
| GET | `/calendars` | `filter[session]=`, `filter[audience]=` | M-11 |
| GET | `/calendars/{slug}` | Event সহ; `/calendars/{slug}.ics` iCal | M-11 |

### 6.3 People

| Method | Path | কাজ | Module |
|---|---|---|---|
| GET | `/offices`, `/offices/{slug}` | Office, section, team (PersonRef), service | M-12 |
| GET | `/services` | সব Office-এর service (`q=`) | M-12 |
| GET | `/support-contacts` | উদ্দেশ্যভিত্তিক contact | M-12 |
| GET | `/administration` | Directory — শুধু public category; `filter[category]=`, `filter[office]=` | M-13 |
| GET | `/leadership` | Group অনুযায়ী current holder; `?former=1` সাবেক | M-15 |
| GET | `/committees`, `/committees/{slug}` | সদস্য role catalog-এর ক্রমে | M-16 |
| GET | `/alumni`, `/alumni/{slug}` | `filter[department]=`, `filter[batch]=`, `filter[pride]=1` | M-17 |

### 6.4 Content

সবগুলো Section 5-এর context নিয়ম মানে (`in`, `descendants`, `highlighted`)।

| Method | Path | বিশেষ filter / নোট | Module |
|---|---|---|---|
| GET | `/news`, `/news/{slug}` | `filter[category]=`, `filter[year]=` | M-18 |
| GET | `/events`, `/events/{slug}` | `filter[when]=upcoming\|past`; `/events/{slug}.ics` | M-19 |
| GET | `/notices`, `/notices/{slug}` | `filter[category]=`, `filter[pinned]=1`; pinned আগে | M-20 |
| GET | `/testimonials` | `filter[type]=alumni` | M-21 |
| GET | `/gallery/albums`, `/gallery/albums/{slug}` | `filter[type]=photo\|video`, `filter[series]=` | M-22 |
| GET | `/sliders` | `filter[placement]=hero\|page_banner` | M-23 |
| GET | `/achievements`, `/achievements/{slug}` | `filter[level]=` | M-24 |
| GET | `/publications` | `filter[year]=`, `filter[indexing]=scopus`, `filter[author]=` | M-25 *(optional)* |
| GET | `/research`, `/research/{slug}` | `filter[status]=ongoing`, `filter[area]=` | M-26 *(optional)* |
| GET | `/documents`, `/documents/{slug}` | `filter[type]=prospectus\|policy\|form` | M-27 |
| GET | `/faq` | Category অনুযায়ী grouped | M-28 |
| GET | `/links` | Group অনুযায়ী | M-29 |
| GET | `/announcements` | `?page_key=admission.index` — সেই page-এর active popup / ticker / countdown / bar | M-30 |

### 6.5 Services, Reputation, Communication

| Method | Path | কাজ | Module |
|---|---|---|---|
| GET | `/transport/service-types` | Service type, default চিহ্নিত | M-31 |
| GET | `/transport/routes` | `filter[service]=`, `filter[day]=sat` | M-31 |
| GET | `/transport/stops` | `q=` (autocomplete) | M-31 |
| GET | `/transport/stops/{id}/departures` | Route finder: `?service=&day=` | M-31 |
| GET | `/labs`, `/labs/{slug}` | Lab + item (equipment, scope, project, product) | M-32 |
| GET | `/facilities` | `filter[campus]=`, context নিয়ম | M-33 |
| GET | `/accreditations` | Context নিয়ম | M-34 |
| GET | `/rankings` | `filter[agency]=qs`; trend-এর জন্য বছর অনুযায়ী | M-34 |
| GET | `/international/partners` | `filter[country]=MY`, `filter[agreement]=dual_degree` | M-35 |
| GET | `/international/conferences`, `/international/visitors` | — | M-35 |
| GET | `/csr`, `/csr/{slug}` | `filter[category]=` | M-36 |
| GET | `/partners` | `filter[type]=industry\|discount` | M-37 |
| GET | `/inquiry/types` | Contact form-এর type | M-38 |
| GET | `/newsletter/lists` | Public list | M-39 |

---

## 7. গুরুত্বপূর্ণ Endpoint-এর উদাহরণ

### 7.1 `GET /api/v1/notices?in=department:cse&per_page=2&locale=en`

```json
{
  "data": [
    {
      "id": 2231,
      "type": "notice.notice",
      "slug": "mid-term-exam-routine-fall-2026",
      "title": "Mid-term exam routine, Fall 2026",
      "reference_no": "[Ref]",
      "excerpt": null,
      "published_at": "2026-10-08T04:00:00Z",
      "expires_at": null,
      "is_pinned": true,
      "is_urgent": false,
      "category": { "id": 3, "vocabulary": "notice.category", "slug": "exam", "name": "Exam" },
      "related": [ { "type": "department.department", "id": 12, "slug": "cse", "name": "Computer Science and Engineering", "url": "/department/cse" } ],
      "on_main_site": true,
      "attachments": [ { "id": 9001, "kind": "document", "title": "Routine (PDF)", "url": "…", "mime_type": "application/pdf", "size_bytes": 182344 } ],
      "url": "/en/notices/mid-term-exam-routine-fall-2026"
    }
  ],
  "meta": {
    "locale": "en",
    "context": { "type": "department.department", "slug": "cse", "name": "Computer Science and Engineering" },
    "pagination": { "page": 1, "per_page": 2, "total": 214, "last_page": 107 }
  },
  "links": { "self": "…", "next": "…?page=2", "prev": null }
}
```

### 7.2 `GET /api/v1/programs/master-in-business-administration`

Program page-এর সব section একবারে; module inactive হলে সেই section `sections`-এ থাকে না (FR-PROG-18)।

```json
{
  "data": {
    "id": 20,
    "slug": "master-in-business-administration",
    "name": "Master of Business Administration (MBA)",
    "short_name": "MBA",
    "level": { "slug": "graduate", "name": "Graduate" },
    "tags": [],
    "faculty": { "type": "faculty.faculty", "slug": "fbe", "name": "Business & Entrepreneurship", "url": "/faculty/fbe" },
    "department": { "type": "department.department", "slug": "bba", "name": "Business Administration", "url": "/department/bba" },
    "hero": {
      "image": { "...Media" },
      "subtitle": "…",
      "video_url": "https://www.youtube.com/watch?v=…",
      "facts": { "credits": 40, "terms": "4–6", "duration": "1–2 years", "next_intake_on": "2026-01-03" },
      "apply_url": "https://admission.example.edu"
    },
    "stats": [
      { "key": "alumni", "label": "Approved alumni", "value": 1000, "display": "1,000+" },
      { "key": "majors", "label": "Majors", "value": 24, "display": "24" }
    ],
    "section_order": ["overview", "variants", "majors", "eligibility", "tuition", "facilitators", "career", "testimonials", "facilities", "memberships"],
    "sections": {
      "overview": { "summary": "…", "objective": "…", "duration_text": "…" },
      "variants": { "intro": "…", "items": [ { "title": "MBA (Regular)", "description": "…", "image": { "...Media" }, "related_program": null, "button": { "text": "Learn more", "url": "…" } } ] },
      "majors": { "intro": "…", "groups": [ { "category": "Major", "items": [ { "name": "Finance", "description": "…" } ] } ] },
      "eligibility": { "general": "…", "english_medium": null },
      "tuition": {
        "provided_by": "fee",
        "categories": [
          { "key": "local", "name": "Local", "currency": "BDT", "plans": [ { "credits": 40, "max_credits": 69, "duration": "1–2 years", "lines": [ { "type": "admission", "label": "Admission fee", "amount": 30250 } ], "total_min": 189025, "total_max": 315000 } ] }
        ],
        "waiver_calculator_url": "/waiver-calculator"
      },
      "facilitators": { "items": [ { "...PersonRef (role_key: facilitator)" } ] },
      "career": { "paths": ["Business Development Manager"], "industries": ["Banking & Financial Institutions"] },
      "testimonials": { "items": [ { "quote": "…", "person": { "...PersonRef" } } ] },
      "facilities": { "items": [ { "title": "Library", "summary": "…", "icon": "library", "url": "…" } ] },
      "memberships": { "items": [ { "title": "…", "description": "…", "logo": { "...Media" } } ] }
    },
    "downloads": [ { "title": "Prospectus", "file": { "...Media" } } ],
    "seo": { "...Seo" },
    "url": "/program/master-in-business-administration"
  },
  "meta": { "locale": "en", "fallback_locale_used": false }
}
```

### 7.3 `GET /api/v1/transport/stops/{id}/departures?service=regular&day=sun`

```json
{
  "data": {
    "stop": { "id": 41, "name": "[Stop name]", "lat": 23.8, "lng": 90.36 },
    "service_type": { "code": "regular_service", "name": "Regular Service" },
    "routes": [
      {
        "route": { "id": 3, "name": "[Route name]", "code": "R3" },
        "to_campus": ["07:00", "07:30", "08:15"],
        "from_campus": ["13:30", "15:00", "17:10"],
        "stops": ["[Stop A]", "[Stop name]", "[…]", "Daffodil Smart City"]
      }
    ]
  }
}
```

---

## 8. Public Form Endpoint (POST)

সব POST: JSON body, CSRF নয় (stateless), কিন্তু honeypot field + rate limit + ঐচ্ছিক captcha token (NFR-SEC-06)।

| Method | Path | Body (মূল) | Response | Ref |
|---|---|---|---|---|
| POST | `/waiver/calculate` | `level`, `program`, `fee_category`, `board`, `gender`, `ssc_gpa`, `hsc_gpa` বা `ug_cgpa`, `golden_ssc`, `golden_hsc`, `waiver_categories[]` | `waiver_percent`, `message`, `estimated_total` (Money), `matched_rules` (নাম), `disclaimer` | FR-FEE-06/07 |
| POST | `/admission/results/lookup` | `batch` (বা `session`), `application_serial`, `captcha_token` | `result` (`selected` / `not_selected` / `waiting` / `absent` / `not_found`), `message`, `student_name` (শুধু নাম) | FR-ADM-06/07, BRL-ADM-02 |
| POST | `/admission/subscribe` | `channel`, `contact` | `202` (যাচাই email / message পাঠানো হয়েছে) | FR-ADM-09 |
| POST | `/inquiries` | `type`, `name`, `email`, `phone?`, `subject`, `message`, `source_url`, `in?` (context), `attachment?` (multipart) | `201` + `reference` | FR-INQ-02/03/05 |
| POST | `/newsletter/subscribe` | `email`, `name?`, `lists[]` | `202` (confirm email) | FR-NLT-01/02 |
| GET | `/newsletter/confirm/{token}` | — | Redirect / `200` | FR-NLT-02 |
| POST | `/newsletter/unsubscribe/{token}` | `reason?` | `200` | FR-NLT-03 |
| POST | `/alumni/submissions` | Alumni form field + photo | `201` (`is_active = false` অবস্থায় জমা) | FR-ALUM-07 |
| POST | `/faq/{id}/feedback` | `helpful: true\|false` | `204` | FR-FAQ-05 |
| POST | `/documents/{id}/download` | — | `302` file URL; download count বাড়ে | FR-DOC-06 |

**নিয়ম:**

* Admission result lookup-এ rate limit কঠোর (Section 10.2) এবং captcha বাধ্যতামূলক; response-এ phone, email বা অন্য ব্যক্তিগত তথ্য কখনো নয়।
* Waiver calculator-এর input database-এ ব্যক্তিগতভাবে সংরক্ষিত হয় না (BRL-FEE-03)।

---

## 9. Admin API

SRS-এ priority **Could** (FR-API-04) — প্রথম release-এ ঐচ্ছিক; তবে contract এখনই নির্ধারিত, যাতে পরে mobile app বা integration একই নিয়মে আসে।

### 9.1 Authentication

* Laravel Sanctum personal access token (`Authorization: Bearer …`)।
* Token তৈরি: Admin Panel → System › API Tokens (FR-API-05); মেয়াদ (default ৯০ দিন), ability।
* Token-এর ability ⊆ সেই user-এর Shield permission; scope ও entity permission user-এর মতোই (A-4)।
* MFA-চালু user-এর token তৈরিতে পুনরায় MFA যাচাই।

### 9.2 Resource Pattern

প্রতিটি admin-enabled module একই pattern পায়:

| Method | Path | কাজ | Permission (Shield) |
|---|---|---|---|
| GET | `/admin/{resource}` | তালিকা — scope-এর ভেতরের, trash সহ `?filter[trashed]=only\|with` | `ViewAny:{Resource}` |
| GET | `/admin/{resource}/{id}` | Detail — translatable field সব ভাষার object হিসেবে | `View:{Resource}` |
| POST | `/admin/{resource}` | তৈরি | `Create:{Resource}` |
| PATCH | `/admin/{resource}/{id}` | আংশিক update | `Update:{Resource}` |
| DELETE | `/admin/{resource}/{id}` | Soft delete | `Delete:{Resource}` |
| POST | `/admin/{resource}/{id}/restore` | Restore | `Restore:{Resource}` |
| DELETE | `/admin/{resource}/{id}/force` | Permanent delete (শুধু Super Admin) | `ForceDelete:{Resource}` |
| POST | `/admin/{resource}/{id}/activate`, `/deactivate` | `is_active` | `Update:{Resource}` |

### 9.3 Content-এর বিশেষ Action

| Method | Path | কাজ | Ref |
|---|---|---|---|
| PUT | `/admin/{resource}/{id}/relations` | Related To তালিকা (`[{type, slug}]`) | FR-REL, FR-GEN-51 |
| PUT | `/admin/{resource}/{id}/main-site` | `{ "published": true }` — শুধু `PublishMain` | FR-CNT-H07 |
| POST | `/admin/{resource}/{id}/main-site-request` | Entity user-এর অনুরোধ | FR-CNT-H09 |
| PUT | `/admin/{resource}/{id}/highlights` | `[{context, order, until}]` | FR-CNT-H01/H02 |
| GET | `/admin/main-site-queue` | Admin-এর queue | FR-CNT-H08 |
| POST | `/admin/media` | Upload (multipart) → Media | FR-MEDIA-03 |
| PUT | `/admin/{resource}/{id}/media/{field}` | Media usage set (`[media_id]`) | FR-MEDIA-09 |
| PUT | `/admin/{assignable}/{id}/people` | Assignment তালিকা (person, role_key, order, dates) | FR-REL-10 |
| POST | `/admin/admission/result-batches/{id}/import` | Result file (CSV / Excel) — queue job, `202` + job id | FR-ADM-05 |
| GET | `/admin/jobs/{id}` | Import / export job-এর অবস্থা | SAD Part B §11 |

### 9.4 Admin API-তে যা নেই (ইচ্ছাকৃত)

Module / theme install, activate, uninstall; backup download / restore; role ও permission পরিবর্তন; settings-এর advanced অংশ (custom script, mail) — এগুলো শুধু Admin Panel থেকে, Super Admin, MFA সহ (নিরাপত্তা; SAD Part B §13)।

---

## 10. Security, Rate Limit ও Caching

### 10.1 Security

| বিষয় | নিয়ম |
|---|---|
| Transport | শুধু HTTPS |
| CORS | Allowed origin settings থেকে (FR-API-08); default শুধু নিজের domain |
| Input | Form Request validation; rich text sanitize; file upload Media-র নিয়ম (FR-MEDIA-13) |
| Output | শুধু visible field (`$hidden`, API Resource class); কোনো internal id / morph class নাম নয়; ব্যক্তিগত contact visibility মানে |
| Enumeration | Public endpoint-এ inactive / draft record-এর জন্য `404` (অস্তিত্ব ফাঁস হয় না) |
| Logging | Admin API-র প্রতিটি write activity log-এ (`causer` = token-এর user, `properties.via = api`) |

### 10.2 Rate Limit (default; settings থেকে পরিবর্তনযোগ্য — FR-API-06)

| Group | সীমা |
|---|---|
| Public GET | ৬০ / মিনিট / IP |
| `/search` | ৩০ / মিনিট / IP |
| `/admission/results/lookup` | ১০ / মিনিট / IP, ১০০ / দিন / IP |
| অন্যান্য public POST | ৫ / মিনিট / IP |
| Admin API | ১২০ / মিনিট / token |

Response header: `X-RateLimit-Limit`, `X-RateLimit-Remaining`; সীমা পার হলে `429` + `Retry-After`।

### 10.3 Caching

* Public GET: `ETag` + `Cache-Control: public, max-age=60, stale-while-revalidate=300`; `If-None-Match` মিললে `304`।
* Server-side: SAD Part B §10-এর cache (tag-ভিত্তিক invalidation) — API ও website একই cache ব্যবহার করে।
* POST ও Admin API: `Cache-Control: no-store`।

---

## 11. Documentation ও Versioning

| বিষয় | সিদ্ধান্ত |
|---|---|
| OpenAPI | Code থেকে automatically তৈরি (প্রস্তাব: `dedoc/scramble` — Laravel-এর জন্য, Form Request ও API Resource থেকে schema বের করে); `/api/docs` **শুধু Admin / Super Admin login-এর পর** — public নয় (APIQ-03) — FR-API-07 |
| Module documentation | প্রতিটি module README-তে তার endpoint, filter ও resource তালিকা (Module Dev Guide-এ template) |
| Version | Path version (`/v1`); additive পরিবর্তন (নতুন field / endpoint) একই version-এ; field সরানো / অর্থ বদল = নতুন version |
| Deprecation | পুরনো version ন্যূনতম ১২ মাস; response header `Deprecation` ও `Sunset` |
| Contract test | প্রতিটি public endpoint-এর response schema test (Pest) — CI-তে |

---

## 12. বর্তমান DIU API থেকে পরিবর্তন

বর্তমান DIU frontend (Next.js) `webbackend.daffodilvarsity.edu.bd/api/v1/public` ব্যবহার করে। নতুন API-তে উল্লেখযোগ্য পরিবর্তন:

| বর্তমান | নতুন | কারণ |
|---|---|---|
| `GET /academic/faculties` | `GET /faculties` | Module-ভিত্তিক সরল path |
| `GET /department/{slug}` — শুধু department menu দেয় | `GET /departments/{slug}` (department data) + `GET /microsites/department/{slug}` (menu, header, homepage — fallback সহ) | Data ও কাঠামো আলাদা; fallback server-এ resolve |
| `GET /faculty/{slug}` — menu খালি থাকলে খালি তালিকা | `GET /microsites/faculty/{slug}` — নিজের menu না থাকলে `source: main` সহ main menu | Fallback (BR-MS-04) |
| `GET /program/{slug}` — `left_menus` + `sections` | `GET /programs/{slug}` — `section_order` + `sections` (একই ধারণা, module-চালিত) | বর্তমান ভালো pattern রাখা হয়েছে |
| Program `program_type: "Postgraduate"` | `level: { slug: "graduate" }` | Standard level key (৪টি level) |
| Program hero-তে `approved_alumni_number`, `majors`, `facilitators` সংখ্যা | `stats[]` (Program-এর Stat Set) | Statistics module (C-20) |
| Tuition-এ `first_year`, `second_year`, `semester_cost` আলাদা field | `lines[]` (type, label, amount) | যেকোনো সময়কাল / university (DB-03 §11.1) |
| Field নাম মিশ্র (`faculty_name`, `full_program_name`) | সব জায়গায় `name`, `slug`, `id` | Consistency |
| Admission subscriber `/admission-subscriber` | `/admission/subscribe` | Module path |
| `/v2/public/transport` | `/transport/...` (v1-এ নতুন করে) | একক version থেকে শুরু |

> বর্তমান Next.js frontend রাখা হবে না — নতুন website Blade + Livewire (APIQ-01)। তাই এই তুলনা শুধু Release 2-এ API তৈরির সময় reference।

---

## 13. খোলা প্রশ্ন

| # | প্রশ্ন | সিদ্ধান্ত |
|---|---|---|
| APIQ-01 *(✔)* | Public website-এর প্রযুক্তি? | **Laravel Blade theme + Livewire** (server-rendered; interactive অংশ Livewire) |
| APIQ-02 *(✔)* | প্রথম release-এ API লাগবে? | **না** — Release 1-এ public বা admin কোনো API নয়; এই document Release 2-এর contract |
| APIQ-03 *(✔)* | API documentation public? | **না** — শুধু Admin / Super Admin login-এর পর |
| APIQ-04 *(স্থগিত)* | পুরনো DIU API path চালু রাখতে হবে? | Release 1-এ API নেই বলে প্রযোজ্য নয়। Go-live-এর আগে DIU যাচাই করবে অন্য কোনো system (mobile app, অন্য site) পুরনো `webbackend` API ব্যবহার করে কিনা — করলে পুরনো backend কিছুদিন আলাদাভাবে চালু রাখা হবে (Deployment & Migration Plan-এ) |
