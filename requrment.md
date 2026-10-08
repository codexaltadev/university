### University Website Management System – Core Concept

এই সিস্টেমটি মূলত একটি **Dynamic University Website Management System**, যেখানে বিশ্ববিদ্যালয়ের website-এর সকল গুরুত্বপূর্ণ academic, administrative এবং content-related information একটি centralized **Admin Panel** থেকে পরিচালনা করা যাবে।

সিস্টেমের **core entity** হিসেবে থাকবে:

* Faculty / School
* Department
* Program
* Course
* Faculty Member / Teacher
* Administration / Administrative Units
* Offices (Registrar Office, Exam Office ইত্যাদি)

এই core entities-গুলোর তথ্য Admin Panel থেকে dynamically **create, update, manage এবং publish** করা যাবে।

এর পাশাপাশি website-এর বিভিন্ন ধরনের content ও functionality পরিচালনার জন্য আলাদা CMS modules থাকবে, যেমন:

* News
* Events
* Notices / Announcements
* Testimonials
* Gallery
* Pages
* Menus
* Documents
* Achievements
* Publications
* Research
* FAQ
* Important Links ইত্যাদি।

বিশেষভাবে, **News, Events, Gallery, Documents, Notices, Testimonials ইত্যাদি CMS content-এর সাথে Faculty, Department, Program, Course বা অন্যান্য relevant entity-এর polymorphic relationship থাকবে।** ফলে একটি content প্রয়োজন অনুযায়ী এক বা একাধিক Faculty, Department, Program, Course বা অন্য entity-এর সাথে যুক্ত করা যাবে।

উদাহরণস্বরূপ, একটি News একই সাথে একটি Faculty এবং তার অন্তর্ভুক্ত একটি Department-এর সাথে সম্পর্কিত হতে পারে। আবার একটি Event কোনো নির্দিষ্ট Program বা Course-এর সাথে যুক্ত হতে পারে।

### Multiple Frontend Designs & Themes

সিস্টেমে একই module-এর জন্য **একাধিক frontend design / layout** থাকতে পারবে। অর্থাৎ Admin Panel থেকে কোনো module-এর content একই থাকলেও website-এ সেটি বিভিন্ন design বা layout-এর মাধ্যমে প্রদর্শন করা যাবে।

উদাহরণস্বরূপ:

* News → Grid / List / Card / Magazine layout
* Events → Calendar / Card / Timeline layout
* Faculty → Grid / List / Profile Card layout
* Programs → Card / List / Detailed layout
* Gallery → Masonry / Grid / Album layout

তবে design পরিবর্তন হলেও website-এর **central theme configuration** অনুযায়ী color, typography এবং অন্যান্য visual settings automatically apply হবে।

সিস্টেমে **multiple frontend themes** থাকবে এবং প্রতিটি theme-এর নিজস্ব design structure থাকতে পারে। Admin প্রয়োজন অনুযায়ী একটি theme select/activate করতে পারবে।

### Centralized Theme & Website Settings

Website-এর সকল গুরুত্বপূর্ণ settings Admin Panel থেকেই পরিচালনা করা যাবে। যেমন:

* Active Theme
* Primary Color
* Secondary Color
* Accent Color
* Font / Typography
* Logo
* Favicon
* Header
* Footer
* Homepage settings
* Menu
* Social Media
* Contact Information
* SEO settings
* Default images
* Website information
* Module visibility
* Homepage sections
* Layout / Design settings
* অন্যান্য global configuration

অর্থাৎ **content, design, theme এবং website configuration—সবকিছু Admin Panel থেকেই manage করা যাবে**, যাতে developer-এর সরাসরি code পরিবর্তনের প্রয়োজন না হয়।

এর ফলে সিস্টেমটি হবে **dynamic, reusable, theme-based এবং highly configurable**, যেখানে একই core system ব্যবহার করে বিভিন্ন university-এর জন্য আলাদা content, design, theme এবং configuration তৈরি ও পরিচালনা করা সম্ভব হবে।



Proposed Module List

Core Academic

Faculty / School
Department
Program
Course
Faculty Member / Teacher
Academic Session / Semester
Curriculum
Administration
Office
Campus / Location

Website Content
11. News
12. Events
13. Notice / Announcement
14. Testimonials
15. Pages
16. Gallery
17. Photo Albums
18. Video Gallery
19. Slider / Banner
20. Achievements
21. Publications
22. Research
23. Downloads / Documents
24. FAQ
25. Important Links

People & Organization
26. Administrative Staff
27. Office / Unit
28. Committee
29. Leadership / Authorities
30. Alumni

Website Structure
31. Menu Management
32. Header Management
33. Footer Management
34. Quick Links
35. Homepage Section Management

Communication
36. Contact Information
37. Contact / Inquiry
38. Newsletter / Subscription
39. Social Media

Theme & Design
40. Theme Management
41. Frontend Layout Management
42. Module Design / Layout Selection
43. Color Management
44. Typography Management
45. Logo & Branding
46. Homepage Design Configuration

System & CMS
47. Media Library
48. User Management
49. Role & Permission Management
50. Website Settings
51. SEO Management
52. Redirect Management
53. Activity / Audit Log
54. API Management

Architecture-এর মূল ধারণা:
Core Academic Entities + Polymorphic CMS Content + Multiple Frontend Designs + Multiple Themes + Centralized Admin Settings।



---

## Modular (Installable Module) Architecture

### লক্ষ্য

* একবার system develop হয়ে গেলে দৈনন্দিন কাজের জন্য **developer-এর প্রয়োজন হবে না** — সব কিছু Admin Panel থেকে configure করা যাবে।
* প্রতিটি feature হবে একটি **স্বতন্ত্র, installable module**।
* যে কেউ **শুধুমাত্র Module Development Documentation follow করে** নতুন module বানাতে পারবে, এবং সেটি **core বা অন্য কোনো module-এর সাথে conflict করবে না**।
* **Super Admin** Admin Panel থেকে module **install, activate, deactivate, update এবং uninstall** করতে পারবে।

### Core vs Module

System দুই ভাগে বিভক্ত থাকবে:

**১. Core (Kernel) — সবসময় চালু থাকবে, deactivate করা যাবে না**

Core-এ শুধু সেই জিনিসগুলো থাকবে যেগুলোর উপর সব module নির্ভর করে:

* Authentication, User Management
* Role & Permission Management
* Module Manager (install / activate / deactivate / uninstall)
* Website Settings Engine (module-রা নিজেদের settings এখানে register করবে)
* Theme Engine (Theme, Color, Typography, Logo & Branding, Layout resolution)
* Media Library
* Menu, Header, Footer, Homepage Section Engine
* **Polymorphic Relation Engine** (নিচে বিস্তারিত)
* SEO base, Redirect, Activity / Audit Log
* Hook / Event system, API base

**২. Modules — installable, activate / deactivate করা যাবে**

Proposed Module List-এর বাকি সবকিছু module হবে, যেমন: Faculty, Department, Program, Course, Faculty Member, News, Events, Notice, Gallery, Testimonials, Publications, Research, Alumni, Committee, Newsletter ইত্যাদি।

> নিয়ম: **Core কখনো কোনো module-এর উপর নির্ভর করবে না।** Module core-এর উপর নির্ভর করবে, এবং প্রয়োজনে অন্য module-এর উপর *declared dependency* রাখতে পারবে।

### Module-এর Structure

প্রতিটি module একটি নিজস্ব folder-এ থাকবে এবং একটি **manifest file (`module.json`)** থাকবে।

```
Modules/
└── News/
    ├── module.json            # manifest (বাধ্যতামূলক)
    ├── src/                   # Models, Controllers, Services, Admin resources
    ├── database/
    │   ├── migrations/
    │   └── seeders/
    ├── routes/                # web.php, api.php, admin.php
    ├── resources/
    │   ├── views/
    │   │   └── layouts/       # grid, list, card, magazine ...
    │   ├── lang/
    │   └── assets/
    ├── config/
    └── README.md
```

**`module.json` উদাহরণ:**

```json
{
  "name": "News",
  "slug": "news",
  "version": "1.0.0",
  "description": "University news management",
  "author": "...",
  "requires_core": ">=1.0 <2.0",
  "dependencies": {},
  "optional_dependencies": ["faculty", "department", "program"],
  "permissions": ["news.view", "news.create", "news.update", "news.delete", "news.publish"],
  "provides": {
    "attachable": true,
    "relatable_target": false,
    "layouts": ["grid", "list", "card", "magazine"],
    "homepage_sections": ["latest-news", "featured-news"],
    "admin_menu": true,
    "settings": true,
    "api": true,
    "sitemap": true,
    "search": true
  }
}
```

### Module Lifecycle (Super Admin)

| Action | কী হবে |
|---|---|
| **Upload / Install** | ZIP upload বা available list থেকে install → manifest validate → core version ও dependency check → migrations run → permissions ও default settings seed। ব্যর্থ হলে সম্পূর্ণ rollback। |
| **Activate** | Routes, admin menu, frontend pages, homepage sections, API, widgets চালু হবে। |
| **Deactivate** | সব route / menu / section / API বন্ধ হবে, কিন্তু **data মুছবে না**। পরে activate করলে আগের মতো ফিরে আসবে। |
| **Update** | নতুন version upload → নতুন migration run → version history রাখা হবে। |
| **Uninstall** | শুধুমাত্র deactivated module uninstall করা যাবে। Super Admin চাইলে data রাখতে বা মুছতে পারবে (confirmation সহ)। |

**Dependency নিয়ম:**

* Dependency active না থাকলে module activate হবে না (যেমন: Course → Program → Department → Faculty)।
* অন্য কোনো active module যার উপর নির্ভর করে, সেই module deactivate / uninstall করা যাবে না — Admin Panel কারণসহ জানাবে।
* Optional dependency থাকলে module কাজ করবে, কিন্তু সেই অংশটুকু শুধু তখনই দেখা যাবে যখন ওই module active থাকবে।

### Conflict এড়ানোর নিয়ম (Naming Convention)

প্রতিটি module-এর একটি **unique `slug`** থাকবে, এবং module-এর সবকিছু সেই slug দিয়ে prefix করা বাধ্যতামূলক:

| বিষয় | নিয়ম | উদাহরণ (`news`) |
|---|---|---|
| Code namespace | `Modules\{Name}\` | `Modules\News\Models\Post` |
| Database table | `{slug}_` prefix | `news_posts`, `news_categories` |
| Route name / URL | `{slug}.` / `/{slug}` | `news.index`, `/news` |
| Admin route | `admin.{slug}.` | `admin.news.edit` |
| Permission | `{slug}.{action}` | `news.publish` |
| Settings key | `{slug}.{key}` | `news.per_page` |
| View / translation namespace | `{slug}::` | `news::layouts.grid` |
| Event name | `{slug}.{event}` | `news.published` |
| Asset path | `modules/{slug}/` | `modules/news/app.css` |

**বাধ্যতামূলক নিয়ম:**

* Module কখনো **core-এর code বা অন্য module-এর code / table সরাসরি পরিবর্তন করবে না**।
* অন্য module-এর সাথে যোগাযোগ হবে শুধু **Hooks / Events, Contracts (interface) এবং Polymorphic Relation Engine**-এর মাধ্যমে।
* Install-এর সময় Module Manager slug, table name, route এবং permission-এর **duplicate check** করবে — conflict থাকলে install হবে না।

### Polymorphic Relation Engine (Core)

News, Events, Gallery ইত্যাদির সাথে Faculty, Department, Program ইত্যাদির সম্পর্ক **core-এ একটি central engine** দিয়ে manage হবে, যাতে module-গুলো একে অপরকে সরাসরি না চেনে।

* **Relatable Target module** (Faculty, Department, Program, Course, Office ...) নিজেকে "attach করা যায় এমন entity" হিসেবে register করবে।
* **Attachable Content module** (News, Events, Notice, Gallery, Documents, Testimonials ...) নিজেকে "attach হতে পারে এমন content" হিসেবে register করবে।
* Core-এর একটি সাধারণ table (যেমন `core_relations`: `content_type, content_id, target_type, target_id`) সব সম্পর্ক রাখবে।
* Admin form-এ "Related to" field **automatically** শুধু active target module-গুলো দেখাবে।
* কোনো target module deactivate হলে তার সম্পর্কিত data মুছবে না, শুধু লুকানো থাকবে।

ফলে নতুন কোনো module (যেমন "Research Center") install করলে, সেটি নিজেকে target হিসেবে register করলেই News / Events ইত্যাদি সাথে সাথে তার সাথে link হতে পারবে — **কোনো code পরিবর্তন ছাড়াই**।

### Extension Points (Module কী কী register করতে পারবে)

Module নিজের service provider / registration file থেকে core-এ নিচেরগুলো register করবে:

* Admin menu item ও dashboard widget
* Permissions
* Settings fields (Website Settings-এ module-এর নিজস্ব tab)
* Frontend layouts (Grid / List / Card ...) — Admin Panel থেকে select করা যাবে
* Homepage section / block — Homepage Section Management-এ দেখা যাবে
* Menu item type (যেমন "News Category" লিংক)
* Polymorphic target / attachable
* SEO meta provider, Sitemap entries, Search provider
* API endpoints
* Events / Hooks (listen ও dispatch)

### Theme ও Module-এর সম্পর্ক

* প্রতিটি module নিজের **default views ও layouts** নিয়ে আসবে।
* কোনো theme চাইলে module-এর view override করতে পারবে: `themes/{theme}/modules/{slug}/...`
* View খোঁজার ক্রম: **Active theme override → Module default layout**।
* সব layout core theme configuration-এর **CSS variables** (color, typography ইত্যাদি) ব্যবহার করবে, তাই theme বা color পরিবর্তন করলে সব module-এ automatically apply হবে।

### Module Manager (Super Admin Panel)

* সব module-এর তালিকা: name, version, status (Installed / Active / Inactive / Update available), author
* Install (ZIP upload), Activate, Deactivate, Update, Uninstall button
* Dependency tree ও "কোন module এর উপর নির্ভর করে" দেখা
* Install / update log এবং error message
* সব action Activity / Audit Log-এ record হবে
* শুধুমাত্র **Super Admin** এই section access করতে পারবে

### Security

* ZIP validation: শুধু allowed file types, path traversal বন্ধ, manifest বাধ্যতামূলক
* Install / update-এর আগে automatic database backup (optional setting)
* Migration fail হলে সম্পূর্ণ rollback
* (Optional) Trusted module-এর জন্য signature / checksum verification

### Module Development Documentation (যা লিখতে হবে)

নতুন developer যাতে শুধু documentation পড়ে module বানাতে পারে, তার জন্য:

1. **Getting Started** — একটি command দিয়ে module skeleton তৈরি (যেমন `php artisan module:make Research`)
2. **`module.json` Reference** — প্রতিটি field-এর ব্যাখ্যা
3. **Naming Convention ও Conflict Rules** — উপরের টেবিল
4. **Extension Points API** — menu, permission, settings, layout, homepage section, relation register করার উদাহরণ
5. **Polymorphic Relation Guide** — target / attachable হিসেবে register করা
6. **Theme & Layout Guide** — layout বানানো ও theme variables ব্যবহার
7. **Events / Hooks Reference** — core কোন event dispatch করে
8. **Packaging ও Versioning** — ZIP বানানো, semantic versioning, update migration
9. **Testing Checklist** — install → activate → deactivate → re-activate → uninstall সবকিছু ঠিকমতো কাজ করছে কিনা
10. **একটি Sample Module** (যেমন FAQ) — reference হিসেবে

### Proposed Technology (Suggestion)

* **Laravel** + **nwidart/laravel-modules** — module structure ও activate/deactivate support
* **Filament** — Admin Panel (প্রতিটি module নিজের Filament resources register করবে)
* **spatie/laravel-permission** — Role & Permission
* **spatie/laravel-medialibrary** — Media Library
* Custom **Module Manager** layer — ZIP install, dependency check, conflict check, lifecycle UI