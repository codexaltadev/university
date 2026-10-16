# System Architecture Document (SAD)
## Part A — Foundation: Stack, Structure, Module System, Base Model, Extension Points

| বিষয় | তথ্য |
|---|---|
| Document | SAD Part A |
| Version | 0.3 — v0.2 approved 2026-10-08; v0.3: Entity Hub (`entityPage()`, §7.2–7.3), Cluster (2026-10-10); frontend stack: Tailwind 4.3.3, Alpine যেখানে প্রয়োজন (2026-10-11) |
| Date | 2026-10-10 |
| Status | **Approved** — v0.3 approved 2026-10-10 |
| Based on | [BRD v1.3](../01-BRD.md), [SRS v1.0](../02-SRS/00-SRS-Overview.md) |
| পাঠক | Core developer, module developer, technical lead |

### SAD-এর গঠন

| Part | বিষয় | Status |
|---|---|---|
| **A** | Technology stack, layer ও dependency নিয়ম, folder structure, module system, base model ও trait, extension point, event, naming, coding standard, ADR | **এই document** |
| B | Core engine (Relation, Assignment, Scope, Highlight, Statistics, Menu fallback), theme engine, request flow, caching / queue / scheduler, security, deployment — [02-SAD-PartB-Engines-Runtime.md](02-SAD-PartB-Engines-Runtime.md) | Draft |

---

## 1. Technology Stack

### 1.1 Runtime

| Layer | Technology | Version constraint | নোট |
|---|---|---|---|
| Language | PHP | `^8.3` (প্রস্তাবিত 8.4) | Development machine-এ 8.4.22 |
| Framework | Laravel | `^13.0` | Development machine-এ 13.5.0 |
| Admin Panel | Filament | `^5.0` | Development machine-এ 5.6.2 |
| Reactive UI | Livewire | `^4.0` (Filament-এর সাথে) | |
| Module system | nwidart/laravel-modules | `^13.0` | Laravel 13-compatible |
| Database | MySQL | `8.0+` | MariaDB 10.11+ বিকল্প |
| Cache / Queue / Session | Redis | `7+` | ছোট installation-এ database driver বিকল্প |
| Queue monitor | laravel/horizon | `^5.24` | Redis queue হলে |
| Web server | Nginx + PHP-FPM | — | HTTPS বাধ্যতামূলক |
| Frontend asset | Vite, Tailwind CSS | `4.3.3` (pin, CSS-first `@theme`) | Theme-এর asset build; অন্য CSS framework নয় |
| Frontend JS | Alpine.js | Livewire-এর সাথে আসে | যেখানে প্রয়োজন (Theme Guide §12); আলাদা Alpine / jQuery / React নয় |

### 1.2 Package

| Package | ব্যবহার | SRS Ref |
|---|---|---|
| bezhansalleh/filament-shield (`^4.2`) | Role ও permission UI, resource / page / widget / custom permission, policy generation | C-03 |
| spatie/laravel-permission (`^7.0`, Shield-এর ভিত্তি) | Role, permission storage (dynamic permission সহ) | C-03 |
| spatie/laravel-activitylog | Activity log | C-04 |
| spatie/laravel-medialibrary | File storage ও image conversion | C-07 |
| spatie/laravel-translatable | JSON-ভিত্তিক multi-language field | C-15 |
| spatie/laravel-backup | Backup | C-19 |
| spatie/laravel-sitemap | Sitemap | C-12 |
| laravel/sanctum | API token | C-14 |
| laravel/scout (database / Meilisearch driver) | Global search | C-17 |
| sentry/sentry-laravel (ঐচ্ছিক) | Error monitoring | NFR-REL |
| Development: laravel/pint, larastan/larastan, pestphp/pest | Code style, static analysis, test | NFR-MNT |

> Version constraint `composer.json`-এ pin করা হবে এবং `composer.lock` repository-তে থাকবে। Package যোগ বা পরিবর্তন শুধু core release-এর মাধ্যমে হবে (ADR-03)।

---

## 2. Layer ও Dependency নিয়ম

### 2.1 তিনটি Layer

```
┌───────────────────────────────────────────────────────────────────┐
│  Layer 3 — Installable Modules (৩৮টি)   Modules/{Name}/            │
│  Faculty, Department, Program, News, Events, Office, Lab ...      │
│  ▸ শুধু Layer 1 contract ও Layer 2-এর public API ব্যবহার করে        │
│  ▸ অন্য installable module ব্যবহার করে শুধু declared dependency হলে │
├───────────────────────────────────────────────────────────────────┤
│  Layer 2 — Core Modules (২০টি)          Modules/{Name}/  core:true │
│  Menu, Media, Pages, Theme, Settings, Statistics, Search ...      │
│  ▸ Admin UI ও feature; deactivate করা যায় না                      │
│  ▸ শুধু Layer 1 ব্যবহার করে; কখনো Layer 3-কে চেনে না                │
├───────────────────────────────────────────────────────────────────┤
│  Layer 1 — Platform Kernel               platform/  (Platform\)    │
│  BaseModel, trait, contract, registry, Module Manager service,    │
│  Theme resolver, Scope engine, Relation engine                    │
│  ▸ Module নয়; সবসময় load হয়                                       │
├───────────────────────────────────────────────────────────────────┤
│  Laravel 13 · Filament 5 · nwidart/laravel-modules · spatie/*     │
└───────────────────────────────────────────────────────────────────┘
```

### 2.2 Dependency নিয়ম (বাধ্যতামূলক)

| # | নিয়ম | কেন |
|---|---|---|
| D-1 | Kernel কোনো module-কে চেনে না | Kernel module ছাড়াই চলতে হবে |
| D-2 | Core module কোনো installable module-কে চেনে না | Installable module বন্ধ করলে core ভাঙবে না (BRD BR-MOD-05) |
| D-3 | Installable module অন্য installable module-এর class সরাসরি `use` করবে শুধু `module.json`-এ `dependencies`-এ থাকলে | Dependency check কার্যকর রাখতে |
| D-4 | `optional_dependencies`-এর module-এর class সরাসরি `use` করা যাবে না; শুধু kernel registry / contract / event দিয়ে | Optional module inactive থাকলে class না-ও থাকতে পারে |
| D-5 | কোনো module অন্য module-এর table-এ সরাসরি query বা write করবে না | Table ownership স্পষ্ট রাখতে |
| D-6 | Module-এর মধ্যে যোগাযোগ: kernel registry, contract (interface), event | Loose coupling |

> D-1 থেকে D-5 CI-তে একটি architecture test (Pest `arch()`) দিয়ে যাচাই হবে।

---

## 3. Repository Folder Structure

```
university-wms/
├── app/                              # Laravel default (শুধু bootstrap-স্তরের কোড)
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   └── Filament/AdminPanelProvider.php
│   └── ...
├── platform/                         # Layer 1 — Platform Kernel  (namespace Platform\)
│   ├── Models/                       #   BaseModel, BasePivot
│   ├── Concerns/                     #   Trait (Section 6)
│   ├── Contracts/                    #   Interface (RelationTarget, Attachable, PersonSource ...)
│   ├── Registry/                     #   Extension registry (Section 7)
│   ├── Modules/                      #   Module Manager service: Installer, Validator, Activator ...
│   ├── Relations/                    #   Content Relation ও Assignment engine
│   ├── Scope/                        #   User scope engine
│   ├── Theme/                        #   Theme resolver, view finder
│   ├── Support/                      #   Helper, migration macro
│   ├── Events/                       #   Kernel event (Section 8)
│   └── PlatformServiceProvider.php
├── Modules/                          # Layer 2 ও 3 — nwidart modules
│   ├── Menu/                         #   Core module (module.json: "type": "core")
│   ├── Media/
│   ├── ...
│   ├── Faculty/                      #   Installable module
│   ├── News/
│   └── ...
├── themes/                           # Frontend theme
│   └── default/
│       ├── theme.json
│       ├── views/                    #   layout, partial, module override
│       ├── assets/
│       └── screenshot.png
├── config/
│   ├── modules.php                   # nwidart config (activator: database)
│   └── platform.php                  # kernel config
├── database/                         # Laravel ও kernel-স্তরের migration
├── storage/app/
│   ├── module-packages/              # Upload করা ZIP (staging)
│   └── module-backups/               # Update / uninstall-এর আগের copy
├── tests/
│   ├── Architecture/                 # D-1 থেকে D-5 যাচাই
│   └── ...
└── composer.json
```

**Kernel ও Core module-এর পার্থক্য:** Kernel-এ শুধু "কীভাবে কাজ করে" (contract, registry, engine); Core module-এ "ব্যবহারকারী কী দেখে" (Filament resource, admin page, frontend view)। যেমন Menu fallback-এর algorithm kernel-এ, Menu builder UI `Modules/Menu`-তে।

---

## 4. Module-এর Internal Structure

nwidart/laravel-modules-এর default structure ও Filament v5 modular standard অনুসরণ করে, সাথে system-এর নিজস্ব অংশ:

```
Modules/News/
├── module.json                         # Manifest (Section 5) — বাধ্যতামূলক
├── composer.json                       # PSR-4 autoload শুধু; "require" খালি (ADR-03)
├── README.md                           # Module documentation — বাধ্যতামূলক
├── CHANGELOG.md                        # Version history — বাধ্যতামূলক
├── app/
│   ├── NewsExtension.php               # ★ একমাত্র registration entry point (Section 7)
│   ├── Providers/
│   │   ├── NewsServiceProvider.php
│   │   └── RouteServiceProvider.php
│   ├── Models/
│   │   └── NewsPost.php                # extends Platform\Models\BaseModel
│   ├── Filament/
│   │   ├── NewsPlugin.php              # Filament plugin — শুধু active হলে panel-এ যোগ হয়
│   │   └── Resources/
│   │       └── NewsPosts/                 # plural model নাম (Filament v5 default)
│   │           ├── NewsPostResource.php   # Main hook only
│   │           ├── Pages/                 # List / Create / Edit
│   │           ├── RelationManagers/
│   │           ├── Schemas/NewsPostForm.php
│   │           └── Tables/NewsPostsTable.php
│   ├── Http/Controllers/Frontend/      # Public website controller
│   ├── Policies/
│   ├── Metrics/                        # Statistics metric provider
│   ├── Search/                         # Search provider
│   ├── Sections/                       # Homepage section class
│   ├── Events/  Listeners/  Jobs/
│   └── Support/
├── config/config.php                   # config key: news.*
├── database/
│   ├── migrations/                     # table prefix: news_
│   ├── seeders/NewsDatabaseSeeder.php  # Install-এর সময় Module Manager চালায়
│   └── factories/
├── resources/
│   ├── views/                          # view namespace: news::
│   │   ├── listing/   grid.blade.php  list.blade.php  card.blade.php  magazine.blade.php
│   │   ├── detail/    standard.blade.php  full-width.blade.php
│   │   └── sections/  latest-news.blade.php  featured-news.blade.php
│   └── lang/          en/  bn/         # translation namespace: news::
├── routes/
│   ├── web.php                         # route name prefix: news.
│   └── api.php                         # route name prefix: api.v1.news.
└── tests/
    ├── Feature/
    └── Unit/
```

---

## 5. Module Manifest (`module.json`)

nwidart-এর field-এর সাথে system-এর নিজস্ব `uwms` block।

### 5.1 উদাহরণ — Department (Relation Target, Microsite, Scope-যোগ্য)

```json
{
  "name": "Department",
  "alias": "department",
  "description": "Academic departments under faculties, with microsites",
  "keywords": ["academic", "department"],
  "priority": 20,
  "providers": ["Modules\\Department\\Providers\\DepartmentServiceProvider"],
  "files": [],

  "uwms": {
    "slug": "department",
    "version": "1.0.0",
    "type": "installable",
    "requires_core": "^1.0",
    "default_active": true,
    "author": { "name": "DIU Web Team", "email": "web@example.edu" },
    "license": "proprietary",
    "table_prefix": "department_",

    "dependencies": { "faculty": "^1.0" },
    "optional_dependencies": ["faculty-member", "statistics"],

    "morph_aliases": {
      "department.department": "Modules\\Department\\Models\\Department"
    },

    "custom_permissions": [
      "UpdateProfile:Department"
    ],

    "provides": {
      "relation_target": { "parent": "faculty" },
      "microsite": true,
      "scopeable": true,
      "assignable": true,
      "attachable": false,
      "person_source": false,
      "layouts": { "listing": ["grid", "list", "card"] },
      "homepage_sections": ["departments"],
      "menu_item_types": ["department"],
      "metrics": ["department.count"],
      "searchable": true,
      "sitemap": true,
      "api": true,
      "settings": true
    }
  }
}
```

### 5.2 Field Reference

| Field | বাধ্যতামূলক | নিয়ম |
|---|---|---|
| `uwms.slug` | ✔ | ছোট হাতের অক্ষর, সংখ্যা, `-`; অনন্য; পরিবর্তন অযোগ্য (BRL-MODM-02) |
| `uwms.version` | ✔ | Semantic version (`MAJOR.MINOR.PATCH`) |
| `uwms.type` | ✔ | `core` অথবা `installable`; ZIP দিয়ে আসা module সবসময় `installable` হতে হবে |
| `uwms.requires_core` | ✔ | Composer-ধরনের constraint; kernel version না মিললে install বাতিল |
| `uwms.default_active` | — | Bundled module-এর জন্য; ZIP install-এ উপেক্ষিত (FR-MODM-16) |
| `uwms.table_prefix` | ✔ | `{slug_underscore}_`; সব table এই prefix দিয়ে শুরু |
| `uwms.dependencies` | — | `{ slug: constraint }`; activate-এর আগে active থাকতে হবে |
| `uwms.optional_dependencies` | — | Slug তালিকা; থাকলে extra feature, না থাকলে লুকানো |
| `uwms.morph_aliases` | ✔ (polymorphic-এ অংশ নেওয়া model থাকলে) | `{ "department.department": "Modules\\Department\\Models\\Department" }`; alias `{slug}.{model}` format, system-wide অনন্য (Part B ADR-17) |
| `uwms.custom_permissions` | — | Resource / page / widget-এর বাইরের permission (যেমন Publish, HighlightMain, Import)। Resource, page ও widget-এর permission Shield automatically তৈরি করে, এখানে লিখতে হয় না (Section 7.4) |
| `uwms.provides.*` | — | Module কী register করে তার declaration; actual registration `{Name}Extension`-এ (Section 7)। Manifest ও Extension না মিললে activate বাতিল |

---

## 6. Base Model, Trait ও Migration Convention

### 6.1 Model Hierarchy

```
Illuminate\Database\Eloquent\Model
 ├── Platform\Models\BaseModel          ← সব business table (FR-GEN-01 থেকে 06)
 │    └── Modules\News\Models\NewsPost
 └── Platform\Models\BasePivot          ← pivot / relation / log table (soft delete ও is_active নেই, Q-06)
```

### 6.2 BaseModel-এর Default Trait (সব business model পায়)

| Trait | যা করে | Column | SRS Ref |
|---|---|---|---|
| `SoftDeletes` (Laravel) | Soft delete, restore | `deleted_at` | FR-GEN-01/02 |
| `HasActiveFlag` | `is_active` cast, `active()` scope, toggle | `is_active` (bool, default true, indexed) | FR-GEN-04 |
| `Blameable` | `created_by`, `updated_by`, `deleted_by` automatically | তিনটি nullable FK → users | FR-GEN-05 |
| `LogsPlatformActivity` | spatie activitylog, শুধু পরিবর্তিত field, sensitive field বাদ | — | FR-GEN-90/91 |
| `GuardsParentDelete` | Active child থাকলে delete-এ সতর্কতা / বাধা | — | FR-GEN-08 |

### 6.3 Opt-in Trait (প্রয়োজন অনুযায়ী model যোগ করবে)

| Trait | কখন ব্যবহার | Column / Relation | SRS Ref |
|---|---|---|---|
| `HasTranslations` (spatie) + `$translatable` | Multi-language field | JSON column | FR-GEN-40 থেকে 43 |
| `HasPublishDate` | Content-type model (পুরনো: Page, Program) | `published_at` (default এখন; ভবিষ্যৎ = scheduled); `frontendVisible()` scope-এ যোগ হয় (`status` column নেই — BRD Q-27) | FR-GEN-10/11 |
| `HasActiveAt` *(2026-10-16)* | নতুন content module (News, Notice, Events …) | `active_at` — একই নিয়ম (খালি / অতীত = দেখায়, ভবিষ্যৎ = scheduled); owner: নতুন table-এ `published_at` নয়। Kernel-এর search, scheduled index, context ক্রম ও PublishingPanel দুটোই চেনে (`Platform\Support\PublishDate::column()`) | FR-GEN-10/11 |
| `HasExpiry` | মেয়াদ অর্থবহ model (Notice, Slider, Announcement) | `expires_at` | FR-GEN-13 |
| `HasSlug` | Frontend URL আছে | `core_slugs` table-এ locale অনুযায়ী slug (P-21, ADR-11); slug history → redirect | FR-GEN-30 থেকে 32 |
| `HasSortOrder` | Drag & drop ক্রম | `sort_order` | FR-GEN-14 |
| `IsAttachable` | Content Relation-এর content দিক | P-01 | FR-REL-02 |
| `IsRelationTarget` | Content Relation-এর target দিক | P-01 | FR-REL-01 |
| `IsPersonSource` | Person directory | P-02 | FR-REL-09 |
| `IsAssignable` | ব্যক্তি assign করা যায় | P-02 | FR-REL-10 |
| `IsScopeable` | User scope-এর entity | P-03 | FR-ROLE-06 |
| `IsMicrositeOwner` | Microsite entity | P-04 থেকে P-09 | BR-MS |
| `AppliesUserScope` | Admin query user-এর scope দিয়ে filter | Global scope | FR-GEN-52 |
| `HasTaxonomy` | Category / tag | P-12 | FR-TAX |
| `HasMediaUsage` | Media Picker field | P-13 | FR-MEDIA-09 |
| `HasSeo` | Frontend-এ দেখানো record | P-14 | FR-GEN-70/71 |
| `IsSearchable` | Global search | P-16 | FR-SRCH-01 |
| `HasViewCount` | View counter | P-17 | FR-CNT-C08 |
| `HasRevisions` | Version history | P-18 | FR-PAGE-07 |

### 6.4 `frontendVisible()` — FR-GEN-12-এর একক বাস্তবায়ন

একটি record frontend-এ দেখাবে শুধু তখন, যখন সবকটি শর্ত সত্য:

```
is_active = true
AND deleted_at IS NULL
AND (published_at IS NULL OR published_at <= now())     (HasPublishDate থাকলে)
AND (expires_at  IS NULL OR expires_at  >  now())       (HasExpiry থাকলে)
AND module active                                        (Module registry)
```

সব frontend query, search index, sitemap, statistics metric ও API এই একটি scope ব্যবহার করবে। নিজস্ব শর্ত লেখা নিষেধ।

### 6.5 Migration Macro

Business table-এর জন্য:

```php
Schema::create('news_posts', function (Blueprint $table) {
    $table->id();
    // ... module-এর নিজস্ব column
    $table->platformColumns();   // is_active, created_by, updated_by, deleted_by, timestamps, softDeletes,
                                 // active_key (generated: deleted_at NULL হলে 1) + index
});
```

Pivot table-এর জন্য `$table->platformPivotColumns()` (শুধু timestamps)।

**নিয়ম:**

* Primary key: auto-increment integer `id` (UUID নয়)।
* Foreign key নীতি: Section 6.6 ও ADR-09।
* প্রতিটি migration-এর `down()` সঠিক থাকতে হবে (rollback ও uninstall-এর জন্য)।

### 6.6 Foreign Key ও Indexing নীতি

#### Foreign Key — কোথায় আছে, কোথায় নেই

| সম্পর্কের ধরন | DB-level FK | On delete | উদাহরণ | কারণ |
|---|---|---|---|---|
| একই module-এর ভেতরে | ✔ | `cascade` (child-only data) অথবা `restrict` | `program_majors.program_id` → `program_programs.id` | Module-এর ভেতরে পূর্ণ integrity |
| Kernel / core table-এ | ✔ | `restrict` বা `set null` | `*.created_by` → `users.id` (set null) | Kernel সবসময় আছে |
| **Hard dependency** module-এ (`dependencies`) | ✔ | `restrict` | `department_departments.faculty_id` → `faculty_faculties.id` | Dependency check নিশ্চিত করে parent module dependent-এর আগে uninstall হবে না; তাই FK নিরাপদ |
| **Optional dependency** module-এ | ✘ | — (application: `set null`) | `alumni_alumni.program_id` (Program optional) | Optional module uninstall হলে table মুছে যেতে পারে; FK থাকলে uninstall ব্যর্থ হবে |
| **Polymorphic** (P-01 থেকে P-21) | ✘ (technically অসম্ভব) | — (application cleanup) | `core_relations.target_type / target_id` | এক column একাধিক table নির্দেশ করে |

FK নেই এমন প্রতিটি reference-এর integrity রাখবে:

1. Record **force delete** হলে `RecordForceDeleted` listener সংশ্লিষ্ট reference মুছে দেবে বা `null` করবে।
2. Module **uninstall** হলে `ModuleUninstalling` listener kernel table ও optional reference পরিষ্কার করবে।
3. সাপ্তাহিক **orphan check** job: reference আছে কিন্তু record নেই এমন row-এর report (admin-এ সতর্কতা)।

> Soft delete-এ FK কোনো বাধা দেয় না, কারণ row থেকে যায়। FK কাজে আসে শুধু force delete ও uninstall-এ।

#### Index — বাধ্যতামূলক নিয়ম

| # | কোথায় | Index | উদাহরণ |
|---|---|---|---|
| IX-1 | প্রতিটি FK column | Single index (FK constraint নিজেই তৈরি করে) | `faculty_id` |
| IX-2 | FK নেই এমন প্রতিটি reference column | Single index — **বাধ্যতামূলক** (FK না থাকলে index নিজে দিতে হবে) | `alumni_alumni.program_id` |
| IX-3 | প্রতিটি polymorphic pair | Composite `(type, id)` | `(target_type, target_id)` |
| IX-4 | Polymorphic pivot | Unique composite, দুই দিকের জন্য দুটি index | `UNIQUE(content_type, content_id, target_type, target_id)` + `(target_type, target_id, content_type)` |
| IX-5 | Frontend visibility | Composite `(is_active, deleted_at)`; content model-এ `(is_active, deleted_at, published_at)` | `frontendVisible()` দ্রুত করতে |
| IX-6 | Listing sort | Sort column যুক্ত composite | `(published_at)`, `(sort_order)` |
| IX-7 | Highlight | `(target_type, target_id, is_highlighted, highlight_order)` | Homepage query |
| IX-8 | Unique business key | Unique index; soft delete থাকলে generated column `active_key` সহ (`deleted_at` সরাসরি নয় — MySQL-এ `NULL` আলাদা গণ্য হয়, তাই active row-এ uniqueness কাজ করে না; DB-02 Section 1.3) | `UNIQUE(department_id, short_name, active_key)` |
| IX-9 | Slug | `core_slugs`-এ `UNIQUE(sluggable_type, locale, slug)` (ADR-11) | URL থেকে record খোঁজা |
| IX-10 | Date-range query | তারিখ column | `events.starts_at`, `notices.expires_at` |
| IX-11 | বড় log / bulk table | Retention ও lookup column | `activity_log(created_at)`, `admission_results(session_id, application_serial)` |

**যাচাই:**

* CI-তে একটি migration test: reference column (`*_id`, `*_type`) আছে কিন্তু index নেই — এমন হলে test fail।
* Development-এ slow query log চালু; প্রতিটি module-এর প্রধান listing query `EXPLAIN` দিয়ে যাচাই (Database Design document-এ)।
* অতিরিক্ত index-ও ক্ষতিকর (write ধীর হয়); প্রতিটি index-এর কারণ data dictionary-তে লেখা থাকবে।

---

## 7. Extension Points

### 7.1 একক Registration Entry Point

প্রতিটি module-এ একটি `{Name}Extension` class থাকবে। Module **active** হলে kernel এটি চালায়; inactive হলে কিছুই register হয় না।

```php
namespace Modules\Department;

use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

final class DepartmentExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->relationTarget(Models\Department::class, parent: Faculty::class, parentKey: 'faculty_id');
        $r->micrositeEntity(Models\Department::class, routePrefix: 'department');
        $r->scopeable(Models\Department::class);
        $r->assignable(Models\Department::class, roles: ['head', 'coordinator']);
        $r->layouts('department.listing', ['grid', 'list', 'card']);
        $r->homepageSection(Sections\DepartmentsSection::class);
        $r->menuItemType(Models\Department::class);
        $r->metric('department.count', Metrics\DepartmentCount::class);
        $r->searchable(Models\Department::class);
        $r->settings(Settings\DepartmentSettings::class);
    }
}
```

### 7.2 Registrar Method তালিকা

| Method | কী register করে | ব্যবহার করে | SRS Ref |
|---|---|---|---|
| `relationTarget()` | Content attach করার target | Relation Engine, Related To field | FR-REL-01 |
| `attachable()` | Target-এ attach হওয়া content | Relation Engine, Highlight | FR-REL-02 |
| `personSource()` | ব্যক্তি-তথ্যের উৎস | Person Picker | FR-REL-09 |
| `assignable()` | যেখানে ব্যক্তি assign করা যায় (ভূমিকা তালিকা সহ) | Assignment | FR-REL-10 |
| `scopeable()` | User scope entity | Scope engine | FR-ROLE-06 |
| `micrositeEntity()` | Microsite (route prefix, parent) | Menu / Header / Homepage / Stat fallback | BR-MS |
| `dynamicPermissions()` | Record তৈরি হলে permission template | Role editor | FR-ROLE-07 |
| `filamentPlugin()` | Admin resource ও page | Admin Panel | FR-GEN-80 |
| `layouts()` | Listing / detail layout | Theme Management | FR-THEME-08 |
| `homepageSection()` | Homepage / microsite section | Homepage Builder | FR-GEN-102 |
| `pageBlock()` | Page builder block | Pages | FR-PAGE-02 |
| `entityTab()` | অন্য entity-র page-এ tab / section (যেমন Tuition → Program) | Program, Faculty ইত্যাদি | FR-PROG-18 |
| `entityPage()` *(v0.3, 2026-10-10)* | Admin-এ অন্য entity-র **Hub**-এ একটি অংশ (Filament page): অধীন record-এর list (Department → Faculty Hub, Program → Department / Faculty Hub), অন্য module-এর তথ্য (Tuition → Program Hub), অথবা প্রতিটি microsite entity-তে core-এর অংশ (Menu, Header & Footer, Homepage, Pages, Statistics) — target হিসেবে model অথবা `'microsite'` (সব microsite entity) | Entity Hub (record sub-navigation) | FR-GEN-86 |
| `menuItemType()` | Menu-তে link করার record type | Menu builder | FR-MENU-04 |
| `vocabulary()` | Taxonomy vocabulary | Taxonomy | FR-TAX-01 |
| `metric()` | Statistics metric | Statistics | FR-STAT-03 |
| `searchable()` | Search provider | Global Search | FR-SRCH-01 |
| `sitemap()` | Sitemap entry | SEO | FR-SEO-02 |
| `apiResource()` | Public API endpoint | API | FR-API-02 |
| `settings()` | Settings tab | Website Settings | FR-SET-10 |
| `notification()` | Notification type | Notification | FR-NOTI-03 |
| `freshnessSource()` | Content Update Reminder-এর জন্য entity ↔ content সম্পর্ক ও "update" সময় | Notification (Reminder) | FR-NOTI-10 |
| `dashboardWidget()` | Admin dashboard widget | Admin Panel | — |

### 7.3 Filament Integration

* প্রতিটি module একটি Filament plugin class (`{Name}Plugin`) দেবে, যা তার resource, page ও widget register করে।
* `AdminPanelProvider` শুধু **active** module-এর plugin panel-এ যোগ করবে (Module registry থেকে)। সব module folder scan করে resource auto-discover করা হবে না (ADR-05)।
* Resource-এর গঠন Filament v5 modular standard অনুযায়ী: `Resources/{PluralModel}/` folder-এর root-এ `{Model}Resource.php` (শুধু hook); তার ভেতরে `Pages/`, form logic `Schemas/{Model}Form.php`-এ, table logic `Tables/{PluralModel}Table.php`-এ (`make:filament-resource`-এর default output)।
* Navigation group (system-wide): `Academic`, `People`, `Content`, `Microsite`, `Services`, `Communication`, `Appearance`, `System`।
* *(v0.3, 2026-10-10)* **Entity Hub** (FR-GEN-86): microsite entity-র resource (Faculty, Department, Program, Office, Lab …) Filament-এর record sub-navigation পায়। Kernel-এর একটি base (`Platform\Filament\Hub`) resource-এর `getPages()` ও `getRecordSubNavigation()`-এ নিজের অংশ (Overview, Profile, People, Activity) এবং registry-তে `entityPage()` দিয়ে আসা অংশ যোগ করে — target model-এর জন্য, অথবা `'microsite'` হলে সব microsite entity-র জন্য। ফলে Faculty module Department / Program / Fee module-কে চেনে না (R-3 অক্ষত); Department module Faculty-তে `departments` সম্পর্ক যোগ করে `Model::resolveRelationUsing()` দিয়ে (সে-ই Faculty-র উপর নির্ভরশীল)। অধীন record-এর অংশ Filament `ManageRelatedRecords` page — parent আগে থেকে বসানো। প্রতিটি অংশের `canAccess()` permission ও scope মানে; inactive module-এর অংশ registry-তেই থাকে না।
* *(v0.4, 2026-10-10)* **Entity form part** (`$r->entityFormPart(Program::class, ProgramTuitionPart::class)`, provides `entity_form_parts`): অন্য module একটি entity-র editor-এর শেষে read-only অংশ যোগ করে (`Platform\Filament\Hub\EntityFormPart`: label, icon, sort, canView, badge, schema)। target module সেই module-কে চেনে না; existing record-এ ও দেখার অনুমতি থাকলে দেখায়। Hub-এ কোনো অংশ না থাকলে side menu দেখায় না।
* *(v0.5, 2026-10-10)* **Microsite tools ও Working in:** `$r->micrositeTool(ManageMenus::class)` (provides `microsite_tools`) — page `Platform\Context\MicrositeTool` মানে (canUseFor, urlFor, isSetUpFor); entity editor-এর `MicrositeActions::group()` ("Microsite ▾", ✓ / !) এগুলো সেই entity-র owner key-তে খোলে। `Platform\Context\WorkingIn` session-এ বর্তমান owner রাখে (top bar switcher `platform.working-in`, trait `FollowsWorkingIn`), "My Microsite" navigation scoped user-এর entity editor। `RelatedRecordsPart` — entityFormPart-এর সাধারণ read-only তালিকা (Add / row link)। Faculty / Department-এর Hub page (entityPage) সরে editor part হয়েছে; entityPage kind পরে দরকার হলে থাকছে।
* *(v0.6, 2026-10-10)* **Website page** (`$r->websitePage('scholarship.index', label: …, path: '/scholarships')`, provides `website_pages`): module-এর website-এর তালিকার page; Menu-র "Module page" item এগুলো থেকে বাছে (module বন্ধ হলে item লুকায়)। Content module-এর নিয়ম (Module Guide): website page + category archive URL (`/{module}/{vocabulary}/{term}`, FR-TAX-05) + একটি page block (`$r->pageBlock()`)।
* *(v0.3)* একই কাজের কয়েকটি screen Filament **Cluster** দিয়ে একটি menu item-এর ভেতরে (FR-GEN-87): `Waiver Calculator` (Rules, Waiver categories, Boards, Rule test, Settings), `Tuition Fees` (Tuition fees, Fee categories)।

### 7.4 Permission Architecture — Filament Shield + Scope

দুটি আলাদা প্রশ্ন, দুটি আলাদা ব্যবস্থা:

| প্রশ্ন | উত্তর দেয় | কোথায় সেট হয় |
|---|---|---|
| User **কী** করতে পারবে? (News create, Program delete, Dashboard widget দেখা) | **Filament Shield** (spatie/laravel-permission-এর উপর) | Shield Role UI |
| User **কোন data-তে** করতে পারবে? (শুধু CSE, অথবা Engineering-এর সব) | **Platform Scope engine** (P-03) | User form-এর Scope field |
| User **কোন Office / Lab**-এ করতে পারবে? | **Dynamic entity permission** (Shield custom permission হিসেবে) | Shield Role UI-এর "Entity Permissions" tab |

#### Permission-এর প্রকার

| প্রকার | কে তৈরি করে | Key format (Shield) | উদাহরণ |
|---|---|---|---|
| Resource permission | Shield, module activate-এর সময় module-এর resource থেকে | `{Action}:{Resource}` | `Create:NewsPost`, `ViewAny:Department` |
| Page permission | Shield | `View:{Page}` | `View:ManageSettings` |
| Widget permission | Shield | `View:{Widget}` | `View:AdmissionStatsWidget` |
| Custom permission | Module manifest-এর `custom_permissions` | `{Action}:{Subject}` | `PublishMain:NewsPost` (main site-এ প্রকাশ, BRD Q-26), `HighlightMain:NewsPost`, `Import:Course` |
| Dynamic entity permission | Kernel, Office / Lab তৈরি হলে (FR-ROLE-07) | `{Action}:{Entity}#{id}` | `Manage:Office#12`, `Content:Lab#3` |

> SRS-এ লেখা permission (যেমন `news.create`, `office.{id}.manage`) **logical নাম**; বাস্তব key উপরের Shield format-এ হবে। Mapping প্রতিটি module-এর README-তে থাকবে।
>
> চূড়ান্ত separator ও case Shield config (`config/filament-shield.php`) অনুযায়ী একবার নির্ধারণ হবে এবং পরে পরিবর্তন করা যাবে না।

#### Authorization-এর ক্রম (প্রতিটি admin action)

```
1. Shield permission আছে?                 (যেমন Update:NewsPost)        না → 403
2. Record কি user-এর scope-এর ভেতরে?        (Scope engine, P-03)           না → 403
3. Office / Lab record হলে entity permission আছে? (Manage:Office#12)       না → 403
4. Business rule (যেমন Main Site highlight → HighlightMain)                না → 403
→ অনুমতি
```

* Shield-এর generate করা policy একটি kernel base policy (`Platform\Policies\ScopedPolicy`) extend করবে, যা ধাপ ২ ও ৩ যোগ করে। এজন্য Shield-এর policy stub publish ও customize করা হবে।
* List query-তে ধাপ ২ আসে global scope (`AppliesUserScope`) থেকে, যাতে scope-এর বাইরের record তালিকাতেই না আসে।
* Super Admin: Shield-এর super admin role — সব ধাপ bypass।

#### Module Lifecycle-এর সাথে সংযোগ

| ঘটনা | Permission-এ কী হয় |
|---|---|
| Install / Activate | Module-এর resource, page, widget-এর জন্য Shield permission generate + manifest-এর `custom_permissions` তৈরি |
| Deactivate | Permission থাকে; Role UI-তে ধূসর (FR-ROLE-03) |
| Uninstall (data সহ) | Module-এর সব permission মুছে ফেলা |
| Office / Lab তৈরি / permanent delete | Dynamic permission তৈরি / মুছে ফেলা |

#### Conflict নিয়ম (Shield-এর কারণে নতুন)

Shield permission key resource-এর class নাম থেকে তৈরি হয়, module slug থেকে নয়। তাই দুটি module-এ `CategoryResource` থাকলে key সংঘর্ষ হবে। নিয়ম:

* **Model, Resource, Page ও Widget-এর class নাম পুরো system-এ অনন্য** হতে হবে (resource permission key model-এর নাম থেকে — POC ফলাফল দেখুন)। সাধারণ নাম module prefix সহ: `NewsCategoryResource`, `NoticeCategoryResource` (শুধু `CategoryResource` নয়)।
* Install validator module-এর resource / page / widget class নাম ও generate হতে যাওয়া permission key বিদ্যমান key-এর সাথে মিলিয়ে দেখবে; মিললে install বাতিল।

#### Proof of Concept (Development শুরুর আগে যাচাই)

| # | যাচাই করার বিষয় | সফলতার শর্ত |
|---|---|---|
| POC-1 | Module activate-এর সময় শুধু সেই module-এর resource-এর Shield permission programmatically generate | অন্য module-এর permission অপরিবর্তিত |
| POC-2 | Customized policy stub দিয়ে generate করা policy `ScopedPolicy` extend করে | CSE user EEE record edit করলে 403 |
| POC-3 | Shield Role UI-তে dynamic entity permission আলাদা tab-এ দেখানো (RoleResource customize) | "Manage: Registrar Office" checkbox দেখা যায় |
| POC-4 | Inactive module-এর permission Role UI-তে ধূসর / আলাদা | Admin বিভ্রান্ত হয় না |

> কোনো POC ব্যর্থ হলে সেই অংশের জন্য বিকল্প (Shield-এর বাইরে নিজস্ব component) এই document-এ আবার নির্ধারণ হবে।

#### POC ফলাফল (Phase 0, 2026-10-09) — ✔ চারটিই সফল

Laravel 13.35, Filament 5.10, Shield 4.3.1, spatie/laravel-permission **8.3** (আগে ৭ লেখা ছিল), nwidart 13.0। Test: `tests/Feature/Poc/ShieldScopePocTest.php` — ১৩টি test pass।

| # | ফল | বাস্তবায়ন |
|---|---|---|
| POC-1 | ✔ | `Platform\Modules\ModulePermissions::sync($module)` — module-এর resource class file থেকে খুঁজে (`app/Filament/Resources/**/*Resource.php`) `FilamentShield::getDefaultPermissionKeys()` দিয়ে key তৈরি; panel-এ register না থাকলেও (inactive module) কাজ করে। অন্য module-এর permission-এর id ও নাম অপরিবর্তিত |
| POC-2 | ✔ | `stubs/filament-shield/{DefaultPolicy,MultiParamMethod,SingleParamMethod}.stub` override → generate করা policy `Platform\Policies\ScopedPolicy` extend করে ও `$this->allows()` ডাকে। Policy module-এর নিজের `app/Policies/`-এ তৈরি হয় ও Laravel নিজে খুঁজে পায়। CSE user EEE record-এর edit URL-এ 403, নিজের record-এ 200; faculty scope-এ অধীন department (hierarchy) |
| POC-3 | ✔ | `Platform\Permissions\EntityPermissions` — Office তৈরি হলে `Manage:Office#{id}`; platform-এর `RoleResource` (Shield-এরটি extend) "Entity permissions" tab-এ "Manage: Registrar Office" দেখায় ও save করে; Office editor শুধু নিজের office |
| POC-4 | ✔ (সংশোধনসহ) | Inactive module-এর permission "Inactive modules" tab-এ ধূসর (disabled)। **POC-এ পাওয়া ঝুঁকি:** Shield role save করার সময় শুধু form-এ থাকা permission রাখে (`syncPermissions`), আর inactive module-এর resource panel-এ নেই — তাই কোনো role save করলেই inactive module-এর permission মুছে যেত। সমাধান: platform-এর `EditRole::afterSave()` সেই role-এর inactive module permission রেখে দেয় (control test দিয়ে প্রমাণিত) |

**POC থেকে সংশোধন:**

* **Permission key model-এর নাম থেকে**, resource class থেকে নয় (Shield config `resources.subject = model`): `Update:Faq`, `ViewAny:NewsPost`। তাই **model class-এর নামও পুরো system-এ অনন্য** হতে হবে (যেমন `NewsCategory`, `NoticeCategory` — শুধু `Category` নয়); উপরের conflict নিয়মে resource-এর সাথে model-ও যুক্ত।
* Shield-এর নিজস্ব RoleResource-এর বদলে `Platform\Filament\Resources\Roles\RoleResource` panel-এ register হয় (Shield তখন নিজেরটি যোগ করে না)।
* Super Admin bypass `ScopedPolicy`-তে (`hasRole(super_admin)`); Shield-এর `define_via_gate = false` অপরিবর্তিত।

---

## 8. Event Catalog

Kernel ও core-এর dispatch করা event। Module এগুলো listen করতে পারবে; নিজের event `Modules\{Name}\Events\` namespace-এ রাখবে।

| Event | কখন | Payload | প্রধান listener |
|---|---|---|---|
| `ModuleInstalled` | Install সম্পন্ন | slug, version | Notification, Log |
| `ModuleActivated` / `ModuleDeactivated` | Status পরিবর্তন | slug | Cache clear, Search re-index, Menu / Homepage সতর্কতা |
| `ModuleUpdated` | Update সম্পন্ন | slug, পুরনো ও নতুন version | Notification, Log |
| `ModuleUninstalling` | Uninstall-এর আগে | slug, keepData | Relation / Assignment / Media usage / Taxonomy পরিষ্কার |
| `ThemeActivated` | Theme পরিবর্তন | slug | View ও CSS cache clear |
| `SettingsUpdated` | Settings পরিবর্তন | group, key | Cache clear |
| `ContentPublished` / `ContentUnpublished` | Status পরিবর্তন | model | Search index, Sitemap, Statistics cache, Newsletter (ঐচ্ছিক) |
| `ContentHighlightChanged` | Highlight যোগ / বাদ / মেয়াদ শেষ | model, context | Homepage cache clear |
| `EntityHierarchyChanged` | Department-এর Faculty বা Program-এর Department পরিবর্তন | entity, পুরনো ও নতুন parent | Scope cache, Menu fallback cache, Statistics cache |
| `MicrositeEntityCreated` / `Deleted` | Office / Lab তৈরি বা মুছে ফেলা | entity | Dynamic permission তৈরি / মুছে ফেলা |
| `PersonUpdated` | Person source-এর তথ্য পরিবর্তন | person | Assignment-এর frontend cache |
| `UserScopeChanged` | User-এর scope পরিবর্তন | user | Permission cache |
| `MediaReplaced` | File replace | media | CDN / view cache |

---

## 9. Naming Convention (চূড়ান্ত)

`{slug}` = module slug (যেমন `faculty-member`), `{prefix}` = underscore রূপ (যেমন `faculty_member`)।

| বিষয় | নিয়ম | উদাহরণ (`faculty-member`) |
|---|---|---|
| Module folder ও PHP namespace | PascalCase | `Modules\FacultyMember\` |
| Table | `{prefix}_` + plural snake | `faculty_member_profiles` |
| Kernel table | `core_` prefix | `core_relations`, `core_assignments` |
| Route name (web / admin / api) | `{slug}.` / Filament default / `api.v1.{slug}.` | `faculty-member.show` |
| Public URL segment | Module settings-এ configurable, default slug | `/faculty-member/{slug}` |
| Filament Resource / Page / Widget class | System-wide অনন্য; সাধারণ নামে module prefix | `FacultyMemberProfileResource`, `NewsCategoryResource` |
| Permission (Shield) | `{Action}:{Resource}` (Section 7.4) | `Update:FacultyMemberProfile` |
| Dynamic permission | `{Action}:{Entity}#{id}` | `Manage:Office#12` |
| Config key | `{slug}.*` | `config('faculty-member.per_page')` |
| Settings key | `{slug}.{key}` | `faculty-member.show_email` |
| View ও translation namespace | `{slug}::` | `faculty-member::listing.grid` |
| Layout ID | `{slug}.{page}.{layout}` | `faculty-member.listing.grid` |
| Metric ID | `{slug}.{metric}` | `faculty-member.count` |
| Livewire / Filament component | Module namespace-এর ভেতরে | — |
| Event class | `Modules\{Name}\Events\{PastTense}` | `ProfileUpdated` |
| Queue job | `Modules\{Name}\Jobs\{Verb}{Noun}` | `SyncExternalProfiles` |
| Asset path | `modules/{slug}/` | `modules/faculty-member/app.css` |

---

## 10. Module Lifecycle (Technical)

### 10.1 Install (ZIP)

```
Upload ZIP ─► Staging (storage/app/module-packages/{uuid})
   │
   ├─ 1. Structure check: module.json, composer.json, README, app/{Name}Extension.php
   ├─ 2. Security check: allowed extension, path traversal নেই, vendor/ নেই, symlink নেই,
   │                     composer.json-এর "require" খালি (ADR-03)
   ├─ 3. Manifest validation: বাধ্যতামূলক field, type = installable, slug format
   ├─ 4. Compatibility: requires_core ↔ kernel version
   ├─ 5. Conflict check:
   │      • slug, folder, namespace আগে থেকে নেই
   │      • migration `--pretend` চালিয়ে table নাম বের করা → সব table_prefix দিয়ে শুরু ও বিদ্যমান নয়
   │      • Filament resource / page / widget class নাম system-wide অনন্য
   │      • Shield-এর generate হতে যাওয়া permission key ও custom_permissions বিদ্যমান নয়
   │      • route file parse → route name `{slug}.` / `api.v1.{slug}.` prefix
   ├─ 6. Dependency: dependencies install আছে কিনা (active হওয়া activate-এর সময় দেখা হবে)
   ▼
Backup (database) ─► Move to Modules/{Name} ─► Autoload refresh
   ─► Run module migrations (dependency-র পরে; batch আলাদা রাখা)
   ─► Create custom_permissions (Shield resource permission তৈরি হবে activate-এর সময়)
   ─► Run {Name}DatabaseSeeder ─► Status = installed (inactive) ─► ModuleInstalled
   │
   └─ যেকোনো ধাপে error: migration rollback (module-এর batch) → folder মুছে ফেলা
      → permission মুছে ফেলা → staging পরিষ্কার → error report (FR-MODM-06)
```

> MySQL-এ DDL transaction-এর ভেতরে rollback হয় না; তাই rollback হবে module-এর নিজস্ব migration batch উল্টো চালিয়ে, এবং প্রয়োজনে install-এর আগের database backup থেকে।

### 10.2 Activate / Deactivate

* **Activate:** dependency active? → manifest `provides` ↔ Extension মিলছে? → status active → route, config, view, Filament ও platform registry cache rebuild → `ModuleActivated`।
* **Deactivate:** কোনো active module কি এটির উপর নির্ভরশীল? → status inactive → cache rebuild → `ModuleDeactivated`। Data, permission, relation অক্ষত (FR-MODM-09)।
* Status রাখা হবে database-এ (custom nwidart activator), file-এ নয় (ADR-02)।

### 10.3 Update

ZIP → install-এর একই validation → version নতুন কিনা (downgrade নিষেধ) → backup (database + পুরনো module folder `storage/app/module-backups`) → folder replace → নতুন migration → permission sync (নতুন যোগ, বাদ পড়াগুলো চিহ্নিত) → `ModuleUpdated`। Error হলে পুরনো folder ফিরিয়ে আনা ও database restore।

### 10.4 Uninstall

শুধু inactive module। `ModuleUninstalling` (listener-রা kernel table থেকে এই module-এর relation, assignment, media usage, taxonomy, highlight, search index সরাবে) → "Data সহ" হলে module-এর migration rollback ও permission মুছে ফেলা → folder মুছে ফেলা। "Data রাখুন" হলে table থাকবে; পরে একই slug আবার install হলে data ফিরে পাবে।

### 10.5 বাস্তবায়নের নোট (Phase 1B, 2026-10-09)

| বিষয় | সিদ্ধান্ত |
|---|---|
| Status | `Platform\Modules\DatabaseActivator` (`core_modules`)। **Row না থাকলে** bundled module (`Modules/`-এ) manifest-এর `default_active` মানে, বাকিগুলো inactive — তাই নতুন installation, deploy ও test row ছাড়াই চলে। Bundled module data সহ uninstall হলে row থাকে `status = uninstalled` |
| Runtime module-এর জায়গা (ADR-12 সংশোধন) | Symlink নয়: `config/platform.php` → `modules.runtime_path` (`UWMS_MODULE_RUNTIME_PATH`), nwidart `scan.paths`-এ যুক্ত। Production-এ shared folder-এ নির্দেশ করে |
| Autoload (ADR-03) | `ModuleAutoloader` runtime module-এর PSR-4 Composer loader-এ যোগ করে। এজন্য nwidart auto-discovery বন্ধ (`composer.json` → `dont-discover`) এবং `bootstrap/providers.php`-এ kernel-এর **পরে** register — নইলে ZIP module-এর provider class পাওয়া যায় না |
| Extension | `Platform\Registry\Registrar` (২১টি method + `filamentPlugin`), `Registry`, `ExtensionLoader`। Admin panel plugin এখন Extension-এর `filamentPlugin()` থেকে; Office-এর dynamic permission `dynamicPermissions()` থেকে |
| `provides` key | `relation_target, attachable, person_source, assignable, scopeable, microsite, dynamic_permissions, layouts, homepage_sections, page_blocks, entity_tabs, menu_item_types, vocabularies, metrics, searchable, sitemap, api, settings, notifications, freshness_source, dashboard_widgets` — অচেনা key validator-এ বাতিল |
| Table conflict | `migrate --pretend`-এর SQL থেকে table নাম; dry run-এ migration exception দিলে install সেখানেই বাতিল (কিছু migrate হওয়ার আগে) |
| Database backup | Backup module (C-?) আসার আগে install / update-এ backup ধাপ `skipped` হিসেবে log হয় |
| জানা সীমাবদ্ধতা | Laravel-এর Migrator একই PHP process-এ migration file path অনুযায়ী cache করে; long-running worker (queue, Octane)-এ module update করলে worker restart বাধ্যতামূলক (deploy-এর `horizon:terminate` ইতিমধ্যে আছে) |
| Command | `module:make-uwms {Name}`, `uwms:module:install {zip|folder}`, `uwms:module:activate|deactivate {slug}`, `uwms:module:uninstall {slug} [--delete-data]`, `uwms:modules:sync` (bundled module + kernel ও module permission; `db:seed` চালায়) |

---

## 11. Coding Standard ও Development Workflow

| বিষয় | নিয়ম |
|---|---|
| Code style | Laravel Pint (Laravel preset); CI-তে বাধ্যতামূলক |
| Static analysis | Larastan level 6 (kernel-এ level 8) |
| Test | Pest; kernel coverage ≥ ৭০% (NFR-MNT-03); প্রতিটি module-এ অন্তত lifecycle test (install → activate → deactivate → reactivate → uninstall) |
| Architecture test | D-1 থেকে D-5 (Pest `arch()`) |
| Module scaffold | `php artisan module:make-uwms {Name}` — Section 4-এর structure, manifest, Extension, Plugin, sample resource তৈরি করবে (FR-MODM-15) |
| Filament import | সব component, column, action, filter explicitly import; string class reference নয় |
| Commit | Conventional Commits (`feat(news): ...`) |
| Versioning | Kernel ও প্রতিটি module আলাদা semantic version |

---

## 12. Architecture Decision Records (ADR)

| ID | সিদ্ধান্ত | বিকল্প | কারণ |
|---|---|---|---|
| ADR-01 | Kernel (`platform/`) module-এর বাইরে আলাদা layer | সব কিছু module হিসেবে | Kernel কখনো disable হবে না; module-রা নির্ভরযোগ্য contract পায় |
| ADR-02 | Module status database-এ (custom nwidart activator) | nwidart-এর default file activator (`modules_statuses.json`) | Multi-server deployment, audit log ও transaction-নিরাপদ |
| ADR-03 | ZIP module নিজস্ব composer dependency আনতে পারবে না | Runtime-এ `composer require` চালানো | Production-এ composer চালানো ঝুঁকিপূর্ণ ও ধীর; version conflict। নতুন package লাগলে core release-এ যোগ হবে |
| ADR-04 *(সংশোধিত v0.2)* | **Filament Shield** (spatie/laravel-permission-এর উপর) role ও permission-এর জন্য; data scope আলাদা Platform Scope engine-এ | নিজস্ব role editor | Shield-এ ready role UI, resource / page / widget / custom permission ও policy generation আছে — পরিচালনা সহজ। Shield "কী করতে পারবে" দেখে, Scope engine "কোন data-তে" — দুটি আলাদা, তাই সংঘর্ষ নেই। ঝুঁকি (class নাম-ভিত্তিক key, dynamic permission, policy-তে scope যোগ) Section 7.4-এর নিয়ম ও POC-1 থেকে 4 দিয়ে নিয়ন্ত্রিত |
| ADR-05 | Filament resource শুধু active module-এর plugin থেকে register | সব module folder scan করে auto-discover | Inactive module-এর admin menu দেখানো যাবে না (FR-MODM-07/09) |
| ADR-06 | Seeder Module Manager install-এর সময় চালায় | Root `DatabaseSeeder` সব module seeder auto-detect করে | ZIP দিয়ে পরে install হওয়া module-এর seeding; development-এ root seeder শুধু bundled module চালাবে |
| ADR-07 | Translation JSON column (spatie/laravel-translatable) | আলাদা translation table প্রতিটি model-এর জন্য | Module developer-এর জন্য সহজ; MySQL 8 JSON support; search ও sort-এর জন্য search index (P-16) locale অনুযায়ী আলাদা text রাখবে |
| ADR-08 | একটি kernel-স্তরের Content Relation table | প্রতিটি module-এর নিজস্ব pivot | নতুন target যোগ করলে কোনো module-এ পরিবর্তন লাগে না (AC-REL-02); highlight ও microsite query এক জায়গায় |
| ADR-09 *(সংশোধিত v0.2)* | FK আছে: module-এর ভেতরে, kernel table-এ, এবং **hard dependency** module-এ। FK নেই: optional dependency ও polymorphic reference — সেখানে index বাধ্যতামূলক ও application cleanup (Section 6.6) | (ক) সব cross-module FK বাদ; (খ) সব জায়গায় FK | (ক) অপ্রয়োজনীয়ভাবে integrity হারায়: dependency check-ই নিশ্চিত করে parent module আগে uninstall হবে না। (খ) optional module uninstall আটকে যায় এবং polymorphic-এ FK সম্ভব নয় |
| ADR-11 *(নতুন v0.2)* | Translatable slug আলাদা `core_slugs` table-এ (sluggable, locale, slug) | Slug JSON column-এ (spatie translatable) | JSON-এর ভেতরের মান index ও unique করা যায় না → URL থেকে record খোঁজা ধীর এবং "প্রতিটি ভাষায় unique" (FR-GEN-31) database-স্তরে নিশ্চিত হয় না। `core_slugs` slug history ও automatic redirect-ও রাখে (FR-GEN-32) |
| ADR-10 | Integer auto-increment ID | UUID | Filament modular standard; index ছোট ও দ্রুত; বর্তমান DIU data migration সহজ |

---

## 13. SRS Traceability

| SRS অংশ | SAD Part A Section |
|---|---|
| FR-GEN-01 থেকে 08 (data lifecycle) | 6.2, 6.5 |
| FR-GEN-10 থেকে 13, FR-GEN-12 | 6.3, 6.4 |
| FR-GEN-30 থেকে 33, 40 থেকে 43 | 6.3, ADR-07 |
| FR-MODM-01 থেকে 16 | 5, 10, ADR-02/03 |
| FR-ROLE-01 থেকে 09, FR-GEN-52, BR-SEC-04/05 | 7.4, ADR-04 |
| FR-GEN-30 থেকে 32 (slug) | 6.3, 6.6 (IX-9), ADR-11 |
| BR-MOD-05, BR-MOD-06 (no conflict) | 2.2, 5.2, 9, 10.1 |
| FR-REL, FR-ROLE-06/07, FR-STAT-03, FR-SRCH-01, FR-MENU-04, FR-PROG-18 | 7.2 |
| NFR-MNT-01 থেকে 04 | 11 |
