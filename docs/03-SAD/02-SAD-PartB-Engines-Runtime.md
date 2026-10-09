# System Architecture Document (SAD)
## Part B — Core Engines, Theme Engine, Request Flow, Runtime ও Deployment

| বিষয় | তথ্য |
|---|---|
| Document | SAD Part B |
| Version | 0.1 (Draft) |
| Date | 2026-10-08 |
| Status | **Approved** (2026-10-08); Deployment: Standard topology (একটি application server) দিয়ে শুরু |
| Based on | [SAD Part A v0.2](01-SAD-PartA-Foundation.md), [SRS v1.0](../02-SRS/00-SRS-Overview.md) |
| পাঠক | Core developer, technical lead, DevOps |

> এখানে table-এর **logical** নাম ও মূল column দেওয়া হয়েছে engine বোঝাতে। চূড়ান্ত column, type ও index Database Design document-এ।

## সূচি

1. [Kernel Boot ও Registry](#1-kernel-boot-ও-registry)
2. [Entity Hierarchy Service](#2-entity-hierarchy-service)
3. [Content Relation Engine ও Highlight](#3-content-relation-engine-ও-highlight)
4. [Person Assignment Engine](#4-person-assignment-engine)
5. [Scope Engine](#5-scope-engine)
6. [Microsite Context ও Fallback Resolver](#6-microsite-context-ও-fallback-resolver)
7. [Statistics Engine](#7-statistics-engine)
8. [Theme Engine](#8-theme-engine)
9. [Request Flow](#9-request-flow)
10. [Caching](#10-caching)
11. [Queue](#11-queue)
12. [Scheduler](#12-scheduler)
13. [Security Architecture](#13-security-architecture)
14. [Deployment Architecture](#14-deployment-architecture)
15. [Architecture Decision Records (Part B)](#15-architecture-decision-records-part-b)
16. [SRS Traceability](#16-srs-traceability)

---

## 1. Kernel Boot ও Registry

### 1.1 Boot Sequence

```
Laravel boot
  └─ PlatformServiceProvider::register()
       ├─ Kernel service bind (RelationEngine, ScopeEngine, FallbackResolver ...)
       └─ Custom nwidart activator (database) bind  ─── ADR-02
  └─ nwidart: শুধু active module-এর ServiceProvider load
  └─ PlatformServiceProvider::boot()
       ├─ Registry cache আছে?  ── হ্যাঁ ─► cache থেকে registry load (দ্রুত পথ)
       │                        └─ না ──► প্রতিটি active module-এর {Name}Extension::register()
       │                                   চালিয়ে registry তৈরি → manifest `provides`-এর সাথে মিলানো
       │                                   → cache-এ রাখা
       └─ Event listener, morph map, view namespace, Filament plugin তালিকা প্রস্তুত
  └─ AdminPanelProvider: registry থেকে active module-এর Filament plugin যোগ (ADR-05)
```

### 1.2 Registry

| Registry | কী রাখে | কে ব্যবহার করে |
|---|---|---|
| `RelationTargetRegistry` | Target model, label, parent type | Relation Engine, Related To field, Hierarchy |
| `AttachableRegistry` | Attachable model, highlight সমর্থন | Relation Engine, Highlight |
| `PersonSourceRegistry` | Person model, presenter | Person Picker, Assignment |
| `AssignableRegistry` | Assignable model, অনুমোদিত ভূমিকা | Assignment |
| `ScopeableRegistry` | Scope entity ও প্রতিটি model-এর scope পথ | Scope Engine |
| `MicrositeRegistry` | Microsite entity, route prefix, parent | Microsite Context, Fallback |
| `LayoutRegistry`, `HomepageSectionRegistry`, `PageBlockRegistry`, `EntityTabRegistry` | Frontend extension | Theme, Homepage Builder, Pages, Entity page |
| `MenuItemTypeRegistry`, `VocabularyRegistry`, `MetricRegistry`, `SearchRegistry`, `SitemapRegistry`, `ApiRegistry`, `SettingsRegistry`, `NotificationRegistry` | Part A Section 7.2 অনুযায়ী | সংশ্লিষ্ট core module |

### 1.3 Morph Map

Polymorphic column-এ PHP class নাম নয়, **স্থির alias** লেখা হবে (`Relation::enforceMorphMap`)। Alias = `{slug}.{model}` (যেমন `department.department`, `news.post`)।

* কারণ: class বা namespace পরিবর্তন করলে database-এর data ভাঙবে না; পুরনো class নাম ফাঁস হয় না।
* Module-এর morph alias manifest-এ declare হবে এবং install validator অনন্যতা যাচাই করবে।
* Inactive module-এর alias-ও map-এ থাকবে (data পড়া ও cleanup-এর জন্য), কিন্তু query-তে সেই type বাদ যাবে।

---

## 2. Entity Hierarchy Service

**কাজ:** কোনো entity-র ancestor ও descendant বের করা (FR-REL-07)। Scope, Menu fallback, Statistics ও Relation query এটি ব্যবহার করে।

### 2.1 Hierarchy সংজ্ঞা

Hierarchy registry থেকে আসে (`relationTarget(parent: ...)`), hardcode নয়:

```
Main Site (root, virtual)
 ├── Faculty ── Department ── Program
 ├── Office  (parent Office ঐচ্ছিক)
 └── Lab     (fallback-এ parent নেই; scope-এর জন্য parent Department ঐচ্ছিক)
```

### 2.2 বাস্তবায়ন (ADR-13)

* Entity সংখ্যা কম (DIU, `COUNT(*)`: ৯ Faculty, ৪৯ Department, ৬৪ Program, ২৪ Office, ১১৩ Lab — মোট ~২৬০ node)। তাই পুরো tree memory-তে রাখা যায়।
* **Hierarchy snapshot:** প্রতিটি node `type:id → { parent, ancestors[], descendants[] }`; Redis-এ একটি key, প্রথম প্রয়োজনে তৈরি।
* Invalidate: `EntityHierarchyChanged`, entity create / delete / restore, module activate / deactivate।
* Database-এ আলাদা closure table নেই; parent FK-ই source of truth।

```
ancestors('program', 57)   → [department:12, faculty:3]
descendants('faculty', 3)  → [department:12, department:13, program:57, program:58, ...]
```

---

## 3. Content Relation Engine ও Highlight

### 3.1 Table (logical): `core_relations` — P-01

| Column | অর্থ |
|---|---|
| `content_type`, `content_id` | Attachable (যেমন `news.post`, 881) |
| `target_type`, `target_id` | Target (যেমন `department.department`, 12); Main Site = `site`, `0` (ADR-15) |
| `is_highlighted`, `highlight_order`, `highlight_until` | Context Highlight (FR-CNT-H01) |
| `created_by`, timestamps | — |

Index: `UNIQUE(content_type, content_id, target_type, target_id)`; `(target_type, target_id, content_type)`; `(target_type, target_id, is_highlighted, highlight_order)` (Part A IX-3, IX-4, IX-7)।

> **Main Site row-এর অর্থ (BRD Q-26, DB-05 Section 1):** `site:0` row থাকা মানে content **main site-এ প্রকাশিত**; সেই row-এর `is_highlighted` মানে main homepage-এ highlight। Main site-এর listing (`/news`) ও main homepage শুধু `site:0` row থাকা content দেখায়। `site:0` row তৈরি / মুছে ফেলা শুধু `PublishMain` permission থাকা user (default: Admin)।

### 3.2 Write Path — "Related To" ও Highlight save

```
Form submit (News #881)
 ├─ Related To = [faculty:3, department:12]
 ├─ Main site  = published (site:0 row)
 ├─ Highlight  = { site: on(1, until 2026-11-01), department:12: on(1) }
 └─ RelationEngine::sync(content)
      1. Scope check: প্রতিটি target user-এর scope-এর ভেতরে?        (FR-GEN-51)
      2. site:0 যোগ / বাদ হলে PublishMain permission আছে?               (FR-CNT-H07)
         Main Site highlight হলে HighlightMain permission আছে?          (FR-CNT-H02)
         Permission না থাকলে site:0 row অপরিবর্তিত থাকে (entity user save করলেও Admin-এর সিদ্ধান্ত মুছে যায় না)
      3. Diff করে insert / update / delete (একটি transaction)
      4. ContentHighlightChanged (পরিবর্তন থাকলে) → cache invalidate
```

### 3.3 Read Path — একটি context-এর content

```sql
-- CSE (department:12)-এর news, descendant (Program) সহ, highlight আগে
SELECT p.* FROM news_posts p
JOIN core_relations r
  ON r.content_type = 'news.post' AND r.content_id = p.id
WHERE (   (r.target_type = 'department.department' AND r.target_id IN (12))
       OR (r.target_type = 'program.program'       AND r.target_id IN (57, 58)) )  -- descendants (FR-REL-08 option)
  AND <frontendVisible conditions>                                                  -- Part A 6.4
ORDER BY (r.is_highlighted AND (r.highlight_until IS NULL OR r.highlight_until > NOW())) DESC,
         r.highlight_order, p.published_at DESC
LIMIT 6;
```

Engine এটি `RelationEngine::for($context)->content('news.post')->includeDescendants()->mode($mode)` ধরনের API দিয়ে দেবে; module raw SQL লিখবে না।

### 3.4 Homepage Section Source Mode (FR-CNT-H04, FR-HOME-11)

| Mode | Algorithm |
|---|---|
| Latest | Context-এর visible content, `published_at DESC`, N টি |
| Highlighted only | `is_highlighted` ও মেয়াদ আছে, `highlight_order` অনুযায়ী, N টি পর্যন্ত |
| Highlighted first, then latest *(default)* | Highlighted (ক্রম অনুযায়ী) + বাকি জায়গা Latest দিয়ে, duplicate বাদ |
| Manual pick | Section settings-এ রাখা content id তালিকা, সেই ক্রমে; কোনোটি invisible হলে বাদ |

Main Site homepage-এ context = `site`; "Latest" mode-এ `site:0` row থাকা (main site-এ প্রকাশিত) visible content।

### 3.5 Highlight মেয়াদ

* Query নিজেই `highlight_until > now()` যাচাই করে, তাই মেয়াদ শেষ হওয়া মাত্র কার্যকর।
* Hourly scheduled job মেয়াদোত্তীর্ণ highlight-এর `is_highlighted = false` করে এবং `ContentHighlightChanged` dispatch করে (cache ও admin "Highlights" view হালনাগাদ)।

---

## 4. Person Assignment Engine

### 4.1 Table (logical)

| Table | কাজ | মূল column |
|---|---|---|
| `core_assignments` (P-02) | ব্যক্তি ↔ Assignable | `person_type`, `person_id`, `assignable_type`, `assignable_id`, `role_key`, `role_label` (translatable), `sort_order`, `starts_on`, `ends_on`, `is_public`, `overrides` (JSON: phone / extension / email) |
| `core_manual_persons` | কোনো directory-তে নেই এমন ব্যক্তি | নাম (translatable), পদবি, প্রতিষ্ঠান, ছবি, contact |

### 4.2 Manual Person একটি Person Source (ADR-14)

Manual entry-ও `core_manual_persons`-এ একটি person হিসেবে রাখা হয় এবং kernel-এর built-in person source। ফলে প্রতিটি assignment-এ সবসময় একটি `person_type/person_id` থাকে — দুই রকম query লাগে না।

### 4.3 Person Presenter Contract

প্রতিটি person source একটি presenter দেয়:

```
PersonPresenter: name(), designation(), photoUrl(), email(), phone(), extension(), profileUrl(), isActive()
```

Frontend সবসময় presenter থেকে পড়ে, তারপর assignment-এর `overrides` প্রয়োগ করে। তাই কর্মকর্তার phone পরিবর্তন করলে সব জায়গায় নতুন নম্বর (AC-ADMN-01)।

### 4.4 Read Path

```
assignments(target: office:3, role: 'team', current: true)
 → WHERE assignable = office:3 AND role_key = 'team'
     AND (ends_on IS NULL OR ends_on >= today) AND is_public
 → person type অনুযায়ী group করে প্রতিটি source থেকে eager load (N+1 নেই)
 → person inactive বা source module inactive হলে বাদ (BRL-REL-04/05)
 → sort_order
```

"Former" তালিকা: `ends_on < today` (BRL-REL-06, FR-LEAD-05)।

### 4.5 Standard Role Catalog (FR-REL-11)

* Module registrar-এ `assignable(Model::class, roles: ['head', 'coordinator', ...])` দিয়ে স্থির role key দেয়; activate-এ `core_assignment_roles`-এ seed হয়।
* Admin label, ক্রম ও active বদলান; key বদলানো যায় না। ফলে একাধিক university-তে একই key, ভিন্ন label (যেমন `head` = "Head" বা "Chairperson")।
* People তালিকার ক্রম: catalog-এর `sort_order` (ভূমিকা অনুযায়ী) → assignment-এর `sort_order` (ব্যক্তি অনুযায়ী)।
* `is_single_holder` ভূমিকায় নতুন ব্যক্তি assign করলে আগের current assignment-এর `ends_on` set করার প্রস্তাব (Former তালিকায় যায়)।

---

## 5. Scope Engine

### 5.1 Table (logical): `core_user_scopes` — P-03

`user_id`, `scopeable_type`, `scopeable_id`। Office ও Lab এখানে নয় — সেগুলো dynamic entity permission (Part A 7.4)।

### 5.2 Effective Scope গণনা

```
user.scopes = [faculty:3]
effective   = faculty:3 ∪ descendants(faculty:3)
            = { faculty:[3], department:[12,13,...], program:[57,58,...] }
```

* কোনো scope না থাকলে user **unscoped** (সব data; BRL-ROLE-03)।
* Per-user cache; invalidate: `UserScopeChanged`, `EntityHierarchyChanged`।

### 5.3 Model-এ Scope প্রয়োগের পথ

প্রতিটি scope-যোগ্য model registry-তে জানায় কীভাবে তাকে scope-এর সাথে মেলাতে হবে:

| পথ | কোন model | Filter |
|---|---|---|
| **Self** | Faculty, Department, Program | `id IN effective[type]` |
| **Direct column** | যেমন Course (`department_id`), Faculty Member (`department_id`), Tuition (`program_id`) | `{column} IN effective[target]` |
| **Relation** | Attachable content (News, Notice ...) | `EXISTS (core_relations WHERE content = this AND target IN effective)` |
| **Owner** | Page, Menu, Homepage, Stat Set (P-04 থেকে P-08) | `owner IN effective` |
| **None** | Scope প্রযোজ্য নয় (যেমন Transport) | কোনো filter নেই; শুধু permission |

প্রয়োগ হয় তিন জায়গায় (Part A 7.4-এর ধাপ ২):

1. `AppliesUserScope` global scope — admin list ও query।
2. `ScopedPolicy` — single record view / update / delete।
3. Form option — Related To, owner select-এ শুধু effective scope-এর entity (FR-GEN-51)।

### 5.4 নতুন Record-এ Scope

Scoped user নতুন content তৈরি করলে Related To-তে তাঁর scope-এর entity default হিসেবে select থাকে এবং অন্তত একটি বাধ্যতামূলক। এভাবে scoped user এমন record তৈরি করতে পারে না যা পরে নিজেই দেখতে পাবে না।

### 5.5 বাস্তবায়নের নোট — Section 1.3 থেকে 5 (Phase 1C, 2026-10-09)

| বিষয় | সিদ্ধান্ত |
|---|---|
| Entity key | সব engine-এ একই রূপ: `{morph alias}:{id}` — যেমন `department.department:12`; Main Site `site:0` |
| Morph map | `Relation::enforceMorphMap` — module-এর `morph_aliases` (inactive সহ) + kernel: `core.user`, `core.manual-person`, `core.assignment`। Alias ছাড়া কোনো model polymorphic column-এ লিখলে exception |
| Hierarchy | `Platform\Hierarchy\EntityHierarchy`; registrar-এর signature: `relationTarget(Model::class, parent: ParentModel::class, parentKey: 'parent_column')` (Part A §7.1-এর `parent: 'faculty'` উদাহরণের বদলে)। Snapshot একটি cache key-তে; target record save / delete / restore ও module activate / deactivate-এ মুছে যায় |
| Relation | `Platform\Relations\RelationEngine` (`sync`, `publishToMainSite`, `requestMainSite`, `dismissRequest`, `expireHighlights`) ও `ContextQuery` (`latest`, `highlighted`, `highlightedFirst`, `manual`, `mode`, `includeDescendants`)। `PublishMain:{Model}` ও `HighlightMain:{Model}` attachable model-এর জন্য activate-এ তৈরি হয়। `uwms:highlights:expire` প্রতি ঘণ্টায় |
| Assignment | `Platform\Assignments\AssignmentEngine` (`assign`, `people`, current / former); Role Catalog-এর default label ও single-holder `config/platform.php` → `role_catalog`; module key দেয় (`assignable(roles: [...])`), activate-এ seed হয়, পরে label বদলালে re-sync-এ অক্ষত থাকে। Manual person kernel-এর built-in person source |
| Scope filter | `AppliesUserScope` → `UserScopeFilter`; path নিজে থেকে: `IsRelationTarget` = self, `IsAttachable` = relation, অথবা `$scopeColumns`। **শুধু admin panel-এর request-এ** প্রযোজ্য (public website-এ নয়)। Scope-এর বাইরের record-এর edit URL তাই **404** দেয় (অস্তিত্বও প্রকাশ পায় না); policy-স্তরে `can()` = false থাকে (Test Plan TC-PERM-03) |
| Uninstall-এ kernel data | "Data সহ" uninstall-এ module-এর alias-এর relation, request, assignment ও role catalog মুছে যায়। **"Data রাখুন"-এ রেখে দেওয়া হয়** (Part A §10.4 থেকে পরিবর্তন) — নইলে পুনরায় install করলে data ফিরলেও "Show in" ও people হারিয়ে যেত |

---

## 6. Microsite Context ও Fallback Resolver

### 6.1 Microsite Context

প্রতিটি public request-এ একটি `MicrositeContext` তৈরি হয়:

```
/en/department/cse/news/convocation-2026
      │         │    │
      │         │    └─ ভেতরের path → News module-এর detail route
      │         └─ slug → core_slugs → department:12
      └─ microsite route prefix (MicrositeRegistry)

MicrositeContext = { entity: department:12, chain: [department:12, faculty:3, site] }
```

Microsite-এর বাইরের URL (যেমন `/en/news/...`) → context = `site`।

### 6.2 Generic Fallback Resolver

Menu, Header / Footer, Template, Homepage, Stat Set, Logo / Banner — সবার জন্য **একটি** resolver:

```
resolve(resource, location, context):
    mode = micrositeSettings(context.entity).fallbackMode(resource)   # own / inherit (default) / main
    chain = match mode:
        own     → [entity]
        inherit → [entity, ...ancestors(entity), site]                 # BRL-MENU-01
        main    → [entity, site]
    for node in chain:
        found = store(resource).find(owner = node, location)
        if found and found.is_active: return found
    return themeDefault(resource, location)
```

| Resource | Store | Location উদাহরণ | SRS Ref |
|---|---|---|---|
| Menu | P-04 | primary, footer-1, quick-links-icon | FR-MENU-07, BRL-MENU-01 |
| Header / Footer | P-05 | header, footer | FR-HF-05 |
| Homepage | P-06 | home | FR-HOME-08 |
| Stat Set | P-07 | default | FR-STAT-08 |
| Template / Logo / Banner | P-09 | template, logo, banner | BR-MS-05/06, FR-SLD-06 |

* **Location অনুযায়ী আলাদা resolve:** CSE-এর নিজস্ব Primary menu আছে কিন্তু Footer নেই → Footer আসবে Engineering বা Main Site থেকে।

**বাস্তবায়ন (Phase 1D, 2026-10-09):**

| অংশ | Class | নোট |
|---|---|---|
| Context | `Platform\Context\SiteContext` | `site()` / `for($entity)`; `key()`, `chain()` (entity → ancestors → `site:0`), `settings()` |
| URL → context | `Platform\Context\MicrositeResolver` | `micrositeEntity(Model::class, routePrefix: 'department')`-এর prefix; ঐচ্ছিক locale segment; slug `core_slugs` থেকে; পুরনো slug হলে `redirect` (301-এর URL); inactive বা অজানা entity → `null` (404) |
| Slug | `Platform\Concerns\HasSlug` | প্রতিটি ভাষায় তৈরি (`config('platform.locales')`), একই type-এ অনন্য (`-2` suffix), পরিবর্তনে পুরনোটি history |
| Resolver | `Platform\Context\FallbackResolver` | Core module প্রতিটি resource-এর জন্য একটি **store** register করে: `store('menu', fn ($type, $id, $location) => …, themeDefault)`। `resolve()` item দেয়, `source()` বলে কোথা থেকে এসেছে ("Inherited from …" indicator)। Mode `core_microsite_settings.fallback_modes`-এ (`own` / `inherit` / `main`, default `inherit`)। `template` store kernel-এরই (settings table থেকে) |
| Cache | — | এখন request-ভিত্তিক memo; persistent cache (`fallback:{resource}:{location}:{entity}:{locale}`) ও invalidation আসবে store-গুলোর সাথে (Phase 2) |
* Lab ও Office-এর chain: `[entity, site]` (parent Office থাকলে ও "inherit" হলে `[office, parentOffice, site]`)।
* ফলাফল cache: key `fallback:{resource}:{location}:{entity}:{locale}`; invalidate: সংশ্লিষ্ট resource-এর পরিবর্তন, `EntityHierarchyChanged`।

---

## 7. Statistics Engine

### 7.1 Table (logical)

`core_stat_sets` (owner — P-07, নাম), `core_stat_items` (set, label translatable, source `manual / metric`, `metric_key`, `metric_params` JSON (যেমন Level filter), `manual_value`, `override_value`, `rounding`, prefix, suffix, icon, sort_order, is_active)।

### 7.2 Metric Provider Contract

```
interface MetricProvider {
    public function key(): string;                          // 'program.count'
    public function label(): string;
    public function count(StatContext $ctx, array $params): int;
}
```

`StatContext` = owner entity + তার descendant (Hierarchy Service থেকে)। Provider শুধু `frontendVisible()` record গণনা করবে (BRL-STAT-02)।

```
program.count  @ faculty:3   → COUNT(programs WHERE department_id IN descendants(faculty:3).department)
program.count  @ site        → COUNT(all visible programs)
```

### 7.3 মান নির্ধারণ

```
value = override_value ?? (source == manual ? manual_value : cachedMetric(key, ctx, params))
display = prefix + round(value, rounding) + suffix        # "1,234" → "1,200+"
metric provider-এর module inactive → item লুকানো (FR-STAT-04)
```

### 7.4 Cache

Key `metric:{key}:{ctxType}:{ctxId}:{paramsHash}`, tag `metric:{module}`। Invalidate: সেই module-এর `ContentPublished` / `ContentUnpublished` / delete / restore, `EntityHierarchyChanged`; তার উপর hourly refresh (FR-STAT-05)।

---

## 8. Theme Engine

### 8.1 Theme Package

```
themes/default/
├── theme.json            # slug, version, requires_core, supported layouts, menu locations,
│                         # header/footer variants, microsite templates, design token defaults
├── views/
│   ├── layouts/          # app.blade.php (base), microsite.blade.php
│   ├── templates/        # page template: default, full-width, with-sidebar, landing
│   ├── partials/         # header variants, footer variants, breadcrumb ...
│   └── modules/{slug}/   # module view override (ঐচ্ছিক)
├── assets/               # Tailwind / CSS / JS (CSS variable ব্যবহার)
└── screenshot.png
```

Theme-এ PHP business logic নিষেধ (BRL-THEME-01)। Theme ZIP install-এর validation module-এর মতো (FR-THEME-02)।

### 8.2 View Resolution ক্রম (FR-THEME-10)

```
news::listing.grid  চাওয়া হলে:
  1. themes/{active}/views/modules/news/listing/grid.blade.php      (theme override)
  2. Modules/News/resources/views/listing/grid.blade.php            (module default)
```

Kernel একটি custom view finder দিয়ে এই ক্রম প্রয়োগ করে; module সবসময় `news::` namespace দিয়ে view ডাকবে।

### 8.3 Layout নির্বাচন

`LayoutResolver::for('news.listing', $context)` → settings-এ নির্বাচিত layout (FR-THEME-08) → active theme সেটি সমর্থন না করলে module-এর default (FR-THEME-11)।

### 8.4 Design Token → CSS Variable

```
Theme settings (color, typography, radius ...)
   └─► compile: /storage/app/public/theme/{theme}-{hash}.css
         :root { --color-primary: #0b5ed7; --font-heading: "Poppins"; --font-body-bn: "Hind Siliguri"; ... }
   └─► base layout-এ <link> (hash-ভিত্তিক URL → browser cache নিরাপদ)
```

* Settings পরিবর্তন → নতুন hash → নতুন file; পুরনোটি মুছে যায়।
* Entity-ভিত্তিক logo ও banner একই fallback resolver দিয়ে (Section 6)।
* ভাষা অনুযায়ী font: `:lang(bn)` selector দিয়ে বাংলার font (FR-THEME-05)।

### 8.5 Preview

Admin "Preview" করলে session-এ preview theme রাখা হয়; শুধু সেই admin-এর request-এ preview theme প্রযোজ্য, visitor-এর নয় (FR-THEME-03)।

### 8.6 Frontend প্রযুক্তি: Blade + Livewire (ADR-18)

* Public website server-rendered **Blade**; interactive অংশ **Livewire 4** component (Filament-এর সাথে একই Livewire)।
* Interactive অংশের উদাহরণ: Waiver calculator, Admission result lookup, Program / Directory filter, Notice board filter ও pagination, Global search (instant), Transport route finder, Inquiry ও Newsletter form, FAQ helpful, Highlight-ভিত্তিক tab (News / Notices / Events)।
* **দায়িত্ব ভাগ:** Livewire component **module**-এর (`Modules/{Name}/app/Livewire/`, logic ও data); তার **view** Blade, তাই theme override করতে পারে (`themes/{theme}/views/modules/{slug}/livewire/...`) — logic theme-এ নয় (BRL-THEME-02)।
* Public form-এর নিরাপত্তা (rate limit, honeypot, captcha, validation) Livewire action-এ — API Spec Section 8-এর একই নিয়ম।
* Livewire component full-page cache-এর বাইরে (ADR-16): cache করা page-এ component lazy-load হয়।
* JavaScript ন্যূনতম: Alpine.js (Livewire-এর সাথে আসে) — slider, lightbox, mega menu, mobile menu।
* Release 1-এ REST API নেই (API Spec APIQ-02); module-এর data access service class-এ রাখা হবে, যাতে Release 2-এ API Resource একই service ব্যবহার করে।

---

## 9. Request Flow

### 9.1 Public Request

```
Browser ─► Nginx (HTTPS, static file, gzip/brotli)
   ─► PHP-FPM ─► Laravel
        1. Maintenance mode?                          → maintenance page (admin bypass)
        2. Redirect middleware: core_redirects-এ path? → 301 / 302  (FR-SEO-04)
        3. Locale: URL prefix → session → default     (FR-GEN-33)
        4. Full-page cache (guest, GET)?  hit ─────────► response (ADR-16)
        5. Route match (module route, active module only)
        6. Slug resolve: core_slugs(type, locale, slug) → record   (না পেলে default locale-এ খোঁজা)
        7. MicrositeContext তৈরি (Section 6.1)
        8. Visibility: frontendVisible() নয় → 404
        9. Controller → data (RelationEngine, Assignment, Metric ...)
       10. View: LayoutResolver → ThemeViewFinder → Blade
       11. Response + SEO meta + JSON-LD + hreflang
        └─ 404 হলে: 404 log (FR-SEO-06) → theme 404 page
```

### 9.2 Admin Request

```
Browser ─► /admin (Filament 5, Livewire 4)
   1. Auth (session) + 2FA (প্রযোজ্য হলে)
   2. Panel: শুধু active module-এর plugin / resource
   3. Navigation: Shield permission অনুযায়ী menu
   4. List: AppliesUserScope global scope → scope-এর ভেতরের record
   5. Action: Shield policy → ScopedPolicy (scope + entity permission) → business rule (Part A 7.4)
   6. Save: BaseModel trait (Blameable, LogsActivity) → RelationEngine::sync → event → cache invalidate
```

### 9.3 API Request

```
/api/v1/{module}/... ─► rate limit ─► (Sanctum token, admin API হলে) ─► module active?
   ─► locale (?locale=) ─► frontendVisible() ─► API Resource (JSON) ─► HTTP cache header
```

---

## 10. Caching

Store: Redis (tag সমর্থনের জন্য)। Database cache driver ব্যবহৃত হলে tag-ভিত্তিক invalidation-এর বদলে version-key কৌশল।

| Cache | Key / Tag | TTL | Invalidate হয় |
|---|---|---|---|
| Platform registry | `platform:registry:{modulesHash}` | চিরস্থায়ী | Module activate / deactivate / update |
| Hierarchy snapshot | `platform:hierarchy` | চিরস্থায়ী | `EntityHierarchyChanged`, entity create / delete |
| User effective scope | `scope:user:{id}` | ১ ঘণ্টা | `UserScopeChanged`, hierarchy পরিবর্তন |
| Shield permission | spatie permission cache | Package default | Role / permission পরিবর্তন |
| Settings | `settings:{group}` | চিরস্থায়ী | `SettingsUpdated` |
| Fallback result | `fallback:{resource}:{location}:{entity}:{locale}` | চিরস্থায়ী | Resource পরিবর্তন, hierarchy পরিবর্তন |
| Rendered menu | tag `menu:{menuId}` | চিরস্থায়ী | Menu পরিবর্তন, target record visibility পরিবর্তন |
| Homepage section data | tag `section:{sectionId}`, `content:{module}` | ১০ মিনিট | Section পরিবর্তন, `ContentPublished`, `ContentHighlightChanged` |
| Metric value | tag `metric:{module}` | ১ ঘণ্টা | Section 7.4 |
| Slug lookup | `slug:{type}:{locale}:{slug}` | ১ দিন | Slug পরিবর্তন |
| Theme CSS | hash-ভিত্তিক file | চিরস্থায়ী | Design setting পরিবর্তন |
| Full-page (guest) | tag `page`, `content:{module}`, `site` | ১০ মিনিট | Content / menu / settings / theme পরিবর্তন (ADR-16) |
| Laravel route / config / view / event cache | Artisan | Deploy পর্যন্ত | Deploy, module lifecycle |

**নিয়ম:** cache invalidation সবসময় event থেকে (Part A Section 8); controller-এ সরাসরি `Cache::forget` নয়।

---

## 11. Queue

Driver: Redis + Horizon (ছোট installation-এ database queue)।

| Queue | কাজ | উদাহরণ job |
|---|---|---|
| `default` | সাধারণ | Notification dispatch |
| `media` | ভারী image কাজ | Image conversion (thumbnail, medium, large, WebP), EXIF মুছে ফেলা |
| `search` | Index | Search index update / remove, sitemap regenerate |
| `mail` | Email (throttled) | Newsletter campaign, auto-reply, invitation |
| `imports` | Bulk import / export | Admission result, course, faculty member, partner import |
| `maintenance` | দীর্ঘ রক্ষণাবেক্ষণ | Backup, log archive, orphan check, module install-এর ভারী ধাপ |

* Failed job retry: ৩ বার, exponential backoff; চূড়ান্ত ব্যর্থতায় Super Admin notification (backup ও module install-এর ক্ষেত্রে)।
* Import job chunk-এ (১,০০০ row) চলবে এবং progress admin UI-তে দেখাবে।

---

## 12. Scheduler

`* * * * * php artisan schedule:run` (cron, একটি server-এ — `onOneServer()`)।

| সময় | Job | SRS Ref |
|---|---|---|
| প্রতি মিনিট | গত মিনিটে `published_at` পার হওয়া content-এর জন্য `ContentPublished` event (search index, sitemap, cache); visibility নিজে query-তে হিসাব হয়, তাই এই job শুধু side-effect-এর জন্য | FR-GEN-11 |
| প্রতি ঘণ্টা | Highlight মেয়াদ শেষ | FR-CNT-H03 |
| প্রতি ঘণ্টা | Metric cache refresh | FR-STAT-05 |
| প্রতি ঘণ্টা | Notice / Announcement / Slider মেয়াদ শেষ → cache invalidate | FR-NOT-03, FR-SLD-04, FR-ANW-03 |
| প্রতিদিন ০১:০০ | Database backup | FR-BKP-01 |
| প্রতিদিন ০২:০০ | Activity log cleanup (retention ≥ ৬০ দিন) ও ঐচ্ছিক archive | FR-LOG-07/08 |
| প্রতিদিন ০২:৩০ | Backup retention cleanup | FR-BKP-05 |
| প্রতিদিন ০৩:০০ | পুরনো পড়া notification মুছে ফেলা (৯০ দিন), closed inquiry retention | FR-NOTI-08, FR-INQ-09 |
| প্রতিদিন ০৪:০০ | Sitemap regenerate | FR-SEO-02 |
| প্রতিদিন ০৯:০০ | Content Update Reminder: অবস্থা হিসাব ও reminder পাঠানো | FR-NOTI-11/12 |
| প্রতি সপ্তাহ (রবিবার ০৯:০০) | Content Update Reminder: Admin সারাংশ | FR-NOTI-13 |
| প্রতিদিন ০৮:০০ | মেয়াদ শেষের কাছাকাছি accreditation / linkage notification | FR-ACR-02, FR-INTL-03 |
| প্রতি সপ্তাহ (রবিবার ০১:৩০) | File / media backup | FR-BKP-01 |
| প্রতি সপ্তাহ | Orphan reference check (Part A 6.6) | ADR-09 |
| প্রতি সপ্তাহ | Broken link check | FR-LINK-03 |

সময় configurable; সব job activity log-এ ফলাফল লিখবে।

---

## 13. Security Architecture

| এলাকা | ব্যবস্থা | Ref |
|---|---|---|
| Transport | HTTPS বাধ্যতামূলক, HSTS | NFR-SEC-01/08 |
| Authentication | Filament login, bcrypt / argon2, password নীতি, login rate limit (৫ বার / ১৫ মিনিট), Filament multi-factor authentication (TOTP); Super Admin-এর জন্য বাধ্যতামূলক করার setting | NFR-SEC-02/03/04 |
| Session | Redis session, secure + httpOnly + SameSite cookie, idle timeout | NFR-SEC-07 |
| Authorization | Shield + Scope + entity permission (Part A 7.4); API-তেও একই policy | BR-SEC-01/04/05 |
| Input | Form Request validation; rich text HTML purifier (allow-list); Blade auto-escape | NFR-SEC-05 |
| Upload | Extension + প্রকৃত MIME যাচাই, executable নিষিদ্ধ, SVG sanitize, file নাম slugify, upload directory-তে PHP execute নিষিদ্ধ (Nginx rule) | FR-MEDIA-13 |
| Module / Theme ZIP | শুধু Super Admin; Part A 10.1-এর validation; vendor ও symlink নিষিদ্ধ; install-এর আগে backup; ঐচ্ছিক checksum / signature | BR-SEC-03 |
| Header | CSP (nonce-ভিত্তিক script), X-Frame-Options, X-Content-Type-Options, Referrer-Policy | NFR-SEC-08 |
| Public form | Honeypot, rate limit, ঐচ্ছিক captcha | NFR-SEC-06 |
| Secret | `.env` repository-তে নয়; production secret server-এর environment-এ | — |
| Personal data | Activity log-এ sensitive field নয়; Inquiry-র ব্যক্তিগত তথ্য retention-এর পর মুছে ফেলা; Alumni / Official contact-এর public visibility নিয়ন্ত্রণ | BRL-LOG-02, FR-INQ-09 |
| Backup | Encrypted archive (spatie/laravel-backup password), public URL-এ নয় | BRL-BKP-01 |
| Dependency | `composer audit` CI-তে | — |

> **জ্ঞাত ঝুঁকি:** Module ZIP মানে PHP code চালানো। Validation ক্ষতিকর কোড পুরোপুরি আটকাতে পারে না। তাই শুধু বিশ্বস্ত উৎসের module install করা উচিত, এবং install-এর অধিকার শুধু Super Admin-এর (BRD Section 10 Risks)।

---

## 14. Deployment Architecture

### 14.1 Environment

| Environment | উদ্দেশ্য |
|---|---|
| Local | Development (WSL / Docker) |
| Staging | Production-এর মতো; module / theme ZIP প্রথমে এখানে পরীক্ষা |
| Production | Live |

### 14.2 Topology

**Standard (প্রস্তাবিত শুরু) — একটি application server:**

```
                  Internet
                     │
              ┌──────▼──────┐
              │   Nginx     │  HTTPS, static, upload dir-এ PHP নিষিদ্ধ
              ├─────────────┤
              │  PHP-FPM    │  Laravel + Filament
              │  Horizon    │  queue worker
              │  Cron       │  schedule:run
              ├─────────────┤
              │  Redis      │  cache, queue, session
              └──────┬──────┘
                     │
              ┌──────▼──────┐        ┌──────────────────┐
              │  MySQL 8    │        │ Backup destination│ (S3 / Drive / FTP)
              └─────────────┘        └──────────────────┘
```

**Scaled — একাধিক application server:**

```
Load Balancer ─► App 1, App 2 ...  (Nginx + PHP-FPM)
                  │
                  ├─ Shared: Modules/runtime ও themes (NFS / shared volume)   ← ADR-12
                  ├─ Media: S3-compatible object storage
                  ├─ Redis (managed)
                  ├─ MySQL (managed, primary + read replica ঐচ্ছিক)
                  └─ Worker server: Horizon + Cron (onOneServer)
```

### 14.3 Runtime-এ Install হওয়া Module ও Deploy (ADR-12)

সমস্যা: ZIP দিয়ে install হওয়া module code server-এ লেখা হয়, repository-তে নয়। Zero-downtime deploy (নতুন release folder) করলে সেগুলো হারিয়ে যেতে পারে, এবং একাধিক server থাকলে শুধু একটিতে থাকে।

সমাধান:

| বিষয় | নিয়ম |
|---|---|
| Folder | Bundled module: `Modules/` (repository-তে)। ZIP-installed module: `Modules/` থেকে symlink করা **shared** folder `shared/modules-runtime/` (release-এর বাইরে, প্রতিটি deploy-এ টিকে থাকে) |
| একাধিক server | `shared/modules-runtime/` ও runtime theme shared volume-এ; install-এর পর সব server-এ OPcache reset ও cache rebuild-এর জন্য broadcast job |
| OPcache | Production-এ `validate_timestamps=0`; তাই module install / update / uninstall-এর পর OPcache reset (FPM pool reload বা cachetool) বাধ্যতামূলক ধাপ |
| Composer autoload | ZIP module PSR-4 root mapping (`Modules\\` → `Modules/`) দিয়ে load হয়; composer চালাতে হয় না (ADR-03) |

### 14.4 CI/CD Pipeline

```
Push / PR
  ├─ composer install, npm ci
  ├─ Pint (style) ─ Larastan ─ composer audit
  ├─ Pest: unit, feature, architecture (D-1 থেকে D-5), migration index test (Part A 6.6)
  ├─ Module lifecycle test: প্রতিটি bundled module install → activate → deactivate → reactivate → uninstall
  ├─ Asset build (Vite) — core ও default theme
  └─ Deploy (staging স্বয়ংক্রিয়; production manual approval)
        ├─ নতুন release folder, shared folder symlink (storage, .env, modules-runtime)
        ├─ php artisan migrate --force   (kernel ও bundled module; শুধু active module-এর)
        ├─ optimize (config, route, view, event cache) + platform registry cache rebuild
        ├─ Horizon terminate (graceful restart), OPcache reset
        └─ Health check → symlink switch (zero-downtime) → ব্যর্থ হলে আগের release-এ ফেরত
```

### 14.5 Server Requirement (Standard topology, শুরু)

| Resource | ন্যূনতম | প্রস্তাবিত |
|---|---|---|
| CPU | ২ vCPU | ৪ vCPU |
| RAM | ৪ GB | ৮ GB |
| Disk | ৫০ GB SSD | ১০০ GB SSD + object storage (media) |
| OS | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS |
| Software | PHP 8.4 (FPM; extension: intl, gd / imagick, redis, zip, bcmath, exif, pdo_mysql), MySQL 8, Redis 7, Nginx, Supervisor (Horizon), Node 20+ (build) | — |

> চূড়ান্ত sizing load test (NFR-CAP-02: ৫০০ concurrent visitor) ও media পরিমাণ দেখে Deployment Plan document-এ নির্ধারণ হবে।

---

## 15. Architecture Decision Records (Part B)

| ID | সিদ্ধান্ত | বিকল্প | কারণ |
|---|---|---|---|
| ADR-12 | ZIP-installed module release-এর বাইরে shared folder-এ; multi-server-এ shared volume ও OPcache reset broadcast | (ক) Runtime install বন্ধ, সব module repository দিয়ে deploy; (খ) প্রতিটি server-এ আলাদা install | BRD-এর "developer ছাড়া install" (BR-MOD-02) রক্ষা হয় এবং deploy-তে module হারায় না। (ক) BRD লঙ্ঘন করে; (খ) server-এর মধ্যে অমিল তৈরি করে |
| ADR-13 | Entity hierarchy memory / Redis snapshot | Closure table, nested set | Entity সংখ্যা কম (শতের ঘরে); parent FK-ই source of truth; আলাদা table রক্ষণাবেক্ষণ লাগে না |
| ADR-14 | Manual person = kernel-এর built-in person source (`core_manual_persons`) | Assignment-এর ভেতরে manual field | প্রতিটি assignment-এ একই query পথ; manual ব্যক্তিকে পরে একাধিক জায়গায় assign করা যায় |
| ADR-15 | Main Site = pseudo-target (`site`, `0`) Content Relation-এ | আলাদা highlight table | Main ও entity highlight একই table ও একই query; homepage engine-এ শাখা কম |
| ADR-16 | Guest-দের জন্য ঐচ্ছিক full-page cache (tag-ভিত্তিক invalidation), default চালু | শুধু fragment cache | NFR-PERF-01 (TTFB < ৫০০ms) ও NFR-CAP-02 (৫০০ concurrent) পূরণ; logged-in admin ও form page বাদ |
| ADR-17 | Polymorphic column-এ স্থির morph alias (`{slug}.{model}`) | PHP class নাম | Refactor-এ data ভাঙে না; install-এ alias অনন্যতা যাচাই করা যায় |
| ADR-18 *(API Spec APIQ-01/02)* | Public website **Blade + Livewire**; Release 1-এ REST API নেই | (ক) বর্তমান DIU-র মতো Next.js headless frontend + API; (খ) Blade + Release 1-এ API | Theme ZIP install, microsite fallback ও admin থেকে design পরিবর্তন server-side theme engine-এ সরল; একটি stack (Laravel + Livewire, Filament-এর সাথে একই) রক্ষণাবেক্ষণ সহজ; SEO-বান্ধব। API পরে (Release 2) — contract তৈরি আছে |

---

## 16. SRS Traceability

| SRS অংশ | SAD Part B Section |
|---|---|
| FR-REL-01 থেকে 10, AC-REL-01/02 | 1.2, 2, 3, 4 |
| FR-CNT-H01 থেকে H06, FR-HOME-11 | 3.1 থেকে 3.5 |
| FR-ROLE-05/06, FR-GEN-51/52/53, BR-SEC-04 | 5 |
| BR-MS-01 থেকে 10, FR-MENU-07/08, BRL-MENU-01, FR-HF-05, FR-HOME-08 | 6 |
| FR-STAT-01 থেকে 09 | 7 |
| FR-THEME-01 থেকে 12 | 8 |
| FR-GEN-12, FR-GEN-33, FR-SEO-04/06 | 9 |
| NFR-PERF-01 থেকে 06 | 10, ADR-16 |
| FR-GEN-11, FR-LOG-07, FR-BKP-01/05, FR-NOTI-08 | 12 |
| NFR-SEC-01 থেকে 08, FR-MEDIA-13 | 13 |
| NFR-REL-01 থেকে 04, BR-MOD-02 | 14, ADR-12 |
