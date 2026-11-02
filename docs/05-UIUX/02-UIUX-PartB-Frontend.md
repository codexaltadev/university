# UI/UX Design Document
## Part B — Public Website (Frontend)

| বিষয় | তথ্য |
|---|---|
| Document | UI/UX Part B: Frontend |
| Version | 0.4 — v0.1 approved 2026-10-08; v0.2: microsite URL hierarchy অনুযায়ী (§3, FR-GEN-34); v0.3: module UI class contract (§9.1, Phase 4.1); v0.4: default theme = live DIU site-এর কপি (owner 2026-10-17) |
| Date | 2026-10-08 |
| Status | ✔ Approved — prototype v3 সহ (2026-10-08); v0.2 (URL) approved 2026-10-10 |
| Based on | BRD v1.7, SRS v1.0, SAD Part B §6 (Microsite), §8 (Theme Engine), UI/UX Part A |
| পাঠক | Theme developer, frontend developer, designer, tester |

> **Owner সিদ্ধান্ত 2026-10-17 (v0.4):** default theme হবে live **daffodilvarsity.edu.bd**-এর হুবহু কপি — header, menu, footer, রং, font, মাপ ও প্রতিটি page-এর গঠন live site থেকে; prototype v3 আর লক্ষ্য নয়। পরের theme-গুলো নিজস্ব design পাবে। এই document-এর slot, URL, accessibility ও Section 9-এর contract আগের মতোই বহাল; চেহারার বর্ণনা যেখানে live site-এর সাথে মেলে না, সেখানে live site-ই ঠিক। Default theme Tailwind-এর palette ও type scale ব্যবহার করে (DIU-র মতো); brand রং token থেকে, theme-এ hex নিষেধ।
>
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
| Page template | `default` (sidebar ছাড়া), `full-width`, `with-sidebar` (বাম sidebar), `right-sidebar`, `landing` (FR-PAGE-04 v0.3) |
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

---

## 11. Modern theme — audit (ধাপ ১, 2026-11-02)

নতুন theme **University Modern** (`themes/modern`, [00-MODERN-THEME.md](../00-MODERN-THEME.md))-এর পথে বাধা কোথায় — code পড়ে তালিকা।
ফলাফল: theme engine (slot, fallback resolver, token, section, override) অন্য theme-এর জন্য প্রস্তুত; বাধা মূলত **DIU-র লেখা
module Settings-এর default-এ** আর **core homepage section-এর view না থাকায়**।

### 11.1 DIU-নির্দিষ্ট লেখা / link / ছবি (অন্য university-কে বাধা দেয়)

| জায়গা | কী | প্রভাব | সুপারিশ |
|---|---|---|---|
| `Modules/*/app/Settings/*Settings.php` default (News, Events, Faq, Gallery, Scholarship, Testimonials, Tuition, Facilities, Partners, Transport, Newsletter, International, Admission `office_name/address`, Alumni `portal_url`, Faculty quick-link PDF) | "Daffodil International University", "DIU", "Daffodil Smart City", `daffodilvarsity.edu.bd` link | Admin কিছু না লিখলে নতুন install-এ পাতার intro-তে DIU দেখায় — theme যাই হোক | Default-গুলো generic করা ও DIU-র লেখা DIU-র install-এ seed / legacy step-এ রাখা — **আলাদা ধাপ** (DIU live site-এর লেখা বদলে যাওয়ার ঝুঁকি, তাই এই কাজে নয়; Decisions D-3) |
| `FacultyMemberSettings` placeholder / fallback `https://faculty.daffodilvarsity.edu.bd` | FMS host | অন্য university-র profile link DIU-তে যায় | `.env` / setting-এ host, fallback খালি — আলাদা ধাপ |
| `themes/default/theme.json` section default (hero video `daffodilvarsity.edu.bd/hero.mp4`, header CTA admission link, footer band virtual tour) ও section label "DIU — …" | শুধু default theme | modern-এ প্রভাব নেই (theme-এর নিজস্ব theme.json) | কিছু নয় |
| `DesignTokens::DEFAULTS` (DIU blue / navy / green, Montserrat + Manrope) | kernel fallback | theme token না দিলে DIU রং | Modern theme.json-এ সব token নিজের দেয় — kernel বদল লাগে না |
| `MakeModuleCommand` author "DIU Web Team" | scaffold | — | কিছু নয় (theme নয়) |
| `MicrositeResolver` "/department/bba/program/…" | শুধু comment | — | কিছু নয় |
| Lang file (`Modules/*/lang`) | কয়েকটি বাক্যে "DIU" (News, Events, Faq, Scholarship) | কিছু label-এ DIU | Settings-এর সাথে একই আলাদা ধাপে |

### 11.2 Theme override না করলে module পাতা কেমন দেখায়

Module-এর নিজের view **token + UI class contract (§9.1)** দিয়ে লেখা, inline style-এ token (`var(--space-*)`, `var(--color-*)`) — তাই
contract class (`btn`, `card`, `badge`, `table`, `prose`, `accordion__*`, `home-section*`, `container`) ভালোভাবে style করলে
পাতাগুলো ব্যবহারযোগ্য ও পরিষ্কার দেখায়। Contract-এর বাইরে যা module view ব্যবহার করে, কিন্তু §9.1-এ নেই:
`chip`, `chip-active` (notice), `tabs`, `tabs__list`, `tabs__tab`, `tabs__panel` (gallery / faq tab), `badge-primary` — modern theme
এগুলোও style করবে (§9.1-এ যোগের সুপারিশ)।

| অবস্থা | পাতা |
|---|---|
| **ভাঙা** (view নেই) | Core homepage section — `core.hero`, `core.statistics`, `core.cta`, `core.rich-text`, `core.image-text`, `core.html`, `core.spacer`: `Modules/Homepage/resources/views/` খালি, তাই theme না দিলে `sections/generic`-এর সাদা তালিকা। Theme-কে `sections/core-*.blade.php` দিতেই হবে |
| **অসুন্দর** (চলে, কিন্তু সাধারণ) | Notice board / notice, news / event listing ও detail, program listing / program page, faculty / department / program microsite home, admission, tuition, waiver calculator, scholarship, gallery, FAQ, office microsite, search — module view-এ hero, breadcrumb-এর চেহারা, card grid নেই |
| **যথেষ্ট** | Documents, links, committee, calendar, transport, CSR, achievements, alumni, partners, testimonials, international — সাধারণ list / card; contract style হলেই চলে |

### 11.3 `uwms:theme:make` command

Theme Guide §3-এ আছে, code-এ নেই (`platform/Console/Commands`-এ শুধু `ThemeActivate`, `ThemeCheck`, `ThemeSync`)। যা লাগবে:
`php artisan uwms:theme:make {slug} [--from=] [--blank] [--name=]` — slug যাচাই (manifest-এর একই regex), folder আগে থেকে থাকলে বাতিল;
`--from=default` = সেই theme-এর folder কপি (`public/build`, `node_modules` বাদ, theme.json-এ slug / name / version 1.0.0 বদল);
`--blank` (default) = validator-এর ন্যূনতম: theme.json, `layouts/app` + `layouts/microsite`, ৫টি page template, একটি header ও footer,
404, lang en / bn, `resources/css/app.css` + `js/app.js`, `vite.config.js`, README, CHANGELOG; শেষে `theme:sync` চালানোর পরামর্শ ও build command দেখানো।
Test: blank theme তৈরি ও (build-এর পরে) ThemeChecker pass, অবৈধ slug / বিদ্যমান folder বাতিল, `--from` কপি।

### 11.4 দেখানোর মতো data

| এলাকা | Data উৎস (module) | Modern theme-এ |
|---|---|---|
| Home | Homepage builder: core section + module section (notice board, upcoming / past events, latest / featured news, our faculties, featured programs, programs by level, testimonials, photo / video gallery, partners, recognition, achievements, leadership message, statistics, slider) | সব module section-এর modern view override + core section view |
| Program | `ProgramPage` presenter (tabs, overview, eligibility, fees, facts, stats, careers, materials) | listing + program microsite home override |
| Microsite | Faculty / Department / Program / Office / Lab — `$context->identity()`, homepage builder (kind-এর shared layout) | microsite layout + microsite-bar |
| Notice / News / Event | `NoticeBoardPage`, `NoticePage`, `NewsPage`, `EventPage` presenter | listing + detail override |
| Admission / Fee / Waiver | Admission circular, `TuitionPage`, Waiver Livewire (`WaiverCalculatorForm`) | পাতার frame; calculator-এর হিসাব ও `wire:` contract অপরিবর্তিত |
| Search | kernel `theme::search` + `instant-search` Livewire | override |

---

## 12. Modern theme — design (ধাপ ২, 2026-11-02)

**University Modern** (`themes/modern`): যেকোনো university-র জন্য modern, minimal theme — সাদা জায়গা, শান্ত রং, বড় পরিষ্কার
typography, card grid, হালকা animation। কোনো university-র নাম, link, ছবি বা রং theme-এ নয়; লেখা, logo, রং Admin থেকে।
Static mockup (desktop 1280 + phone 390, "Example University" demo লেখা): [`mockups/modern/index.html`](mockups/modern/index.html)
— home, program listing, program page, microsite home, notice board, notice, news & events, detail, admission + tuition + waiver,
search, 404। Owner-এর reference link দেওয়া হয়নি; ভাব নেওয়া হয়েছে সাধারণ আধুনিক university / SaaS site-এর ধরন থেকে (কপি নয়)।

### 12.1 Design token (theme.json default — Admin › Themes › Design থেকে বদলায়)

| Token | Default | ব্যবহার |
|---|---|---|
| `color-primary` | `#1D4ED8` (নীল, সাদায় 6.7:1) | link, primary button, active tab, eyebrow |
| `color-secondary` | `#0F172A` (প্রায় কালো slate) | footer, microsite bar, CTA band, active chip |
| `color-accent` | `#F59E0B` (amber) | গাঢ় background-এ eyebrow / highlight — সাদায় ছোট text নয় |
| `color-bg` / `color-surface` | `#FFFFFF` / `#F5F7FA` | page / বিকল্প section, page hero |
| `color-text` / `color-text-muted` | `#111827` / `#4B5563` | body / সহায়ক text (দুটোই AA) |
| `color-border` | `#E5E7EB` | card, divider, input |
| `radius-sm / md / lg` | 8 / 12 / 20px | input / badge-box / card; button ও chip pill (999px) |
| `shadow-card` | `0 1px 2px rgba(15,23,42,.06), 0 8px 24px -12px rgba(15,23,42,.12)` | card |
| `container-max` | 1240px | সব section-এর প্রস্থ, পাশে 20px gutter |
| Spacing | kernel `--space-*` (4px ভিত্তি); section 80px (phone 56px) | — |

রং শুধু token থেকে — theme-এর view / CSS-এ hex নেই (T-2); Tailwind palette (`gray-*`, `blue-*`) ব্যবহার হয় না, শুধু token utility
(`bg-primary`, `text-muted`, `border-border`, `bg-surface` …), যাতে Admin-এর রং পুরো site-এ চলে।

### 12.2 Typography (সব font bundled, কোনো external CDN নয়)

| | English | বাংলা |
|---|---|---|
| Font | **Inter** variable (400–800, OFL, `@fontsource-variable/inter`) heading ও body | **Noto Sans Bengali** variable (OFL) |
| Body | 16px / 1.65 | 16px / 1.85 (`:lang(bn)`, যুক্তাক্ষর) |
| Scale | 14 · 16 · 18 · 20 · 24 · 30 · 40 · 52 · 64 (fluid `clamp()`) | একই; heading-এ letter-spacing 0 |
| Heading | 700, letter-spacing −0.02em (display −0.035em) | 700, letter-spacing 0 |

`theme.json "fonts": ["Inter", "Noto Sans Bengali"]` — kernel Google Fonts চাইবে না; Admin অন্য font বাছলে তবেই।

### 12.3 Component

| Component | চেহারা |
|---|---|
| Button | pill, 44px উঁচু; `btn--primary` ভরা primary, `btn` সাদা + border, `btn--secondary` secondary ভরা, `btn--outline` স্বচ্ছ |
| Card | সাদা, 1px border, radius-lg, হালকা shadow; hover-এ 2px উপরে + border primary-র আভা; media card = উপরে 16:10 ছবি |
| Badge | ছোট pill, primary-র 10% আভা; success / warning / danger / muted রূপ |
| Chip (filter) | pill border; চালু = secondary ভরা |
| Tab | নিচে রেখা, চালু tab-এ primary রেখা; phone-এ পাশে scroll |
| Accordion | border-এর তালিকা, `+` → `−`, খোলা item-এর শিরোনাম primary |
| Table | header ছোট uppercase muted, সারির মাঝে রেখা, phone-এ পাশে scroll |
| Form | 48px input, radius-md, focus-এ primary ring |
| Pagination | গোল বোতাম, চালু = secondary |
| Breadcrumb | page hero-র উপরে ছোট muted, link primary |
| Hero | বাম লেখা (eyebrow, বড় heading, text, দুই button), ডানে radius-lg ছবি; phone-এ ছবি উপরে |
| Empty state | মাঝখানে muted লেখা, surface box |
| Date box | 56px box: দিন বড়, মাস ছোট — notice / event তালিকায় |

### 12.4 Layout

* **Header** (`modern`, একমাত্র variant + `minimal`): উপরে পাতলা utility row (topbar menu + ভাষা, ছোট muted); মূল row 72px —
  logo (+ microsite-এ entity নাম), মাঝে primary menu (submenu থাকলে **mega panel**: পুরো প্রস্থে column ধরে), ডানে search ও
  CTA button (theme option, খালি = নেই); scroll-এ সাদা blur sticky। 1024px-এর নিচে logo + search + ☰, menu ডান দিক থেকে drawer।
* **Microsite bar**: header-এর নিচে secondary রঙের পাতলা bar — entity নাম, parent link।
* **Footer** (`columns`, `simple`): secondary background; প্রথম column logo + tagline + social, তারপর Admin-এর footer column
  (না থাকলে footer-1..3 menu), নিচে copyright (region-এর, না থাকলে site name)।
* **Page template**: `default` (page hero + 760px লেখা), `full-width`, `with-sidebar` (বাম 300px), `right-sidebar`, `landing`
  (hero ছাড়া, block-গুলো পুরো প্রস্থে)। Page hero = surface background, breadcrumb, শিরোনাম, excerpt।
* **Microsite template** `standard`: microsite bar + homepage builder-এর section।

### 12.5 পাতা

| পাতা | গঠন (mockup অনুযায়ী) |
|---|---|
| Home | hero (core.hero / slider) → program finder (search + level chip) → statistics → notice + event দুই column → faculties grid → news card → admission CTA band → testimonials → gallery → partners / recognition logo। ক্রম ও চালু-বন্ধ Admin-এর homepage builder-এ |
| Program listing | page hero + search / level / faculty filter → media card grid (level badge, মেয়াদ, credit) → pagination |
| Program page | microsite bar → hero (badge, নাম, summary, Apply / brochure) → মূল তথ্যের stat row → tab (overview, eligibility, curriculum, fees, careers) + ডানে key facts / quick links |
| Microsite home | microsite bar → page-hero section (নাম, about, ছবি) → programs → notices + head |
| Notice board / notice | page hero + on-board / archive chip + filter → date-box তালিকা (urgent badge) → pagination; notice: hero, লেখা, attachment card, পাশে আরও notice |
| News / event | featured বড় card + 3-column grid, category chip; detail: 720px লেখা, বড় ছবি, event-এ তারিখ / সময় / স্থান box |
| Admission / tuition / waiver | ধাপের card, fee table tab সহ, calculator card (বামে form, ডানে ফল) — হিসাব module-এর, শুধু frame |
| Search | বড় search box, ধরন অনুযায়ী chip, ফলাফল তালিকা |
| 404 / 503 | বড় সংখ্যা, এক লাইন ব্যাখ্যা, search + home button |

### 12.6 Motion ও accessibility

Card hover 150ms, section-এ fade-up একবার (IntersectionObserver, `prefers-reduced-motion`-এ বন্ধ); focus ring 2px primary;
touch target ≥ 44px; skip link; সব রং-জোড়া AA; 360px-এ পাশে scroll নেই।

### 12.7 Module পাতা — modern-এ কোনটা কীভাবে (ধাপ ৫–৬)

| ধরন | পাতা |
|---|---|
| Modern override (`themes/modern/views/modules/…`) | notice board + notice, news listing + post, event, program card (সব listing layout ও section-এ) + card listing + program page, waiver calculator frame, tuition fee (grouped), search (`views/search.blade.php`) |
| Module view + modern contract style ও "module pages" CSS | events listing (list / cards / timeline / calendar), faculty / department / office / lab microsite home, program listing-এর list / table / detailed layout, tuition compact, admission (circular, result, seat plan, contact), scholarship, documents, gallery, FAQ, administration / people, committee, calendar, transport, CSR, achievements, alumni, partners, testimonials, international, links, newsletter, facilities, accreditation |
| Page template | default, full-width, with-sidebar, right-sidebar, landing — সবই page hero ও modern page block (core block = prose) |
| HTML block editor | `editor_css: ["css/editor.css"]` — Jodit-এ website-এর মতো font, heading, table |

পরে আলাদা override লাগলে অগ্রাধিকার: events listing, office microsite frame, admission circular, gallery album।

### 12.8 সংশোধন v1.1 — Bold & Photographic (owner 2026-11-02)

Owner-এর মন্তব্য: v1.0 অতি সাদামাটা, SaaS-এর মতো। Owner বলেছেন রং ও branding আমার মতো করে বানাতে, content ও data যেমন আছে তেমন রেখে। ফলে নতুন চেহারা:

| বিষয় | v1.1 |
|---|---|
| রং (theme.json default) | primary royal blue `#163C9E`, secondary midnight navy `#0B1B3F`, accent gold `#F5B700`, surface `#F3F5FA`, text `#0F172A` |
| Font | heading **Plus Jakarta Sans** 800 (bundled), body Inter, বাংলা Noto Sans Bengali |
| Hero | পুরো প্রস্থে ছবি, উপরে গাঢ় navy overlay; ছবি না থাকলে brand gradient + হালকা grid ও আলো; সাদা বড় heading, সোনালি eyebrow pill, সোনালি প্রধান বোতাম |
| Header | navy utility bar, বড় logo, bold menu, সোনালি Apply বোতাম; mega panel-এর উপরে নীল রেখা, column শিরোনামের নিচে সোনালি রেখা |
| Section | eyebrow-এর আগে সোনালি দাগ, বড় navy শিরোনাম; notice তালিকা card-এর ভেতরে, নীল date box (জরুরি হলে লাল); figures নীল gradient band-এ; testimonials navy পটভূমিতে; CTA band gradient + আলো |
| Card | border প্রায় নেই, নরম বড় shadow, hover-এ উপরে ওঠে, শিরোনাম নীল হয়; ছবি না থাকলে brand gradient |
| ভেতরের পাতা | page hero = navy→নীল gradient, সাদা শিরোনাম, filter chip সাদা-স্বচ্ছ, চালু chip সোনালি |
| Phone | নিচে স্থির bar: **Call** (Settings › Contact-এর ফোন) + **Apply** (header বোতামের option) — দুটোই খালি হলে bar নেই |
| Footer | উপরে সোনালি রেখা, navy, column শিরোনামের নিচে সোনালি দাগ, social hover সোনালি |

নতুন কোনো content field যোগ হয়নি। Hero-র image field-এর অর্থ এখন "heading-এর পেছনের ছবি", আর বিদ্যমান option ও setting ব্যবহার হয় (header বোতাম, contact phone)।
