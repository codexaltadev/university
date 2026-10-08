# UI/UX Design Document
## Part A — Admin Panel (Filament 5)

| বিষয় | তথ্য |
|---|---|
| Document | UI/UX Part A: Admin Panel |
| Version | 0.1 (Draft) |
| Date | 2026-10-08 |
| Status | ✔ Confirmed |
| Based on | BRD v1.7, SRS v1.0, SAD Part A / B, DB-01 থেকে DB-06 |
| পাঠক | UI developer, Filament developer, tester, training team |

### UI/UX Document-এর গঠন

| Part | বিষয় | Status |
|---|---|---|
| **A** | Admin Panel: নীতি, role অনুযায়ী অভিজ্ঞতা, navigation, standard screen, custom component, প্রধান flow-এর wireframe | **এই document** |
| B | Frontend: theme ও design token, page template, microsite layout, module layout, component, responsive ও accessibility — [02-UIUX-PartB-Frontend.md](02-UIUX-PartB-Frontend.md) | Draft |

> Wireframe-গুলো গঠন বোঝানোর জন্য (low-fidelity); রং, font ও চূড়ান্ত চেহারা Filament-এর default theme ও university branding অনুযায়ী হবে।

---

## 1. Design নীতি

| # | নীতি | কী বোঝায় |
|---|---|---|
| U-1 | **Non-technical user প্রথম** | Department-এর একজন content editor ২ ঘণ্টার training-এ news, notice, event publish করতে পারবেন (NFR-USE-04)। Technical শব্দ (slug, morph, relation) UI-তে নয় — "Web address", "Show in" ইত্যাদি |
| U-2 | **যা করতে পারবেন না, তা দেখবেন না** | Permission না থাকলে menu, button, field লুকানো (FR-GEN-82); scope-এর বাইরের data তালিকাতেই আসে না |
| U-3 | **প্রতিটি screen একই রকম** | সব module একই list, form, filter, bulk action, trash, activity pattern (Section 5) — একটি শিখলে সব জানা |
| U-4 | **Context সবসময় দৃশ্যমান** | Entity user সবসময় জানবেন তিনি কোন Faculty / Department / Office-এর জন্য কাজ করছেন (Section 4) |
| U-5 | **Fallback স্পষ্ট** | Menu, header, homepage, statistics কোথা থেকে আসছে ("Engineering Faculty থেকে নেওয়া") তা লেখা থাকে (Section 7.4) |
| U-6 | **ভুল ফেরানো যায়** | Delete → Trash; বড় কাজের আগে confirmation; force delete-এ নাম টাইপ করে নিশ্চিত |
| U-7 | **একই জিনিস একবার** | ব্যক্তি, file, category একবার তৈরি করে picker দিয়ে বারবার ব্যবহার (Person Picker, Media Picker, Taxonomy) |
| U-8 | **ভাষা দুই স্তরে** | Admin interface-এর ভাষা (user নিজে বাছবেন, FR-GEN-85) এবং content-এর ভাষা (form-এর language tab) আলাদা |

---

## 2. User Role অনুযায়ী অভিজ্ঞতা

| Role | Login-এর পর প্রথম screen | প্রধান কাজ | Navigation-এ যা দেখেন |
|---|---|---|---|
| **Super Admin** | System Dashboard | Module / theme install, role, backup, log | সব group |
| **Admin** | Site Dashboard | সব content, main site প্রকাশ ও highlight, settings, menu | System group-এর কিছু অংশ ছাড়া সব |
| **Content Manager** | Content Dashboard | নির্দিষ্ট module-এর content | Content group (permission অনুযায়ী) |
| **Faculty / Department / Program user** | "My Microsite" Dashboard (নিজের entity) | নিজের entity-র content, menu, homepage, statistics, people | Content + "My Microsite" + Academic (নিজের) |
| **Office / Lab user** | Office / Lab Dashboard | নিজের Office / Lab-এর page, service, team, content | Content + "My Microsite" + Office / Lab |

---

## 3. Navigation (Information Architecture)

Sidebar-এর group ও item (SAD Part A Section 7.3)। শুধু active module ও permission থাকা item দেখায়।

```
┌──────────────────────────────┐
│ 🏛  University Admin          │
│ [🔍 Search everything…  ⌘K]  │   ← Filament global search (scope মেনে)
├──────────────────────────────┤
│ 🏠 Dashboard                  │
│                              │
│ ▸ MY MICROSITE   (entity user)│   Section 4
│     Overview                 │
│     Pages                    │
│     Menu                     │
│     Homepage                 │
│     Header & Footer          │
│     Statistics               │
│     People                   │
│                              │
│ ▸ CONTENT                    │
│     News · Events · Notices  │
│     Gallery · Slider         │
│     Documents · FAQ          │
│     Achievements             │
│     Testimonials             │
│     Announcements            │
│     Publications* Research*  │   * Optional module (active হলে)
│     ⭐ Main Site Queue (Admin)│   FR-CNT-H08
│     ⭐ Highlights (Admin)     │   FR-CNT-H06
│                              │
│ ▸ ACADEMIC                   │
│     Faculties · Departments  │
│     Programs · Courses       │
│     Curricula · Sessions     │
│     Faculty Members          │
│     Admission · Fees & Waiver│
│     Academic Calendar        │
│     Campuses                 │
│                              │
│ ▸ PEOPLE                     │
│     Offices · Administration │
│     Leadership · Committees  │
│     Alumni                   │
│                              │
│ ▸ SERVICES                   │
│     Transport · Labs         │
│     Facilities               │
│     Accreditation & Ranking  │
│     International · CSR      │
│     Partners                 │
│                              │
│ ▸ COMMUNICATION              │
│     Inquiries (🔴 3)         │   unread badge
│     Newsletter               │
│                              │
│ ▸ APPEARANCE  (Admin)        │
│     Main Menu · Main Homepage│
│     Header & Footer          │
│     Theme & Design           │
│     Layouts                  │
│                              │
│ ▸ SITE  (Admin)              │
│     Media Library            │
│     Categories & Tags        │
│     SEO & Redirects          │
│     Settings · Languages     │
│     Content Freshness        │   FR-NOTI-15
│                              │
│ ▸ SYSTEM  (Super Admin)      │
│     Modules · Themes         │
│     Users · Roles            │
│     Role Labels              │   Standard Role Catalog (DB-01 §8.4)
│     Activity Log · Backups   │
│     API Tokens               │
└──────────────────────────────┘
```

**Top bar (ডান দিকে):** Context switcher (Section 4) · Language switcher (admin UI) · 🔔 Notification (FR-NOTI-01) · "View site" link · User menu (Profile, 2FA, Logout)।

Media Library সব user দেখেন (নিজের scope অনুযায়ী), তাই entity user-এর জন্য Content group-এ একটি "Media" item-ও দেখায়।

---

## 4. Entity Context (Scoped ও Office / Lab user)

### 4.1 Context Switcher

একজন user একাধিক entity-তে কাজ করতে পারেন (FR-USER-03)। Top bar-এ:

```
┌───────────────────────────────────────────────────────────────────────┐
│ Working in:  [ CSE Department ▾ ]                                     │
│              ┌─────────────────────────────────┐                      │
│              │ ● CSE Department                │                      │
│              │   └ BSc in CSE  (Program)       │  ← descendant-ও      │
│              │   └ MSc in CSE  (Program)       │                      │
│              │ ○ Robotics Lab                  │  ← Lab permission    │
│              └─────────────────────────────────┘                      │
└───────────────────────────────────────────────────────────────────────┘
```

* নির্বাচিত context-ই নতুন content-এর default "Show in" (FR-GEN-51) এবং "My Microsite" কোন entity-র তা ঠিক করে।
* Context কেবল UI-র সুবিধা; নিরাপত্তা scope engine ও policy থেকে (SAD Part B §5) — context বদলে scope-এর বাইরে যাওয়া যায় না।
* Admin-এর জন্য switcher-এ "Main Site" + সব entity।

### 4.2 "My Microsite" Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│ CSE Department — Microsite                          [ View microsite ↗ ]│
├─────────────────────────────────────────────────────────────────────────┤
│ Content freshness                                                       │
│  ✅ News        last update 3 days ago                                  │
│  ⚠️ Notices     95 days — reminder sent (2)            [ Add notice ]   │  FR-NOTI-16
│  ❌ FAQ         no content (required)                   [ Add FAQ ]      │
├────────────────────────────────┬────────────────────────────────────────┤
│ Your microsite uses            │ Recent activity                        │
│  Menu       : own              │  • Rahim edited "Lab Facilities" page  │
│  Header     : Engineering ⤴    │  • You published notice #2231          │
│  Footer     : Main site ⤴      │                                        │
│  Homepage   : own (8 sections) │                                        │
│  Statistics : own (4 items)    │                                        │
│  Template   : Department v2    │                                        │
└────────────────────────────────┴────────────────────────────────────────┘
⤴ = parent / main site থেকে নেওয়া (U-5)
```

---

## 5. Standard Screen Pattern (সব module)

### 5.1 List Screen

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Notices                                                     [ + New notice ]│
├─────────────────────────────────────────────────────────────────────────────┤
│ [ All (1,204) ] [ Active ] [ Scheduled (3) ] [ Inactive ] [ 🗑 Trash (12) ]  │  tabs
├─────────────────────────────────────────────────────────────────────────────┤
│ 🔍 Search title / reference…   Category ▾  Show in ▾  Date ▾  Main site ▾  │  filters
├──┬──────────────────────────────────┬─────────────┬───────────┬──────┬──────┤
│☐ │ Title                            │ Show in     │ Date      │Active│  ⋯   │
├──┼──────────────────────────────────┼─────────────┼───────────┼──────┼──────┤
│☐ │📌 Mid-term exam routine Fall 2026│ CSE · 🌐Main │ 08 Oct 26 │ [●]  │  ⋯   │
│☐ │ Lab closed on Friday   EN · বাং ○│ CSE         │ 07 Oct 26 │ [●]  │  ⋯   │
│☐ │ ⏰ Convocation notice            │ 🌐Main       │ 15 Oct 26 │ [●]  │  ⋯   │
└──┴──────────────────────────────────┴─────────────┴───────────┴──────┴──────┘
  Bulk (selected): [ Activate ] [ Deactivate ] [ Delete ]           ‹ 1 2 3 … ›
```

| উপাদান | নিয়ম | Ref |
|---|---|---|
| Tab | All / Active / Scheduled (ভবিষ্যৎ `published_at`) / Inactive / Trash | FR-GEN-02, FR-GEN-11 |
| Active toggle | Row-এ সরাসরি switch | FR-GEN-04 |
| "Show in" | Related entity chip; 🌐Main = main site-এ প্রকাশিত | FR-CNT-C01, FR-CNT-H07 |
| Language indicator | ● আছে, ○ নেই (প্রতিটি ভাষা) | FR-GEN-43 |
| 📌 / ⏰ | Pinned / Scheduled | FR-NOT-02, FR-GEN-11 |
| Row action (⋯) | View on site, Edit, Duplicate, Activity, Delete | FR-CNT-C10, FR-GEN-83 |
| Trash tab | Restore, (Super Admin) Delete permanently | FR-GEN-02/03 |
| Column toggle, per-page, sort | Filament default | FR-GEN-80 |

### 5.2 Form Screen (Create / Edit)

```
┌───────────────────────────────────────────────────────────────────────────────┐
│ ← Notices / Edit: Mid-term exam routine Fall 2026      [ View ↗ ] [ Save ▾ ]   │
├───────────────────────────────────────────────┬───────────────────────────────┤
│ [ Content ] [ Attachments ] [ SEO ] [ Activity ]│  PUBLISHING                  │
│                                               │  Active          [●]          │
│  Language: [ English ● ] [ বাংলা ○ ]           │  Publish date    [08 Oct 26 ▾]│
│  ┌─────────────────────────────────────────┐  │   (future = scheduled)        │
│  │ Title *                                 │  │  Expires         [ —        ] │
│  │ [ Mid-term exam routine Fall 2026     ] │  │  Pinned          [○]          │
│  │ Reference no.  [ DIU/REG/2026/551     ] │  ├───────────────────────────────┤
│  │ Body                                    │  │  SHOW IN                      │
│  │ [ rich text editor …                  ] │  │  [ CSE Department ✕ ]         │
│  └─────────────────────────────────────────┘  │  [ + Add Faculty / Dept / … ] │
│                                               │                               │
│                                               │  🌐 Main site    [●] (Admin)  │
│                                               ├───────────────────────────────┤
│                                               │  HIGHLIGHT                    │
│                                               │  Main site   [●] #1 until [ ] │
│                                               │  CSE         [○]              │
│                                               ├───────────────────────────────┤
│                                               │  CATEGORY  [ Exam ▾ ]         │
│                                               │  TAGS      [ fall-2026 ✕ ]    │
└───────────────────────────────────────────────┴───────────────────────────────┘
```

| অংশ | নিয়ম | Ref |
|---|---|---|
| বাম: tab | Content (ভাষা tab সহ), module-নির্দিষ্ট tab, SEO, Activity | FR-GEN-40, FR-GEN-70, FR-GEN-83 |
| ডান: sidebar | Publishing (Active, Publish date, Expires), Show in, Main site, Highlight, Category / Tag — সব content module-এ একই জায়গায় | FR-GEN-10/11/13, FR-CNT-C01/C03, FR-CNT-H01/H07 |
| Main site switch | শুধু `PublishMain` permission থাকলে দেখায়; entity user-এর form-এ নেই | FR-CNT-H07 |
| Default ভাষা | বাধ্যতামূলক; অন্য ভাষা ঐচ্ছিক; tab-এ ●/○ | FR-GEN-41 |
| Save ▾ | Save · Save & create another · Save & view | — |
| অসংরক্ষিত পরিবর্তন | Page ছাড়ার আগে সতর্কতা | FR-GEN-84 |
| Web address | Title থেকে automatic; "Edit web address" link-এ খোলে | FR-GEN-30 |

### 5.3 Confirmation ও Feedback

| পরিস্থিতি | UI |
|---|---|
| Save সফল | সবুজ toast "Saved" |
| Delete | Modal: "Move to Trash? Restore করা যাবে।" |
| Force delete (Super Admin) | Modal + record-এর নাম টাইপ করে নিশ্চিত |
| ব্যবহৃত media / parent record delete | তালিকাসহ সতর্কতা (FR-MEDIA-10, FR-GEN-08) |
| Module deactivate বা uninstall | নির্ভরশীল module / data-র তালিকা; uninstall-এ slug টাইপ (FR-MODM-11) |
| Empty state | ব্যাখ্যা + প্রধান button ("এখনো কোনো notice নেই — [ + New notice ]") |
| Error | বোধগম্য ভাষায়, technical trace নয় (NFR-USE-03) |

---

## 6. Custom Form Component (Filament-এ নতুন তৈরি করতে হবে)

Filament-এর built-in component-এর বাইরে, platform-এর reusable component — সব module একই ব্যবহার করবে।

| Component | কাজ | যেখানে ব্যবহার | Ref |
|---|---|---|---|
| **MediaPicker** | Library modal থেকে select বা নতুন upload; single / multiple; alt text | সব image / file field | FR-MEDIA-04 |
| **RelatedToField** ("Show in") | Active target entity search ও chip; scope অনুযায়ী সীমিত | Attachable content | FR-GEN-50/51 |
| **PublishingPanel** | Active, Publish date, Expires, Main site switch | Content form sidebar | FR-GEN-10/11/13, FR-CNT-H07 |
| **HighlightPanel** | Context-প্রতি highlight switch, ক্রম, until | Content form sidebar | FR-CNT-H01/H02 |
| **PersonPicker** | Person source-গুলোতে search + "Add new person" (manual) inline | Assignment, Committee, Speaker, Author | FR-REL-09 |
| **PeopleManager** | Assignable-এর people তালিকা: role (catalog থেকে), ক্রম (drag), মেয়াদ, public | Office, Committee, Department, Leadership | FR-REL-10/11 |
| **TranslatableTabs** | ভাষা tab, ●/○ indicator | সব translatable form | FR-GEN-40/43 |
| **TermSelect** | Vocabulary-ভিত্তিক category / tag; tag টাইপ করে তৈরি | Category / tag field | FR-TAX-04 |
| **FallbackIndicator** | "Inherited from Engineering Faculty" badge ও "Create own" button | Menu, Header, Homepage, Stats, Template | FR-MENU-08 |
| **WebAddressField** | Slug preview ও edit, পুরনো URL redirect সতর্কতা | Frontend record | FR-GEN-30/32 |

---

## 7. প্রধান Flow ও Wireframe

### 7.1 Login ও 2FA

```
┌──────────────────────────────┐      ┌──────────────────────────────┐
│ 🏛  University Admin          │      │ Two-factor code              │
│ Email    [               ]   │ ──►  │ [ _ _ _ _ _ _ ]              │
│ Password [               ]   │      │ [ Use recovery code ]        │
│ [ Sign in ]  Forgot password?│      │ [ Verify ]                   │
└──────────────────────────────┘      └──────────────────────────────┘
৫ বার ভুল → "১৫ মিনিট পর চেষ্টা করুন" (NFR-SEC-03); inactive user → "Account inactive" (AC-USER-01)
```

### 7.2 Department user — নতুন Notice

```
1. Content › Notices › [ + New notice ]
2. Show in = CSE Department (context থেকে default; সরানো যায় না যদি শেষটি হয়)
3. Title, body, attachment (MediaPicker), category
4. Publish date = এখন (বা ভবিষ্যৎ → Scheduled)
5. Save → CSE microsite-এ দেখায়; Main site switch নেই (Admin-এর সিদ্ধান্ত)
6. Admin-এর "Main Site Queue"-এ নতুন হিসেবে দেখা দেয় (FR-CNT-H08)
```

### 7.3 Admin — Main Site Queue ও Highlights

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Main Site Queue — entity content not on main site                           │
│ Type ▾  Entity ▾  Last 30 days ▾                                            │
├──────────────────────────────────────┬─────────────┬──────────┬─────────────┤
│ Notice: Lab closed on Friday         │ CSE         │ 07 Oct   │ [Publish 🌐]│
│ News: ICPC Regional champions        │ CSE, Engg.  │ 06 Oct   │ [Publish 🌐]│
│ Event: AI Workshop                   │ EEE         │ 05 Oct   │ [Publish 🌐]│
└──────────────────────────────────────┴─────────────┴──────────┴─────────────┘
Bulk: [ Publish to main site ]  [ Dismiss ]

Highlights — Main site                                 Context: [ Main site ▾ ]
 ⠿ 1. ICPC Regional champions          until 01 Nov    [ ✕ ]
 ⠿ 2. Convocation 2026                 until —         [ ✕ ]
 (drag ⠿ দিয়ে ক্রম)
```

### 7.4 Menu Builder (fallback সহ)

```
┌───────────────────────────────────────────────────────────────────────────┐
│ Menu — CSE Department          Location: [ Primary ▾ ]                    │
├───────────────────────────────────────────────────────────────────────────┤
│ ℹ This microsite uses Engineering Faculty's Primary menu.                 │  FallbackIndicator
│   [ Create own menu ]  [ Copy Engineering's menu and edit ]  [ Use main ] │  FR-MENU-08/09
└───────────────────────────────────────────────────────────────────────────┘
               ── own menu তৈরির পর ──
┌──────────────────────────────┬────────────────────────────────────────────┐
│ ADD ITEMS                    │ STRUCTURE                                  │
│ ▸ Pages      [search…]       │ ⠿ Home                                     │
│ ▸ Programs   [search…]       │ ⠿ About ▾                                  │
│ ▸ Module pages               │    ⠿ Message from Head                     │
│   News · Notices · Events    │    ⠿ Faculty Members                       │
│ ▸ Categories                 │ ⠿ Programs ▾                               │
│ ▸ File (Media)               │    ⠿ BSc in CSE                            │
│ ▸ Custom link                │    ⠿ MSc in CSE   ⚠ inactive → hidden      │  FR-MENU-10
│ ▸ Heading                    │ ⠿ Notices                                  │
│                              │ [ + ] drag to nest (3 levels)              │
│                              │ Title per language: EN ● বাং ●             │
└──────────────────────────────┴────────────────────────────────────────────┘
```

### 7.5 Homepage Builder

```
┌──────────────────────────────────────────────────────────────────────────┐
│ Homepage — CSE Department                          [ Preview ] [ Save ]  │
├──────────────────────────────────────────────────────────────────────────┤
│ ⠿ Hero slider            source: CSE slides                  [⚙] [👁] [✕]│
│ ⠿ Statistics             set: CSE default (4 items)          [⚙] [👁] [✕]│
│ ⠿ About + Head's message                                     [⚙] [👁] [✕]│
│ ⠿ Latest News            Highlighted first · 6 · Grid        [⚙] [👁] [✕]│
│ ⠿ Notice Board           Latest · 8 · List                   [⚙] [👁] [✕]│
│ ⠿ Upcoming Events        ⚠ Events module inactive — hidden   [⚙] [👁] [✕]│  FR-HOME-09
│ [ + Add section ]                                                        │
└──────────────────────────────────────────────────────────────────────────┘
[⚙] Section settings (drawer): heading (ভাষা অনুযায়ী) · source mode · count ·
     filter · layout variant · background · visible from / until
```

### 7.6 Statistics Editor

```
┌───────────────────────────────────────────────────────────────────────────┐
│ Statistics — Engineering Faculty     Set: [ default ▾ ]                   │
├───┬──────────────────┬──────────────┬─────────────────┬───────────┬───────┤
│ ⠿ │ Label            │ Source       │ Value           │ Display   │ Active│
├───┼──────────────────┼──────────────┼─────────────────┼───────────┼───────┤
│ ⠿ │ Departments      │ Auto ▾       │ 6  (live)       │ 6         │ [●]   │
│ ⠿ │ Programs         │ Auto ▾       │ 18 (live)       │ 18        │ [●]   │
│ ⠿ │ Students         │ Manual ▾     │ [ 8500 ]        │ 8,500+    │ [●]   │
│ ⠿ │ Alumni           │ Auto ▾       │ ⚠ Alumni module inactive     │ [○]   │  FR-STAT-04
└───┴──────────────────┴──────────────┴─────────────────┴───────────┴───────┘
[ + Add item ]   Item edit: label (ভাষা), icon, prefix / suffix, rounding, as-of date
```

### 7.7 Media Library

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ Media Library            [ ⬆ Upload ]  View: [▦ Grid] [≡ List]               │
├──────────────┬───────────────────────────────────────────────┬───────────────┤
│ FOLDERS      │ 🔍 search   Type ▾  Month ▾  Uploaded by ▾    │ DETAILS       │
│ ▸ All        │ Unused only [○]                               │ [ preview ]   │
│ ▸ Shared     │ ┌────┐ ┌────┐ ┌────┐ ┌────┐ ┌────┐            │ convocation…  │
│ ▾ CSE        │ │ 🖼 │ │ 🖼 │ │ 📄 │ │ 🎬 │ │ 🖼 │            │ 1600×900 · 2MB│
│   ▸ Events   │ └────┘ └────┘ └────┘ └────┘ └────┘            │ Title  EN|বাং  │
│   ▸ Notices  │  … drag & drop files here to upload …         │ Alt text *    │
│ [+ Folder]   │                                               │ Caption       │
│              │                                               │ Used in (3) ▾ │  FR-MEDIA-09
│              │                                               │ [Copy URL]    │
│              │                                               │ [Replace]     │
│              │                                               │ [Delete]      │
└──────────────┴───────────────────────────────────────────────┴───────────────┘
Picker mode (form থেকে খুললে): একই screen modal-এ, ডানে [ Select ] button
```

### 7.8 People ও Assignment (Office / Committee)

```
┌────────────────────────────────────────────────────────────────────────────┐
│ Registrar Office › People                                  [ + Add person ]│
├────────────────────────────────────────────────────────────────────────────┤
│ HEAD                                                                       │
│  ⠿ 👤 Dr. A. B. M. Kamal Pasha   "Registrar"     since 2021   public [●]   │
│ TEAM                                                                       │
│  ⠿ 👤 Md. Shihab Uddin  Assistant Administrative Officer      public [●]   │
│  ⠿ 👤 Kamrun Nahar      Administrative Officer   ext. 245 ✎   public [●]   │
│ FORMER                                                                     │
│     👤 … (ends_on অতীত)                                                    │
└────────────────────────────────────────────────────────────────────────────┘
[ + Add person ] →  PersonPicker:
  ┌─────────────────────────────────────────────────────────┐
  │ 🔍 [ Kamal…                    ]                        │
  │  Administration · Faculty Members · Alumni              │
  │   👤 Dr. A. B. M. Kamal Pasha — Deputy Registrar        │
  │ Not found?  [ + Add new person (not in any directory) ] │  manual person
  │ Role [ Team ▾ ] (catalog labels)  Since [ ]  Until [ ]  │
  │ Display role (optional) [            ]  Note [        ] │
  └─────────────────────────────────────────────────────────┘
একই ব্যক্তির তথ্য বদলাতে: নামে click → মূল profile (Administration ইত্যাদি) খোলে
```

### 7.9 Module Manager (Super Admin)

```
┌───────────────────────────────────────────────────────────────────────────────┐
│ Modules                                              [ ⬆ Install from ZIP ]   │
│ [ All ] [ Active ] [ Inactive ] [ Core ] [ Updates ]                          │
├──────────────────┬─────────┬──────────┬────────────────────────┬──────────────┤
│ Module           │ Version │ Status   │ Depends on / Used by   │              │
├──────────────────┼─────────┼──────────┼────────────────────────┼──────────────┤
│ 🔒 Menu (core)   │ 1.0.0   │ Core     │ —                      │ Details      │
│ Department       │ 1.2.0   │ ● Active │ ← Faculty · → Program  │ [Deactivate] │
│ Research         │ 1.0.0   │ ○ Off    │ (optional module)      │ [Activate]   │
└──────────────────┴─────────┴──────────┴────────────────────────┴──────────────┘

Install from ZIP → upload → validation checklist (live):
  ✅ Structure   ✅ Security   ✅ Manifest   ✅ Core 1.x compatible
  ❌ Conflict: table "news_posts" already exists (module "News")
  → [ Cancel ]    (কিছুই install হয়নি — FR-MODM-04)

Deactivate Faculty → ⛔ "Department, Program, Course এর উপর নির্ভর করে। আগে সেগুলো বন্ধ করুন।"
```

### 7.10 Role ও User Scope (Shield + Scope)

```
User: rahim@diu.edu.bd
 ROLES   [ Department Editor ✕ ]  [ + ]                 ← Shield role
 SCOPE   [ CSE Department ✕ ]  [ + Faculty / Department / Program ]   ← Scope (P-03)
 Effective: Department Editor permissions on CSE + BSc in CSE + MSc in CSE
           [ View effective permissions ]                ← FR-ROLE-09

Role editor (Shield):
 [ Resources ] [ Pages ] [ Widgets ] [ Custom ] [ Entity permissions ]
 Notices      ☑ View ☑ Create ☑ Update ☑ Delete ☐ Publish to main site
 Offices / Labs: Registrar Office ☑ Manage ☑ Content ☐ Menu      ← POC-3
 (inactive module-এর permission ধূসর — POC-4)
```

### 7.11 Theme ও Design

```
┌───────────────────────────────┬─────────────────────────────────────────────┐
│ Theme & Design                │  LIVE PREVIEW  [ Desktop ▾ ] [ EN ▾ ]       │
│ Theme   [ University v1 ▾ ]   │  ┌───────────────────────────────────────┐  │
│ COLORS                        │  │  (homepage preview, শুধু admin দেখে)  │  │
│ Primary   [■ #0B5ED7]         │  │                                       │  │
│ Secondary [■ #1B2A4A]         │  │                                       │  │
│ Accent    [■ #F5A623]  ⚠ low  │  │                                       │  │
│           contrast on white   │  └───────────────────────────────────────┘  │  FR-THEME-04
│ TYPOGRAPHY                    │                                             │
│ Heading [ Poppins ▾ ]         │                                             │
│ Body    [ Inter ▾ ]           │                                             │
│ বাংলা   [ Hind Siliguri ▾ ]    │                                             │
│ BRANDING  Logo · Dark logo ·  │                                             │
│           Favicon             │                                             │
│ [ Reset ]  [ Save & publish ] │                                             │
└───────────────────────────────┴─────────────────────────────────────────────┘
Layouts screen: module × page → [ Grid ▾ ] (News listing, Events listing, …)
```

### 7.12 Inquiry Inbox

```
┌──────────────────────────────┬──────────────────────────────────────────────┐
│ Inquiries   New ▾  Type ▾    │ Admission query — Rakib Hasan                │
│ ● Admission query    2h  CSE │ rakib@…  ·  from: /department/cse             │
│ ○ Transport route    1d  —   │ Status [ In progress ▾ ]  Assign [ Me ▾ ]     │
│ ○ Transcript         3d  Reg.│──────────────────────────────────────────────│
│                              │ "আমি BSc in CSE-তে ভর্তি হতে চাই …"          │
│                              │ ── Internal note (only staff) ──             │
│                              │ [ Reply by email … ]          [ Send ]        │
└──────────────────────────────┴──────────────────────────────────────────────┘
```

### 7.13 Content Freshness Report (Admin)

```
              News   Notices  Events  FAQ   Slider
Engineering    ✅      ✅       ⚠ 102d  ✅     ✅
CSE            ✅      ⚠ 95d    ✅      ❌     ⚠ 1 old
EEE            ⚠ 130d  ✅       ✅      ✅     ✅
Registrar Off. —       ✅       —       ✅     —
[ Export ]  Cell click → সেই entity-র সেই module-এর list
```

---

## 8. Dashboard Widget (Role অনুযায়ী)

| Widget | Super Admin | Admin | Entity / Office / Lab user | Ref |
|---|---|---|---|---|
| System health (শেষ backup, failed job, module update) | ✔ | | | FR-BKP-06 |
| Main Site Queue (নতুন entity content) | | ✔ | | FR-CNT-H08 |
| Content Freshness সারাংশ | ✔ | ✔ | নিজের entity | FR-NOTI-15/16 |
| New inquiries | ✔ | ✔ | নিজের entity | FR-INQ-06 |
| Recently published (আমার / সবার) | | ✔ | ✔ | — |
| Scheduled content (আসন্ন) | | ✔ | ✔ | FR-GEN-11 |
| Quick actions (New notice, New news, Upload media) | | ✔ | ✔ | — |
| Admission result status | | Admission Office | | FR-ADM-08 |

---

## 9. Multi-language (Admin)

| বিষয় | নিয়ম |
|---|---|
| Admin interface ভাষা | User profile থেকে (English / বাংলা); Filament-এর translation + নিজস্ব `lang/` string |
| Content-এর ভাষা | Form-এ TranslatableTabs; default ভাষা আগে ও বাধ্যতামূলক |
| অনুবাদ অসম্পূর্ণ | List-এ ●/○; "Missing বাংলা" filter |
| তারিখ ও সংখ্যা | Admin-এ সবসময় English অঙ্ক ও `DD Mon YYYY` (data entry-তে বিভ্রান্তি এড়াতে); frontend ভাষা অনুযায়ী |

---

## 10. Accessibility ও Responsive (Admin)

* Keyboard দিয়ে সব কাজ (Filament default + custom component-এ focus order)।
* Color ছাড়াও icon / text দিয়ে অবস্থা (✅ ⚠ ❌, "Active" label)।
* Tablet-এ (≥ 768px) পুরো ব্যবহারযোগ্য; mobile-এ list, quick edit ও notice publish (NFR-USE-01)।
* Alt text ছাড়া image save-এ সতর্কতা (FR-GEN-61)।

---

## 11. Admin Panel Branding (UXQ-01 confirmed)

**সিদ্ধান্ত:** Admin Panel default-এ একটি **নিরপেক্ষ (neutral) theme** — সব university-তে একই চেহারা ও ব্যবহার-পদ্ধতি। তবে প্রতিটি university নিজের branding বসাতে পারবে। Admin branding website-এর theme / branding থেকে **আলাদা** (website theme বদলালে admin বদলায় না)।

| Setting | Default (neutral) | University বদলাতে পারবে | Filament-এ |
|---|---|---|---|
| Panel name | "University Admin" | ✔ (multi-language) | `brandName()` |
| Logo (light ও dark mode) | Neutral platform logo | ✔ (Media Library থেকে) | `brandLogo()`, `darkModeBrandLogo()` |
| Logo height | 2rem | ✔ | `brandLogoHeight()` |
| Favicon | Neutral | ✔ | `favicon()` |
| Primary color | Neutral blue-gray | ✔ (color picker; contrast সতর্কতা) | `colors(['primary' => …])` |
| Secondary / accent (danger, warning, success ছাড়া) | Filament default | ✔ | `colors()` |
| Font | Filament default (Inter) + বাংলা fallback | ✔ (তালিকা থেকে) | `font()` |
| Login page background / image | নেই | ✔ | Custom login view |
| Login page message | নেই | ✔ (multi-language) | Custom login view |
| "Website branding ব্যবহার করুন" shortcut | বন্ধ | ✔ — এক click-এ website-এর logo ও primary color admin-এ কপি | — |
| Layout, navigation, component আকার | স্থির (neutral) | ✘ — সব university-তে একই, যাতে documentation ও training এক থাকে | — |

* কোথায়: **Site › Settings › Admin Branding** (Admin ও Super Admin; Super Admin "Reset to neutral" করতে পারবেন)।
* বাস্তবায়ন: `AdminPanelProvider` boot-এ `core_settings` (group `admin_branding`) পড়ে Filament panel configure করে; settings cache থেকে (SAD Part B §10)।
* SRS সংশোধন: FR-SET-13 যোগ (SRS Part 1, C-05)।

## 12. খোলা প্রশ্ন

| # | প্রশ্ন | প্রস্তাব |
|---|---|---|
| UXQ-01 *(✔ সমাধান)* | Admin Panel-এর branding? | Default neutral theme; university নিজের logo, color, favicon, font, login page বদলাতে পারবে; layout স্থির (Section 11) |
| UXQ-02 *(✔ প্রস্তাব গৃহীত)* | Entity user-দের জন্য "Main site-এ দেখানোর অনুরোধ" button রাখা হবে? | হ্যাঁ — form-এর Publishing panel-এ "Request main site" switch; Admin-এর Main Site Queue-তে "Requested" চিহ্ন ও আগে দেখায়; সিদ্ধান্ত Admin-এরই (Q-26)। SRS FR-CNT-H09 |
