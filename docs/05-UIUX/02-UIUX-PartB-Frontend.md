# UI/UX Design Document
## Part B — Public Website (Frontend)

| বিষয় | তথ্য |
|---|---|
| Document | UI/UX Part B: Frontend |
| Version | 0.3 — v0.1 approved 2026-10-08; v0.2: microsite URL hierarchy অনুযায়ী (§3, FR-GEN-34); v0.3: module UI class contract (§9.1, Phase 4.1) |
| Date | 2026-10-08 |
| Status | ✔ Approved — prototype v3 সহ (2026-10-08); v0.2 (URL) approved 2026-10-10 |
| Based on | BRD v1.7, SRS v1.0, SAD Part B §6 (Microsite), §8 (Theme Engine), UI/UX Part A |
| পাঠক | Theme developer, frontend developer, designer, tester |

> এই document **default theme**-এর গঠন ও নিয়ম ঠিক করে। নতুন theme নিজের চেহারা দিতে পারবে, কিন্তু Section 9-এর contract (view, slot, CSS variable) মানতে হবে। Wireframe low-fidelity।

---

## 1. Design নীতি

| # | নীতি | কী বোঝায় | Ref |
|---|---|---|---|
| F-1 | **তথ্য খুঁজে পাওয়া প্রথম** | Visitor (ছাত্র, অভিভাবক, গবেষক) ৩ click-এর মধ্যে Program, Admission, Notice, Contact পাবেন | BRD §5.1 |
| F-2 | **দ্রুত** | Cache সহ TTFB < ৫০০ms, Lighthouse Performance ≥ ৮০, image lazy + WebP | NFR-PERF-01/02/05 |
| F-3 | **সবার জন্য** | WCAG 2.1 AA: contrast, keyboard, alt text, semantic HTML | NFR-USE-02 |
| F-4 | **দুই ভাষা সমান** | বাংলা ও English দুই ভাষাতেই layout ভাঙবে না; বাংলার নিজস্ব font ও line-height | BR-LANG, FR-THEME-05 |
| F-5 | **Microsite পরিচিতি** | Visitor সবসময় বুঝবেন তিনি কোন Faculty / Department / Office-এ আছেন, এবং main site-এ ফেরার পথ | BR-MS |
| F-6 | **শুধু token** | কোনো hardcoded রং / font নয়; সব theme-এর CSS variable থেকে | FR-GEN-101 |
| F-7 | **Module না থাকলেও ভাঙে না** | কোনো module inactive হলে তার section / link নিঃশব্দে লুকায় | FR-HOME-09, NFR-REL-04 |

---

## 2. Design Token (CSS Variable)

Theme Management (C-06)-এর settings থেকে তৈরি হয় (SAD Part B §8.4)। সব layout শুধু এগুলো ব্যবহার করবে।

### 2.1 Color

| Token | ব্যবহার | Default (neutral) |
|---|---|---|
| `--color-primary` | Button, link, active state | **DIU: `#034EA2`** (DIU blue) |
| `--color-primary-contrast` | Primary-র উপর text (automatic: সাদা / কালো) | সাদা |
| `--color-secondary` | Utility bar, footer, dark section, gradient-এর শেষ | **DIU: `#002652`** (deep navy) |
| `--color-accent` | Primary CTA ("Apply now"), status dot, highlight badge | **DIU: `#39B54A`** (green) — এর উপর text গাঢ় navy (`#002652`); সাদা background-এ ছোট text হিসেবে নয় |
| `--gradient-primary` | Primary button, impact band, CTA card | `linear-gradient(90deg, #034EA2, #002652)` |
| `--color-bg`, `--color-surface` | Page ও section background | `#FFFFFF`, `#F3F4F9` (DIU) |
| `--color-text`, `--color-text-muted` | Body ও সহায়ক text | `#1F2328`, `#5B6470` |
| `--color-border` | Divider, card border | `#E3E6EA` |
| `--color-success`, `--color-warning`, `--color-danger` | Notice "Urgent", form error ইত্যাদি | স্থির |

Contrast: `--color-text` ও `--color-primary-contrast` সবসময় AA (৪.৫:১); admin color বাছার সময় সতর্কতা (FR-THEME-04)।

### 2.2 Typography

| Token | English default | বাংলা default |
|---|---|---|
| `--font-heading` | Montserrat 700–800 (DIU live site; admin বদলাতে পারবেন) | Hind Siliguri |
| `--font-body` | Manrope (DIU live site) | Hind Siliguri / Noto Sans Bengali |
| `--line-height-body` | 1.6 | **1.8** (বাংলা যুক্তাক্ষর ও মাত্রা) |
| Type scale | 14 / 16 / 18 / 20 / 24 / 30 / 36 / 48 px (`--text-xs` … `--text-5xl`) | একই, তবে heading-এ letter-spacing 0 |

বাংলা font `:lang(bn)` selector দিয়ে প্রযোজ্য (SAD Part B §8.4)। Font Google Fonts থেকে `font-display: swap`, subset।

### 2.3 Spacing, Radius, Shadow, Breakpoint

| Token | মান |
|---|---|
| `--space-1` … `--space-12` | 4px ভিত্তিক (4, 8, 12, 16, 24, 32, 48, 64 …) |
| `--radius-sm / md / lg` | 4 / 8 / 16px (theme setting) |
| `--shadow-card` | হালকা এক স্তর |
| `--container-max` | 1280px |
| Breakpoint | `sm` 640 · `md` 768 · `lg` 1024 · `xl` 1280 |

---

## 3. Site Map ও URL

```
/{locale}/                                       Main homepage
/{locale}/news, /notices, /events, /gallery …    Main listing (main site-এ প্রকাশিত — Q-26)
/{locale}/news/{slug}                            Detail
/{locale}/faculties, /faculties/{faculty}                    Faculty listing / microsite      (/faculties/fsit)
/{locale}/departments, /{faculty}/{department}               Department listing / microsite   (/fsit/cse)
/{locale}/programs, /{faculty}/{department}/{program}        Program listing / microsite      (/fsit/cse/bsc-in-cse)
/{locale}/labs, /{faculty}/{department}/lab/{lab}            Lab listing / microsite          (/fsit/cse/lab/robotics; Department ছাড়া /lab/{lab})
/{locale}/offices, /office/{office}                          Office listing / microsite       (/office/registrar)
/{locale}/{faculty}/{department}/news                        Microsite-এর ভেতরের listing (entity-র সব content)
/{locale}/{faculty}/{department}/{page-slug}                 Microsite page                   (/fsit/cse/lab-facilities)
/{locale}/admission, /tuition-fees, /waiver-calculator, /scholarships, /academic-calendar
/{locale}/faculty-members, /administration, /leadership, /committees, /alumni
/{locale}/transport, /facilities, /rankings, /international/partners, /csr, /partners
/{locale}/faq, /downloads, /search, /contact
```

* `{locale}` default ভাষার জন্য ঐচ্ছিক (FR-GEN-33)।
* URL segment (যেমন `/administration`) module settings-এ বদলানো যায় (FR-ADMN-04)।
* প্রতিটি page-এ breadcrumb ও `hreflang` (NFR-SEO-02)। Microsite-এ breadcrumb hierarchy অনুযায়ী: Home › FSIT › CSE › BSc in CSE (প্রতিটি নিজের microsite-এ link)।
* *(v0.2, 2026-10-10)* Microsite URL hierarchy অনুযায়ী (FR-GEN-34 থেকে 36): `/fsit` একা → `/faculties/fsit`; সরালে বা slug বদলালে পুরনো URL 301; DIU-র পুরনো URL (`/department/cse`, `/department/bba/program/…`) 301; সংরক্ষিত শব্দ ও slug সংঘর্ষ save-এ আটকানো।

---

## 4. Global Layout

### 4.1 Main Site

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ TOP BAR  ☎ 09617901212  ✉ info@…   Quick: Student Portal · Library · Webmail │  FR-HF-02
│                                              🔍   EN | বাং   f in ▶           │
├──────────────────────────────────────────────────────────────────────────────┤
│ ANNOUNCEMENT BAR  "Spring 2027 admission going on — Apply now →"   [✕]       │  FR-ANW (bar)
├──────────────────────────────────────────────────────────────────────────────┤
│ [LOGO]  University Name        About▾ Academics▾ Admission▾ Research▾ Campus▾│  Primary menu
│                                Notices  Contact               [ Apply Now ]  │  (mega menu)
├──────────────────────────────────────────────────────────────────────────────┤
│ NOTICE TICKER ▸ Mid-term exam routine … ▸ Convocation notice …               │  FR-NOT-06
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│                         PAGE CONTENT (template)                              │
│                                                                              │
├──────────────────────────────────────────────────────────────────────────────┤
│ FOOTER  [Logo + about]  [Quick Links]  [Academics]  [Contact + Map]          │  FR-HF-03
│         [Newsletter: email ____ Subscribe]                [f in ▶ X]         │
│ © {year} University Name · Privacy · Sitemap                                 │
└──────────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Microsite (Faculty / Department / Program / Office / Lab)

Main site-এর header-এর নিচে একটি **microsite bar** — visitor জানেন কোথায় আছেন (F-5)।

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ TOP BAR (main site-এর, ছোট)                          ← University home       │
├──────────────────────────────────────────────────────────────────────────────┤
│ [Univ. logo] │ Department of Computer Science & Engineering                  │  entity নাম + ঐচ্ছিক entity logo
│              │ Faculty of Science & IT ›                                     │  parent link
├──────────────────────────────────────────────────────────────────────────────┤
│ Home  About▾  Programs▾  Faculty Members  Notices  News  Labs▾  Contact      │  Microsite menu
│                                                                              │  (own → parent → main — fallback)
├──────────────────────────────────────────────────────────────────────────────┤
│ Home › Faculty of Science & IT › CSE › Notices                               │  breadcrumb
├──────────────────────────────────────────────────────────────────────────────┤
│                         PAGE CONTENT                                         │
├──────────────────────────────────────────────────────────────────────────────┤
│ FOOTER (entity-র নিজস্ব বা fallback)                                          │
└──────────────────────────────────────────────────────────────────────────────┘
```

* Header, menu, footer, template — সবই fallback resolver থেকে (SAD Part B §6.2)।
* Entity logo ও banner না থাকলে university logo ও default banner (BR-MS-06)।

### 4.3 Mobile

```
┌────────────────────────┐        ┌────────────────────────┐
│ [LOGO]     🔍  ☰       │  ☰ →   │ ✕          EN | বাং    │
├────────────────────────┤        │ About               ›  │
│ CSE Department ▾       │ ← microsite  Academics      ›  │
├────────────────────────┤        │ Admission           ›  │
│ content…               │        │ Notices                │
│                        │        │ Contact                │
│                        │        │ [ Apply Now ]          │
│ [ Apply Now ] (sticky) │        │ ☎  ✉  f in ▶           │
└────────────────────────┘        └────────────────────────┘
Off-canvas menu, nested level slide-in; mega menu → accordion
```

---

## 5. Page Template

### 5.1 Main Homepage (default section — FR-HOME-12)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ HERO SLIDER  [ image ]  "Shape your future"  [Explore Programs] [Apply]  │  FR-SLD
│              ● ○ ○                                                       │
├──────────────────────────────────────────────────────────────────────────┤
│ STATISTICS   22,000+ Students │ 64 Programs │ 350 Faculty │ 700+ Partners │  C-20 (counter animation)
├──────────────────────────────────────────────────────────────────────────┤
│ PROGRAMS     [Undergraduate] [Graduate] [Diploma] [PG Diploma]  ← level tab│  FR-PROG-16
│   ┌────┐ ┌────┐ ┌────┐ ┌────┐                         View all →         │
├──────────────────────────────────────────────────────────────────────────┤
│ LATEST NEWS (highlighted first)        │ NOTICE BOARD                    │
│ ┌──────────┐ ┌──────┐ ┌──────┐        │ 📌 Mid-term routine   08 Oct    │
│ │ featured │ │      │ │      │        │    Convocation notice 07 Oct    │
│ └──────────┘ └──────┘ └──────┘        │    View all notices →           │
├──────────────────────────────────────────────────────────────────────────┤
│ UPCOMING EVENTS   [12 OCT] AI Workshop · [20 OCT] Career Fair  View all →│
├──────────────────────────────────────────────────────────────────────────┤
│ MESSAGE FROM VICE CHANCELLOR  [photo]  "…"   Read more →                 │  FR-LEAD-06
├──────────────────────────────────────────────────────────────────────────┤
│ TESTIMONIALS (slider)                                                    │
├──────────────────────────────────────────────────────────────────────────┤
│ PARTNERS & RANKINGS  [logo] [logo] [logo] [logo] →                       │
└──────────────────────────────────────────────────────────────────────────┘
সব section Homepage Builder দিয়ে সাজানো / বাদ দেওয়া যায়
```

### 5.2 Microsite Homepage (entity ধরন অনুযায়ী default)

| Entity | Default section (ক্রমে) | Ref |
|---|---|---|
| Faculty | Hero · Statistics · Overview · Dean's message · Departments · Programs · News · Events · Notices | FR-FAC-06 |
| Department | Hero · Statistics · About · Head's message · Programs · Faculty Members · News · Events · Notices · Achievements | FR-DEPT-07 |
| Program | Hero (Apply) · Statistics · Overview · Majors · Curriculum · Tuition · Eligibility · Career · Facilitators · FAQ · News | FR-PROG-13 |
| Office | Overview · Statistics · Head's message · Services · Team · Notices · Documents · Contact | FR-OFF-10 |
| Lab | Hero · About · Statistics · Scope · Equipment · Projects · Products · News · Gallery · Committee · Partners · Contact | FR-LAB-06 |

### 5.3 Program Page (সবচেয়ে বিস্তারিত page)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ HERO  BSc in Computer Science & Engineering                              │
│       Undergraduate · 4 Years · 148 Credits · Day        [ Apply Now ]   │
│       Next intake: 15 Jan 2027  ⏳ 98 days                [ Brochure ⬇ ] │
├──────────────────────────────────────────────────────────────────────────┤
│ 18 Majors │ 45 Faculty │ 2,000+ Alumni │ 30 Industry Experts             │  Stat Set
├──────────────────────────────────────────────────────────────────────────┤
│ [Overview] [Majors] [Curriculum] [Tuition & Waiver] [Eligibility]        │  sticky tab /
│ [Career] [Faculty] [FAQ]                                                 │  anchor nav
├──────────────────────────────────────────────────────────────────────────┤
│ Curriculum   Semester 1 ▾  CSE-112 Structured Programming  3 cr          │  FR-CURR-05
│              Semester 2 ▸  …                                             │
│ Tuition      Local | International    Admission fee …  Total ৳ …         │  FR-FEE-04
│              [ Calculate your waiver → ]                                 │
│ Eligibility  General | English-medium                                    │
└──────────────────────────────────────────────────────────────────────────┘
Tab-গুলো extension point থেকে আসে (FR-PROG-18) — Tuition module inactive হলে tab নেই
```

### 5.4 Listing Page (News — layout বদলানো যায়)

```
News                                    [ Grid ▦ ]  (admin-নির্বাচিত layout)
Category ▾   Year ▾   🔍                                   (FR-CNT-C05)
┌────────┐ ┌────────┐ ┌────────┐
│ image  │ │ image  │ │ image  │     Grid: ৩ column (desktop) → ২ (tablet) → ১ (mobile)
│ title  │ │ title  │ │ title  │     List: thumbnail বাম + title / excerpt / date
│ 08 Oct │ │ 07 Oct │ │ 05 Oct │     Card: বড় image + overlay title
└────────┘ └────────┘ └────────┘     Magazine: ১টি বড় + পাশে ৪টি ছোট
            ‹ 1 2 3 … ›
```

### 5.5 Detail Page (News / Notice / Event)

```
Home › News › ICPC Regional champions
┌───────────────────────────────────────────────┬──────────────────────────┐
│ ICPC Regional champions                       │ RELATED                  │
│ 08 Oct 2026 · CSE · Achievement               │  • …                     │
│ [ featured image ]                            │  • …                     │
│ body …                                        │ CATEGORIES               │
│ [ gallery ▦▦▦ → lightbox ]                    │  Achievement (12)        │
│ Attachments: 📄 Result.pdf  ⬇                 │                          │
│ Share: f  in  X  ✉  🖨                         │                          │
└───────────────────────────────────────────────┴──────────────────────────┘
Notice: 📌 Urgent badge, reference no, attachment-এর inline PDF preview (FR-NOT-05)
Event: date badge, time, venue map, speakers (person card), [ Add to calendar ] [ Register ]
```

### 5.6 Interactive Tool Page

| Page | গঠন | Ref |
|---|---|---|
| **Waiver Calculator** | ধাপভিত্তিক form (Level → Program → Fee category → Board / Gender → SSC / HSC GPA বা CGPA → waiver category) → ফলাফল card: waiver %, বার্তা, waiver-পরবর্তী আনুমানিক মোট, disclaimer, [Print] | FR-FEE-06/08 |
| **Admission Result** | Session ▾, Application serial ____, captcha, [Check] → Selected / Waiting / Not selected বার্তা card (রং + icon + text) | FR-ADM-06/07 |
| **Transport Route Finder** | "আমার stop" search (autocomplete) + Service ▾ + দিন → route card: stop timeline, to-campus / from-campus সময়; map toggle | FR-TRN-05/06 |
| **Academic Calendar** | Session ▾ Audience ▾; view: Table / Month / Timeline; [PDF ⬇] [Add to Google Calendar] | FR-CAL-04/06 |
| **Global Search** | Search box → module অনুযায়ী grouped result (Programs 3, News 12, Notices 5 …), module filter chip, highlight | FR-SRCH-02 |
| **Directory** (Faculty Members, Administration, Alumni) | Filter sidebar (Department, designation / category, Campus) + person card grid; card-এ ছবি, নাম, পদবি, email (সুরক্ষিত) | FR-FM-03, FR-ADMN-04, FR-ALUM-02 |

### 5.7 অন্যান্য

* **Contact:** ঠিকানা (campus অনুযায়ী tab), map, phone / email, Office directory link, inquiry form (type ▾) (FR-INQ-01)।
* **404:** "Page not found" + search box + জনপ্রিয় link (Admission, Programs, Notices) — ভাষা অনুযায়ী।
* **Maintenance:** university logo, বার্তা (settings থেকে), সম্ভাব্য সময়।

---

## 6. Component Inventory (Default Theme)

| Component | কোথায় | Accessibility নোট |
|---|---|---|
| Header, Top bar, Mega menu, Off-canvas menu | সব page | `nav` landmark, `aria-expanded`, Esc-এ বন্ধ |
| Microsite bar | Microsite | Entity নাম `h1` নয় (page title `h1`) |
| Breadcrumb | সব inner page | `nav aria-label="Breadcrumb"` + JSON-LD |
| Hero slider | Homepage, microsite | Pause button, autoplay বন্ধের option, slide-এ alt |
| Stat counter | Statistics section | Animation `prefers-reduced-motion`-এ বন্ধ; সংখ্যা text-এ |
| News card, Event card (date badge), Notice row, Program card, Person card, Partner logo, Testimonial card | Listing ও section | পুরো card একটি link; heading level ঠিক রাখা |
| Gallery masonry + Lightbox | Gallery, detail | Keyboard ←/→/Esc, focus trap |
| Accordion | FAQ, curriculum | `button` + `aria-controls` |
| Tabs | Program page, calendar | Arrow key navigation |
| Filter bar, Pagination | Listing | Filter পরিবর্তনে result count ঘোষণা (`aria-live`) |
| Language switcher | Top bar | `lang` ও `hreflang` সহ link |
| Search box (instant) | Header | Combobox pattern |
| Popup (Announcement) | Configured page | Focus trap, Esc, frequency cookie (FR-ANW-04) |
| Notice ticker | Header নিচে | Pause on hover / focus |
| Countdown | Homepage, Program | সময় text-এও |
| Announcement bar | Top | Dismiss button |
| Attachment list, PDF preview, Flipbook viewer | Notice, Documents | File type ও size লেখা |
| Share buttons | Detail page | Text label সহ |
| Newsletter form, Inquiry form | Footer, Contact | Label, error message field-এর সাথে যুক্ত |
| Empty state | Listing | "এখনো কোনো notice নেই" |

---

## 7. Responsive নিয়ম

| Breakpoint | আচরণ |
|---|---|
| < 768 (mobile) | একক column; off-canvas menu; sticky "Apply Now" (Program, Admission); table → card / horizontal scroll; slider image mobile সংস্করণ (FR-SLD-01) |
| 768–1023 (tablet) | Listing ২ column; sidebar নিচে চলে যায় |
| ≥ 1024 (desktop) | Mega menu; listing ৩–৪ column; detail-এ sidebar |
| সব | Touch target ≥ 44px; font ছোট হয় না ১৬px-এর নিচে (body) |

---

## 8. Accessibility, Performance ও SEO Checklist

| এলাকা | নিয়ম | Ref |
|---|---|---|
| Semantic | `header`, `nav`, `main`, `footer`, প্রতি page-এ একটি `h1`, heading ক্রম | NFR-USE-02 |
| Keyboard | Skip-to-content link; সব interactive element focusable, দৃশ্যমান focus ring (`--color-primary`) | NFR-USE-02 |
| Contrast | AA (Section 2.1) | FR-THEME-04 |
| Image | Alt text (Media Library থেকে); decorative হলে খালি alt | FR-GEN-61 |
| Motion | `prefers-reduced-motion` সম্মান | — |
| Language | `<html lang>` ও mixed text-এ `lang` attribute | BR-LANG |
| Image performance | `srcset` (thumbnail / medium / large / WebP), lazy loading, width / height দেওয়া (layout shift নেই) | NFR-PERF-05 |
| Asset | Theme CSS / JS Vite build, critical CSS inline, font subset + swap | NFR-PERF-02 |
| SEO | Meta, OG, canonical, hreflang, JSON-LD (Organization, CollegeOrUniversity, Event, NewsArticle, BreadcrumbList, FAQPage) | NFR-SEO-03 |

---

## 9. Theme Developer Contract

নতুন theme বানাতে হলে যা দিতে হবে (SAD Part B §8.1):

| প্রয়োজন | বিস্তারিত |
|---|---|
| `theme.json` | slug, version, `requires_core`, supported layout তালিকা, menu location, header / footer variant, microsite template, design token default |
| Base layout | `layouts/app.blade.php`, `layouts/microsite.blade.php` — নির্দিষ্ট **slot** সহ: `topbar`, `announcement`, `header`, `microsite-bar`, `breadcrumb`, `content`, `sidebar`, `footer`, `popup`; `@livewireStyles` / `@livewireScripts` বাধ্যতামূলক |
| Livewire view override (ঐচ্ছিক) | Module-এর Livewire component (waiver calculator, filter, search, form)-এর শুধু Blade view theme বদলাতে পারে: `views/modules/{slug}/livewire/...`; logic module-এ (SAD Part B §8.6) |
| Page template | `default`, `full-width`, `with-sidebar`, `landing` (FR-PAGE-04) |
| Header / footer variant | ন্যূনতম একটি করে |
| Token ব্যবহার | Section 2-এর সব CSS variable ব্যবহার; hardcoded রং / font নিষেধ (CI lint) |
| Module override (ঐচ্ছিক) | `views/modules/{slug}/...` — না দিলে module-এর default |
| Accessibility | Section 8 পূরণ; theme install-এর আগে automated check (axe) staging-এ |
| Screenshot | `screenshot.png` (Theme Manager-এ দেখানোর জন্য) |
| Module UI contract | Section 9.1-এর সব class style করা |

### 9.1 Module UI class contract (v0.3, Phase 4.1)

Module-এর website view theme জানে না, তাই সে শুধু এই class ও token utility (`bg-primary`, `text-muted`, `font-heading`, `rounded-lg` …) ব্যবহার করে; প্রতিটি theme এগুলোকে নিজের চেহারা দেয়। নতুন নাম লাগলে আগে এখানে যোগ করতে হবে।

| Class | কাজ |
|---|---|
| `btn`, `btn--primary`, `btn--secondary`, `btn--outline`, `btn--sm` | Link / button (touch target ≥ 44px) |
| `badge`, `badge--success`, `badge--muted`, `badge--warning`, `badge--danger` | অবস্থা (Open / Closed, New, Archived) |
| `card` | সাধারণ box |
| `prose`, `lead` | Editor-এর rich text; ভূমিকা বাক্য |
| `table` | Data table |
| `accordion__item`, `accordion__title`, `accordion__body` | Accordion (FAQ, scholarship) |
| `empty-state`, `form-error`, `no-print`, `sr-only` | খালি তালিকা, field error, print-এ লুকানো, শুধু screen reader |
| `page`, `page__title`, `page-grid`, `home-section` (`--alt`, `--dark`, `__title`, `__sub`) | Page ও homepage section-এর কাঠামো |

Default theme-এর test (`tests/Feature/Theme/DefaultThemeAssetsTest.php`) যাচাই করে যে build-এ প্রতিটি class আছে।

---

## 10. খোলা প্রশ্ন

| # | প্রশ্ন | প্রস্তাব |
|---|---|---|
| UXQ-03 *(✔ সমাধান, সংশোধিত)* | Default theme-এর রং ও style? | **DIU live site (daffodilvarsity.edu.bd, ২০২৬)** থেকে: Primary `#034EA2`, Navy `#002652`, Green `#39B54A`, Surface `#F3F4F9`; font Montserrat + Manrope; blue→navy gradient button। (প্রথমে পুরনো `diu` project-এর template রং নেওয়া হয়েছিল — সেটি বর্তমান branding নয়, বাদ।) Style: বড় photo hero, quick-link tile, story-ভিত্তিক section, প্রচুর whitespace। BRAC University site (bracu.ac.bd) Cloudflare-এর কারণে স্বয়ংক্রিয়ভাবে দেখা যায়নি |
| UXQ-04 *(✔ সমাধান)* | Clickable HTML prototype development-এর আগে? | হ্যাঁ — প্রথম সংস্করণ তৈরি: [University Website Prototype](https://claude.ai/artifact/LYk8PswwPuktGYkXwFHLh5) (Main Homepage, CSE Microsite, BSc in CSE Program page, Admin Notice form — link দিয়ে যুক্ত; Admin form-এ Admin / Department user দৃশ্য বদলানো যায়)। Team ও stakeholder review-এর পর Filament / Blade-এ বাস্তবায়ন |
