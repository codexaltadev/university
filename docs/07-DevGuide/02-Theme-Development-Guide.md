# Theme Development Guide

| বিষয় | তথ্য |
|---|---|
| Document | Development Guide — Part B: Theme |
| Version | 1.2 (Tailwind CSS v4.3.3 CSS-first config; Alpine.js যেখানে প্রয়োজন) |
| Date | 2026-10-11 |
| Status | ✔ Approved |
| Based on | SAD Part B (§6 fallback resolver, §8 theme engine, §8.6 Blade + Livewire), UI/UX Part B (§2 token, §4 layout, §6 component, §8 checklist, §9 Theme Developer Contract), SRS C-06 (FR-THEME, FR-MENU, FR-HF, FR-PAGE-04) |
| পাঠক | Frontend / Laravel developer যিনি নতুন public website theme বানাবেন বা default theme বদলাবেন |

### Development Guide-এর গঠন

| Part | বিষয় | Status |
|---|---|---|
| A | [Module Development](01-Module-Development-Guide.md) | ✔ Approved |
| **B** | Theme Development — এই document, একটি উদাহরণ theme (`campus`) সহ | **✔ Approved** |

> **গুরুত্বপূর্ণ:** `<x-platform::…>` component, `@themeVite`, `uwms:theme:*` command ও `theme.json` schema **kernel**-এর অংশ। এই guide তাদের **নির্ধারিত API** দেখায়; kernel তৈরির সময় নাম সামান্য বদলালে guide একসাথে update হবে।

## সূচি

1. [Theme কী, কী নয়](#1-theme-কী-কী-নয়)
2. [৬টি নিয়ম যা ভাঙা যাবে না](#2-৬টি-নিয়ম-যা-ভাঙা-যাবে-না)
3. [Quick start: theme তৈরি](#3-quick-start-theme-তৈরি)
4. [ধাপ ১ — Manifest (`theme.json`)](#4-ধাপ-১--manifest-themejson)
5. [ধাপ ২ — Base layout ও slot](#5-ধাপ-২--base-layout-ও-slot)
6. [ধাপ ৩ — Design token ও CSS](#6-ধাপ-৩--design-token-ও-css)
7. [ধাপ ৪ — Header, footer ও menu](#7-ধাপ-৪--header-footer-ও-menu)
8. [ধাপ ৫ — Page template ও microsite template](#8-ধাপ-৫--page-template-ও-microsite-template)
9. [ধাপ ৬ — Module view override](#9-ধাপ-৬--module-view-override)
10. [ধাপ ৭ — Livewire view override](#10-ধাপ-৭--livewire-view-override)
11. [ধাপ ৮ — Theme option ও translation](#11-ধাপ-৮--theme-option-ও-translation)
12. [ধাপ ৯ — Asset build](#12-ধাপ-৯--asset-build)
13. [ধাপ ১০ — Test](#13-ধাপ-১০--test)
14. [ধাপ ১১ — Package, version ও update](#14-ধাপ-১১--package-version-ও-update)
15. [Submission checklist](#15-submission-checklist)
16. [Reference](#16-reference)

---

## 1. Theme কী, কী নয়

| Theme **করে** | Theme **করে না** (module বা kernel-এর কাজ) |
|---|---|
| Public website-এর চেহারা: layout, header, footer, page template, typography, spacing | Data query, business rule, permission |
| Module-এর view (listing, detail, section, Livewire view) override | Livewire component-এর PHP logic, route, controller |
| Design token-এর **default** মান দেয় | Admin-এর বাছাই করা রং / font / logo বাতিল করা |
| Menu location, header / footer variant, page / microsite template ঘোষণা | Menu-র item, homepage-এর section ক্রম (admin builder-এ ঠিক হয়) |

Admin Panel (Filament) theme-এর আওতায় নয়; তার branding আলাদা setting (FR-SET-13)।

**উদাহরণ theme:** এই guide জুড়ে আমরা **`campus`** theme বানাব — default theme-এর একটি বিকল্প, যেখানে header-এ centered logo, বড় photo hero ও "magazine" listing layout।

---

## 2. ৬টি নিয়ম যা ভাঙা যাবে না

Install validator ও CI এগুলো যাচাই করে (FR-THEME-02); ভাঙলে theme install হবে না।

| # | নিয়ম | যাচাই |
|---|---|---|
| T-1 | **কোনো PHP business logic নয়** — শুধু Blade view, asset, `theme.json`, translation (BRL-THEME-02)। Blade-এ `@php` block, DB query, `Model::` call নিষেধ | Validator: `.php` শুধু `*.blade.php` ও `lang/*.php`; Blade-এ নিষিদ্ধ pattern scan |
| T-2 | **শুধু token** — hardcoded রং, font-family নয়; `var(--color-*)`, `var(--font-*)` (FR-GEN-101, FR-THEME-07) | Stylelint rule (`color-no-hex` ইত্যাদি, token file ছাড়া) |
| T-3 | **সব slot ও `@livewireStyles` / `@livewireScripts` base layout-এ** (Section 5) | Validator: layout render test |
| T-4 | **Module না থাকলেও ভাঙবে না** — কোনো module-এর route / component সরাসরি ধরে নেবেন না; `@moduleActive('slug')` দিয়ে যাচাই (UI/UX F-7) | Test: সব installable module inactive অবস্থায় render |
| T-5 | **Override-এ contract অক্ষত** — module view-এর দেওয়া variable, Livewire-এর `wire:` attribute ও public property-র নাম বদলাবেন না (Section 9–10) | Test: override সহ module-এর feature test |
| T-6 | **Prebuilt asset** — ZIP-এ compiled CSS / JS থাকবে; server-এ `npm` চলে না | Validator: `public/build/manifest.json` আছে কিনা |

---

## 3. Quick start: theme তৈরি

```bash
php artisan uwms:theme:make campus --from=default     # default theme কপি করে শুরু (প্রস্তাবিত)
# অথবা
php artisan uwms:theme:make campus --blank            # শুধু প্রয়োজনীয় file
```

যা তৈরি হয় (SAD Part B §8.1):

```
themes/campus/
├── theme.json
├── README.md  CHANGELOG.md
├── screenshot.png                  # 1200×900, Theme Manager-এ দেখায়
├── views/
│   ├── layouts/
│   │   ├── app.blade.php           # base (main site)
│   │   └── microsite.blade.php     # microsite (Faculty / Department / Program / Office / Lab)
│   ├── templates/                  # page template
│   │   ├── default.blade.php
│   │   ├── full-width.blade.php
│   │   ├── with-sidebar.blade.php
│   │   └── landing.blade.php
│   ├── microsite-templates/
│   │   └── standard.blade.php
│   ├── partials/
│   │   ├── headers/centered.blade.php   classic.blade.php
│   │   ├── footers/columns.blade.php
│   │   ├── topbar.blade.php  microsite-bar.blade.php  breadcrumb.blade.php
│   ├── components/                 # theme-এর নিজস্ব Blade component (<x-theme::card>)
│   ├── modules/{slug}/…            # module view override (ঐচ্ছিক)
│   └── errors/404.blade.php  503.blade.php
├── lang/en/theme.php  lang/bn/theme.php
├── resources/                      # source — ZIP-এ ঐচ্ছিক
│   ├── css/app.css
│   └── js/app.js
├── public/build/                   # compiled asset + manifest.json — ZIP-এ বাধ্যতামূলক
├── package.json  vite.config.js    # Tailwind v4: tailwind.config.js নেই — config resources/css/app.css-এ
```

> **Frontend stack (নির্ধারিত):** Blade + Livewire + **Tailwind CSS v4.3.3** (version pin করা, `^` নয়) + **Alpine.js যেখানে প্রয়োজন** (§12) — যেখানে যেটা ভালো, সেখানে সেটা। Bootstrap, jQuery বা অন্য CSS framework নয়।

Development-এ:

```bash
cd themes/campus && npm install && npm run dev       # Vite dev server, hot reload
php artisan uwms:theme:activate campus --preview     # শুধু আপনার session-এ preview (FR-THEME-03)
```

---

## 4. ধাপ ১ — Manifest (`theme.json`)

```json
{
  "slug": "campus",
  "name": "Campus",
  "version": "1.0.0",
  "requires_core": "^1.0",
  "author": { "name": "DIU Web Team", "email": "web@example.edu" },
  "license": "proprietary",
  "description": "Photo-first theme with centered header and magazine listings",
  "screenshot": "screenshot.png",

  "layouts": {
    "news.listing": ["grid", "list", "magazine"],
    "news.detail": ["default"],
    "faq.listing": ["accordion"]
  },

  "menu_locations": {
    "primary": "Primary (header)",
    "topbar": "Top bar",
    "footer-1": "Footer column 1",
    "footer-2": "Footer column 2",
    "quick-links-icon": "Quick links (icon tiles)"
  },

  "header_variants": ["centered", "classic"],
  "footer_variants": ["columns"],

  "page_templates": ["default", "full-width", "with-sidebar", "landing"],
  "microsite_templates": ["standard"],

  "tokens": {
    "color-primary": "#034EA2",
    "color-secondary": "#002652",
    "color-accent": "#39B54A",
    "color-bg": "#FFFFFF",
    "color-surface": "#F3F4F9",
    "font-heading": "Montserrat",
    "font-body": "Manrope",
    "font-bn": "Hind Siliguri",
    "radius-md": "8px"
  },

  "options": [
    { "key": "sticky_header", "type": "toggle", "label": "Sticky header", "default": true },
    { "key": "hero_height", "type": "select", "label": "Hero height", "options": ["medium", "tall"], "default": "tall" }
  ]
}
```

| Field | মনে রাখুন |
|---|---|
| `slug` | একবার install হলে বদলায় না; view namespace `theme::` সবসময় **active** theme নির্দেশ করে, slug নয় |
| `layouts` | এখানে যে layout ঘোষণা করবেন, `views/modules/{slug}/…`-এ তার view থাকতে হবে **অথবা** module-এর নিজের সেই layout থাকতে হবে। না থাকলে module-এর default (FR-THEME-11) |
| `menu_locations` | Kernel-এর standard location (`primary`, `topbar`, `footer-*`, `quick-links-icon`, `sidebar`) ব্যবহার করুন; নতুন key দিলে অন্য theme-এ গেলে সেই menu দেখাবে না (FR-MENU-01) |
| `page_templates` | চারটি standard template বাধ্যতামূলক (FR-PAGE-04); অতিরিক্ত যোগ করা যায়, কিন্তু theme বদলালে অচেনা template `default`-এ পড়ে |
| `tokens` | শুধু **default** — admin Theme settings-এ বদলালে সেটাই জয়ী (Section 6) |
| `options` | Theme-নির্দিষ্ট ছোট setting; type: `toggle`, `select`, `text`, `number`, `color`, `image` (Section 11) |

---

## 5. ধাপ ২ — Base layout ও slot

Kernel প্রতিটি page-কে layout-এর **নির্দিষ্ট slot**-এ ভরে দেয় (UI/UX Part B §9)। Slot-এর নাম ও উপস্থিতি বাধ্যতামূলক; জায়গা ও style theme-এর ইচ্ছা।

| Slot | কী আসে | উৎস |
|---|---|---|
| `head` | Meta, OG, canonical, hreflang, JSON-LD, token CSS | `<x-platform::head />` |
| `topbar` | Contact, quick links, language switcher, social | FR-HF-02 |
| `announcement` | Announcement bar | Announcement module (inactive হলে খালি) |
| `header` | Logo + primary menu (নির্বাচিত variant) | FR-HF-01, fallback resolver |
| `microsite-bar` | Entity নাম, parent link (শুধু microsite) | UI/UX §4.2 |
| `ticker` | Notice ticker | FR-HF-06 |
| `breadcrumb` | Breadcrumb + JSON-LD | — |
| `content` | Page template / module view | — |
| `sidebar` | `with-sidebar` template-এ | — |
| `footer` | Footer (নির্বাচিত variant) | FR-HF-03 |
| `popup` | Popup announcement | FR-ANW-04 |

```blade
{{-- themes/campus/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-platform::head />                                  {{-- SEO + token CSS (SAD Part B §8.4) --}}
    @themeVite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-bg text-text font-body @themeOption('sticky_header') has-sticky-header @endthemeOption">
    <a href="#main" class="skip-link">{{ __('theme::theme.skip_to_content') }}</a>

    <x-platform::slot name="topbar" />
    <x-platform::slot name="announcement" />
    <x-platform::slot name="header" />                    {{-- নির্বাচিত variant: partials/headers/{variant} --}}
    <x-platform::slot name="ticker" />
    <x-platform::slot name="breadcrumb" />

    <main id="main">
        {{ $slot }}                                        {{-- content: page template বা module view --}}
    </main>

    <x-platform::slot name="footer" />
    <x-platform::slot name="popup" />

    @livewireScripts
</body>
</html>
```

`<x-platform::slot>` প্রথমে theme-এর `partials/{slot}.blade.php` (বা variant) খোঁজে, না পেলে kernel-এর minimal default দেখায়। Header / footer: `partials/headers/{variant}`, `partials/footers/{variant}` — variant region-এর নির্বাচন, theme-এ না থাকলে theme-এর প্রথম variant। Partial পায় `$region` (null = theme default), `$config` (region-এর switch / footer column) ও `$context`। Slot-এর data (menu, logo, entity) fallback resolver থেকে আসে (SAD Part B §6.2) — theme নিজে খুঁজবে না।

**`microsite.blade.php`** একই, শুধু `header`-এর পরে `<x-platform::slot name="microsite-bar" />`; microsite-এর menu, header, footer automatically entity → parent → main resolve হয়।

**Kernel-এর দেওয়া Blade helper (theme ব্যবহার করবে):**

| Helper | কাজ |
|---|---|
| `<x-platform::menu location="primary" :depth="3" />` | Resolved menu tree; `view` attribute দিয়ে নিজের markup (`view="theme::partials.menus.mega"`) |
| `<x-platform::logo variant="light" />` | Entity / university logo (fallback সহ, FR-THEME-06) |
| `<x-platform::language-switcher />` | `hreflang` সহ link |
| `<x-platform::image :media="$item->featured" sizes="(min-width:1024px) 33vw, 100vw" />` | `srcset`, WebP, lazy, width / height, alt (NFR-PERF-05) |
| `<x-platform::homepage :context="$context" />` | Homepage builder-এর section-গুলো ক্রমে |
| `<x-platform::stats :context="$context" />` | Stat set (C-20) |
| `@moduleActive('notice') … @endmoduleActive` | Module active কিনা (T-4) |
| `@themeOption('key') … @endthemeOption`, `theme_option('key')` | Theme option (Section 11) |
| `$context` (সব view-এ shared) | `SiteContext`: `isMicrosite()`, `entity()`, `chain()` |

---

## 6. ধাপ ৩ — Design token ও CSS

### 6.1 Token কোথা থেকে আসে

```
theme.json "tokens"   (theme default)
   └─► Admin Theme settings (FR-THEME-04/05) — বদলালে override
         └─► compile: :root { --color-primary: …; --font-heading: …; }  (SAD Part B §8.4)
               └─► <x-platform::head /> এই CSS link করে
```

Theme কখনো `:root`-এ token-এর মান নিজে **লিখবে না** — শুধু ব্যবহার করবে। পূর্ণ token তালিকা: UI/UX Part B §2।

### 6.2 Tailwind-কে token-এ বাঁধা

Tailwind **v4.3.3** CSS-first: `tailwind.config.js` নেই; সব config `resources/css/app.css`-এ। `@theme inline` token-কে utility-তে বাঁধে, মান kernel-এর `:root` থেকে আসে (theme নিজে মান লেখে না):

```css
/* themes/campus/resources/css/app.css */
@import "tailwindcss";

/* view কোথায় — theme ও module view (নইলে module-এর class build-এ আসবে না) */
@source "../../views";
@source "../../../../Modules/*/resources/views";
@source "../../../../platform/resources/views";

@theme inline {
  --color-primary: var(--color-primary);
  --color-primary-contrast: var(--color-primary-contrast);
  --color-secondary: var(--color-secondary);
  --color-accent: var(--color-accent);
  --color-bg: var(--color-bg);
  --color-surface: var(--color-surface);
  --color-text: var(--color-text);
  --color-muted: var(--color-text-muted);
  --color-border: var(--color-border);
  --font-heading: var(--font-heading);
  --font-body: var(--font-body);
  --radius-sm: var(--radius-sm);
  --radius-md: var(--radius-md);
  --radius-lg: var(--radius-lg);
  --container-container: var(--container-max);
}
```

এখন Blade-এ `bg-primary`, `text-muted`, `font-heading`, `rounded-md`, `max-w-container` — সবই admin-এর রং মেনে চলে। `bg-blue-700`, `text-[#034EA2]` CI lint fail করবে।

* `package.json`-এ `tailwindcss` ও `@tailwindcss/vite` দুটোই ঠিক `4.3.3` (pin); version বদলানো = আলাদা সিদ্ধান্ত, CHANGELOG-এ লেখা।
* `vite.config.js`-এ `@tailwindcss/vite` plugin (PostCSS config লাগে না)।
* Reusable অংশ (card, hero, mega menu, accordion) Blade component (`<x-theme::card>`); `@apply` শুধু খুব দরকারে।

### 6.3 বাংলা

```css
/* resources/css/app.css */
:lang(bn) {
  font-family: var(--font-bn);
  line-height: var(--line-height-body-bn, 1.8);   /* যুক্তাক্ষর ও মাত্রা (UI/UX §2.2) */
  letter-spacing: 0;
}
```

বাংলা ও English দুই ভাষায় প্রতিটি page দেখে নিন — বাংলা text সাধারণত ১০–২০% লম্বা; fixed-height card বা button-এ text কেটে যায় কিনা।

---

## 7. ধাপ ৪ — Header, footer ও menu

### 7.1 Header variant

```blade
{{-- themes/campus/views/partials/headers/centered.blade.php --}}
<header @class(['site-header bg-bg border-b border-border', 'sticky top-0 z-40' => theme_option('sticky_header')])>
    <div class="mx-auto max-w-container flex flex-col items-center gap-3 py-4">
        <a href="{{ $context->homeUrl() }}"><x-platform::logo variant="light" class="h-14" /></a>
        <x-platform::menu location="primary" :depth="3" view="theme::partials.menus.mega" />
    </div>
</header>
```

Admin Header / Footer settings-এ `centered` ও `classic` থেকে বাছবেন (FR-HF-01); microsite নিজের variant নিতে পারে (FR-HF-05)।

### 7.2 Menu markup

```blade
{{-- themes/campus/views/partials/menus/mega.blade.php — $items: resolved tree --}}
<nav aria-label="{{ __('theme::theme.primary_nav') }}" x-data="{ open: null }">
    <ul class="flex gap-6 font-heading">
        @foreach ($items as $item)
            <li class="relative" @keydown.escape="open = null">
                @if ($item->children->isNotEmpty())
                    <button type="button" :aria-expanded="open === {{ $loop->index }}"
                            @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}">
                        {{ $item->label }}
                    </button>
                    <div x-show="open === {{ $loop->index }}" x-cloak class="mega-panel bg-bg shadow-card rounded-md">
                        @include('theme::partials.menus.mega-panel', ['items' => $item->children])
                    </div>
                @else
                    <a href="{{ $item->url }}" @if ($item->isActive) aria-current="page" @endif>{{ $item->label }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
```

`$item`-এর field: `label`, `url`, `target`, `icon`, `isActive`, `children`, `type`। Inactive module ও inactive / deleted target-এর item kernel আগেই বাদ দেয় (FR-MENU-10/11)।

**Mobile:** একই tree দিয়ে off-canvas menu (UI/UX §4.3) — `<x-platform::menu location="primary" view="theme::partials.menus.offcanvas" />`।

### 7.3 Footer

Footer column-এর block (about, menu, contact, social, newsletter, custom HTML) admin builder-এ ঠিক হয় (FR-HF-03); theme শুধু প্রতিটি block type-এর markup দেয়:

```
views/partials/footers/columns.blade.php
views/partials/footer-blocks/about.blade.php  menu.blade.php  contact.blade.php
                             social.blade.php  newsletter.blade.php  html.blade.php
```

Block view না থাকলে kernel-এর default markup। `newsletter` block-এ Newsletter module-এর Livewire form বসে — `@moduleActive('newsletter')` দিয়ে ঘিরে রাখুন।

---

## 8. ধাপ ৫ — Page template ও microsite template

### 8.1 Page template (FR-PAGE-04)

```blade
{{-- themes/campus/views/templates/with-sidebar.blade.php --}}
<x-theme::layout>                                  {{-- layouts/app বা microsite — kernel ঠিক করে --}}
    <div class="mx-auto max-w-container grid gap-8 lg:grid-cols-[1fr_320px] py-12">
        <article class="prose-theme">
            <h1 class="font-heading text-4xl">{{ $page->title }}</h1>
            {!! $body !!}                           {{-- Page builder block, kernel render করে ও sanitize করে --}}
        </article>
        <aside><x-platform::slot name="sidebar" /></aside>
    </div>
</x-theme::layout>
```

`<x-theme::layout>` context অনুযায়ী `layouts.app` বা `layouts.microsite` বেছে নেয়।

### 8.2 Microsite template (BR-MS-05)

Faculty / Department / Program / Office / Lab-এর homepage-এর মোড়ক। Section-গুলো homepage builder থেকে আসে, theme শুধু চারপাশ সাজায়:

```blade
{{-- themes/campus/views/microsite-templates/standard.blade.php --}}
<x-theme::layout>
    <x-theme::entity-hero :entity="$context->entity()" :banner="$banner" />   {{-- banner: fallback resolver --}}
    <x-platform::homepage :context="$context" />
</x-theme::layout>
```

### 8.3 Homepage section-এর মোড়ক

প্রতিটি homepage section-এর view module-এর (যেমন `faq::sections.faq-highlights`); theme সব section-এর চারপাশের markup একবার দেয়:

```blade
{{-- themes/campus/views/partials/section-wrapper.blade.php --}}
<section id="{{ $section->anchor }}" @class(['py-16', 'bg-surface' => $section->style === 'alt'])>
    <div class="mx-auto max-w-container">
        @if ($section->title)
            <h2 class="font-heading text-3xl mb-8">{{ $section->title }}</h2>
        @endif
        {{ $slot }}
    </div>
</section>
```

---

## 9. ধাপ ৬ — Module view override

Module-এর যেকোনো view theme বদলাতে পারে (FR-THEME-10, SAD Part B §8.2):

```
news::listing.magazine চাওয়া হলে:
  1. themes/{active}/views/modules/news/listing/magazine.blade.php     ← theme override
  2. Modules/News/resources/views/listing/magazine.blade.php           ← module default
```

**উদাহরণ — নতুন layout যোগ:** News module-এ `grid` ও `list` আছে; `campus` theme `magazine` যোগ করে:

1. `theme.json` → `"news.listing": ["grid", "list", "magazine"]`
2. `themes/campus/views/modules/news/listing/magazine.blade.php` তৈরি
3. Admin Theme Management → News listing → Magazine (FR-THEME-08)

```blade
{{-- themes/campus/views/modules/news/listing/magazine.blade.php --}}
{{-- Module-এর দেওয়া variable: $posts (paginator), $context, $filters — নাম বদলাবেন না (T-5) --}}
<x-platform::page-header :title="__('news::news.title')" />

<div class="mx-auto max-w-container grid gap-6 lg:grid-cols-3">
    @foreach ($posts as $post)
        <article @class(['lg:col-span-2 lg:row-span-2' => $loop->first])>
            <a href="{{ $post->url }}" class="block group">
                <x-platform::image :media="$post->featured" :sizes="$loop->first ? '66vw' : '33vw'" class="rounded-md" />
                <h2 class="font-heading mt-3 group-hover:text-primary">{{ $post->title }}</h2>
                <time datetime="{{ $post->published_at->toDateString() }}" class="text-muted text-sm">
                    {{ $post->published_at->translatedFormat('d M Y') }}
                </time>
            </a>
        </article>
    @endforeach
</div>

{{ $posts->links('theme::partials.pagination') }}
```

**Contract কোথায় পাবেন:** প্রতিটি module-এর README-তে "Views" অংশ — view নাম ও প্রতিটির variable তালিকা (Module Guide §17 checklist)। Override-এর আগে module-এর default view কপি করে শুরু করুন:

```bash
php artisan uwms:theme:override news listing.grid      # → themes/{active}/views/modules/news/listing/grid.blade.php
```

**Module update হলে:** module CHANGELOG-এ view variable বদলের কথা থাকলে override update করতে হবে। Theme Manager module update-এর পর "Outdated overrides" তালিকা দেখায় (module view-এর hash বদলেছে কিন্তু override নয়)।

---

## 10. ধাপ ৭ — Livewire view override

Livewire component-এর **PHP class module-এ**, theme শুধু **Blade view** বদলাতে পারে (SAD Part B §8.6):

```
faq::livewire.faq-search
  1. themes/{active}/views/modules/faq/livewire/faq-search.blade.php
  2. Modules/Faq/resources/views/livewire/faq-search.blade.php
```

```blade
{{-- themes/campus/views/modules/faq/livewire/faq-search.blade.php --}}
{{-- Contract: public property $search; action helpful(int $faqId, bool $yes); variable $groups --}}
<div>
    <label for="faq-q" class="sr-only">{{ __('faq::faq.search') }}</label>
    <input id="faq-q" type="search" wire:model.live.debounce.300ms="search"
           class="w-full rounded-md border-border" placeholder="{{ __('faq::faq.search') }}">

    <p aria-live="polite" class="text-muted text-sm mt-2">{{ trans_choice('faq::faq.results', $groups->flatten()->count()) }}</p>

    @foreach ($groups as $category => $faqs)
        <h2 class="font-heading text-2xl mt-10">{{ $category }}</h2>
        @foreach ($faqs as $faq)
            <div x-data="{ open: false }" class="border-b border-border" wire:key="faq-{{ $faq->id }}">
                <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="faq-a-{{ $faq->id }}"
                        class="w-full text-left py-4 font-heading">
                    {{ $faq->question }}
                </button>
                <div id="faq-a-{{ $faq->id }}" x-show="open" x-collapse class="pb-4">
                    {!! $faq->answer !!}
                    @if (settings('faq.show_helpful_buttons'))
                        <button wire:click="helpful({{ $faq->id }}, true)">👍</button>
                        <button wire:click="helpful({{ $faq->id }}, false)">👎</button>
                    @endif
                </div>
            </div>
        @endforeach
    @endforeach
</div>
```

| বদলাতে পারবেন | বদলাতে পারবেন না |
|---|---|
| Markup, class, Alpine.js interaction, icon, ক্রম | `wire:model` / `wire:click`-এর property ও method নাম |
| কোন variable দেখাবেন বা লুকাবেন | Root element একটি থাকার Livewire নিয়ম |
| Loading state (`wire:loading`) | Validation, rate limit, honeypot field (module-এর partial `<x-platform::honeypot />` রেখে দিন) |

---

## 11. ধাপ ৮ — Theme option ও translation

### 11.1 Option

`theme.json`-এর `options` Theme Management-এ "Theme options" tab হিসেবে আসে; মান প্রতিটি theme-এর জন্য আলাদা সংরক্ষিত হয়।

```blade
@if (theme_option('hero_height') === 'tall') min-h-[80vh] @endif
```

Option শুধু চেহারার জন্য। Content বা data (যেমন "হোমপেজে কয়টি news") option নয় — সেটা homepage section setting (FR-HOME-11)।

### 11.2 Translation

Theme-এর নিজের string (`skip_to_content`, `read_more`, `primary_nav` …) `lang/{locale}/theme.php`-এ; ব্যবহার `__('theme::theme.key')`। Module-এর string module-এর namespace-এ (`news::news.title`) — theme-এ কপি করবেন না। `en` ও `bn` দুটোই বাধ্যতামূলক; admin translation override (FR-LANG-03) theme string-এও কাজ করে।

---

## 12. ধাপ ৯ — Asset build

```js
// themes/campus/vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    tailwindcss(),
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      buildDirectory: 'build',          // → themes/campus/public/build
      publicDirectory: 'public',
      refresh: ['views/**'],
    }),
  ],
});
```

```bash
npm run build      # → public/build/manifest.json + hashed file
```

* Install / activate-এর সময় kernel `public/` কে `public/themes/{slug}`-এ publish করে; `@themeVite` manifest পড়ে সঠিক URL দেয়।
* **JS — যেখানে যেটা সবচেয়ে ভালো, সেখানে সেটা:**
  * Alpine.js যেখানে প্রয়োজন সেখানে নির্দ্বিধায় ব্যবহার করুন — mega menu, mobile off-canvas, tab, slider, modal / lightbox, accordion, counter, scroll reveal, যেকোনো browser-side interaction। "আগে অন্য কিছু চেষ্টা" বাধ্যতামূলক নয়।
  * কাজ অনুযায়ী সেরা পথ বেছে নিন: browser-এর interaction = Alpine; server-এর data / form / lookup = Livewire; শুধু চেহারা = Tailwind / CSS।
  * Alpine Livewire-এর সাথেই আসে — **আলাদা Alpine, jQuery বা React / Vue load করবেন না**। Alpine plugin (Focus, Collapse, Intersect) Livewire-এর Alpine-এ যোগ হয়।
  * Alpine-এর `x-data` ছোট রাখুন; বড় logic `Alpine.data('megaMenu', …)` হিসেবে `resources/js/app.js`-এ। `[x-cloak]` ব্যবহার করুন; JS বন্ধ থাকলেও link কাজ করবে (progressive enhancement)।
  * Slider, lightbox-এর মতো ছোট library ঠিক আছে, কিন্তু bundle size বাজেট: CSS ≤ 80 KB, JS ≤ 120 KB (gzip) (NFR-PERF-02)।
* Font: token-এ যে font, kernel সেটি Google Fonts / upload থেকে load করে (FR-THEME-05); theme নিজে font link দেবে না।
* Image (logo, pattern): `public/images/`; content image কখনো theme-এ নয় — Media Library-তে।

---

## 13. ধাপ ১০ — Test

```bash
php artisan uwms:theme:check campus
```

| Check | কী যাচাই | Ref |
|---|---|---|
| Structure | `theme.json` schema, বাধ্যতামূলক layout / template / slot, `public/build/manifest.json` | T-3, T-6 |
| Forbidden code | `.php` file, Blade-এ `@php` / DB / Model call | T-1 |
| Token lint | Hex রং, hardcoded font-family, arbitrary color class | T-2 |
| Render matrix | প্রতিটি template × {main site, faculty, department, program, office, lab} × {en, bn} — error ছাড়া render | — |
| Module-less | সব installable module inactive করে homepage, menu, footer render | T-4 |
| Override contract | Override থাকা module-এর feature test override সহ চালানো | T-5 |
| Accessibility | axe-core (staging): সব critical / serious শূন্য | UI/UX §8 |
| Performance | Lighthouse (homepage, program, news listing): Performance ≥ 80, CLS < 0.1 | NFR-PERF-01 |

**Manual:**

- [ ] Admin-এ রং বদলে (খুব হালকা ও খুব গাঢ় primary) সব page পড়া যায়
- [ ] Logo ছাড়া ও খুব চওড়া logo — header ভাঙে না
- [ ] Menu ৩ স্তর, ২০টি item — mega menu ও mobile off-canvas
- [ ] বাংলা দীর্ঘ শিরোনাম — card ও button
- [ ] Keyboard-only: skip link, menu, accordion, slider pause, lightbox Esc
- [ ] `prefers-reduced-motion` চালু অবস্থায় animation বন্ধ
- [ ] Print view (Notice, Waiver result)

---

## 14. ধাপ ১১ — Package, version ও update

```bash
php artisan uwms:theme:package campus    # → storage/app/theme-packages/campus-1.0.0.zip
```

ZIP-এ থাকে: `theme.json`, `views/`, `lang/`, `public/build/`, `public/images/`, `screenshot.png`, README, CHANGELOG। বাদ: `node_modules/`, `.git/`, `.env`, symlink। `resources/` (source) ঐচ্ছিক — অন্যরা পরে বদলাতে চাইলে রাখুন।

| পরিবর্তন | Version |
|---|---|
| CSS / markup ঠিক করা | PATCH |
| নতুন layout, variant, template, option | MINOR |
| Layout / template / menu location / option বাদ দেওয়া বা নাম বদল | MAJOR — CHANGELOG-এ কী বদলাতে হবে লিখুন |

**Update-এর নিয়ম:**

* Option বা menu location বাদ গেলে সংরক্ষিত মান হারায় না; পুরনো theme-এ ফিরলে আবার কাজ করে।
* Active theme update হলে নতুন hash-এর CSS তৈরি হয়, page cache clear হয় (SAD Part B §10)।
* Active theme uninstall নিষেধ; default theme কখনো uninstall নয় (BRL-THEME-01)।
* **Child theme নেই** (Release 1): ছোট পরিবর্তনের জন্য default theme কপি করে নতুন slug দিন (`--from=default`)। নয়তো default theme update-এ আপনার পরিবর্তন মুছে যাবে।

---

## 15. Submission checklist

- [ ] `theme.json` সম্পূর্ণ; `requires_core` ঠিক; `screenshot.png` আছে
- [ ] `layouts/app` ও `layouts/microsite`-এ সব slot, `@livewireStyles`, `@livewireScripts`, skip link
- [ ] চারটি standard page template; ন্যূনতম একটি header ও footer variant; একটি microsite template
- [ ] কোনো `.php` (Blade ও lang ছাড়া), `@php`, query নেই
- [ ] Tailwind `4.3.3` pin; শুধু token; `@theme inline` token-এ বাঁধা; module view path `@source`-এ
- [ ] Interaction-এ Alpine, server data-য় Livewire; আলাদা Alpine / jQuery নেই
- [ ] Module-dependent অংশ `@moduleActive`-এ ঘেরা
- [ ] প্রতিটি module override-এ contract (variable, `wire:` নাম) অক্ষত; README-তে override তালিকা
- [ ] `en` ও `bn` translation; বাংলা font ও line-height
- [ ] `public/build/manifest.json` আছে; bundle size বাজেটের মধ্যে
- [ ] `uwms:theme:check` pass; axe ও Lighthouse লক্ষ্য পূরণ
- [ ] Manual checklist (Section 13) সম্পন্ন
- [ ] CHANGELOG-এ version entry

---

## 16. Reference

| বিষয় | কোথায় |
|---|---|
| Theme package গঠন, view resolution, token compile, preview | SAD Part B §8.1–§8.5 |
| Blade + Livewire দায়িত্ব ভাগ | SAD Part B §8.6 |
| Fallback resolver (menu, header, footer, template, logo) | SAD Part B §6.2 |
| Design token তালিকা | UI/UX Part B §2 |
| Global layout ও microsite bar | UI/UX Part B §4 |
| Page template ও homepage default | UI/UX Part B §5 |
| Component ও accessibility নোট | UI/UX Part B §6, §8 |
| Theme Developer Contract (সংক্ষিপ্ত) | UI/UX Part B §9 |
| Theme, menu, header / footer, page requirement | SRS Core C-06 (FR-THEME, FR-MENU, FR-HF, FR-PAGE) |
| Module-এর view ও layout নাম | প্রতিটি module-এর README; Module Guide §10 |
