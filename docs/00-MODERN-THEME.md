# Modern theme — কাজ দেওয়ার ধাপ

নতুন theme **University Modern** (`themes/modern`): modern ও minimal, যেকোনো university ব্যবহার করতে পারবে,
ভবিষ্যতে এই CMS-এর default theme। DIU-র হুবহু কপি বর্তমান `themes/default` যেমন আছে থাকবে।

প্রতিটি ধাপ = একটি cloud session (claude.ai/code, [00-CLOUD-SESSION.md](00-CLOUD-SESSION.md)) = একটি branch / Pull Request।
নিচের prompt হুবহু কপি করে দিন। প্রতিটি ধাপ শেষে Claude থামবে; "যাচাই করুন" তালিকা দেখে "oky" / "push" বলুন,
PR merge করুন, তারপর পরের ধাপ।

## মূল সিদ্ধান্ত (সব ধাপে প্রযোজ্য)

| বিষয় | সিদ্ধান্ত |
|---|---|
| নাম | slug `modern`, নাম "University Modern" |
| কার জন্য | যেকোনো university — theme-এ কোনো DIU নাম, link, লেখা, ছবি বা রঙ নয়; logo, রঙ, লেখা সব Admin থেকে |
| চেহারা | modern, minimal: সাদা জায়গা, শান্ত রঙ, বড় পরিষ্কার typography, card grid, হালকা animation |
| মান | WCAG AA, mobile-first, Lighthouse mobile ≥ 90, বাংলা ও English দুটোতেই সুন্দর |
| Content | আমাদের বিদ্যমান module data দিয়েই — নতুন content field নয়; থাকলে ফাঁক জানাবে |
| নিয়ম | default theme-এর মতো: no `@php`, রঙ শুধু token, logic module presenter-এ, ThemeChecker pass |
| Default হওয়া | শেষ ধাপে `config/platform.php` → `themes.default` বদলে; DIU site `default` theme-ই চালাবে |
| Branch | `theme/modern-m{n}` — `main`-এ সরাসরি নয় |

## ধাপ ০ — প্রস্তুতি (নিজে করবেন)

1. এই file push করা আছে কিনা দেখুন (cloud session GitHub থেকে পড়ে)।
2. claude.ai/code-এ environment তৈরি (setup script `bash scripts/cloud-setup.sh`)।
3. পছন্দের ২–৩টি modern university / CMS site-এর link রাখুন (ভাব নেওয়ার জন্য, কপি নয়) — ধাপ ১-এ দেবেন।

## ধাপ ১ — যাচাই: theme-এর পথে বাধা কোথায় (code বদল নয়)

```
docs/00-MODERN-THEME.md পড়ো — নতুন theme "modern"-এর ধাপ ১।
Theme Guide (docs/07-DevGuide/02-Theme-Development-Guide.md), SAD Part B §6/§8, UI/UX Part B পড়ো,
themes/default কী কী override করে দেখো। তারপর একটা audit লিখো docs/05-UIUX/02-UIUX-PartB-Frontend.md-এর
নতুন অংশে ("Modern theme — audit"):
- কোন module / kernel জায়গায় DIU-নির্দিষ্ট লেখা, link, ছবি বা ধারণা আছে যা অন্য university-র theme-কে বাধা দেবে
  (presenter, settings-এর default, theme.json section default, lang file)
- theme override না করলে কোন module পাতা কেমন দেখায় (module-এর নিজের view) — কোনগুলো ভাঙা / অসুন্দর
- Theme Guide-এর `uwms:theme:make` command code-এ নেই — কী লাগবে
- কোন content দেখানোর মতো data আছে (home, program, microsite, notice, event, admission, fee/waiver ...)
কিছু বদলাবে না; তালিকা ও সুপারিশ দিয়ে থামো।
```

**যাচাই করুন:** বাধার তালিকা বোধগম্য কিনা; কোনগুলো আগে ঠিক করা হবে বলে দিন (দরকার হলে "ধাপ ১ক: এগুলো ঠিক করো")।

## ধাপ ২ — Design (doc + mockup)

```
docs/00-MODERN-THEME.md-এর ধাপ ২। ধাপ ১-এর audit ধরে "modern" theme-এর design লিখো UI/UX Part B-এর
নতুন অংশে: design token (রঙ — Admin থেকে বদলানো যায় এমন primary/secondary/accent/surface, spacing,
radius, shadow), typography scale (English + বাংলা font, সব bundled, কোনো external font CDN নয়),
component (button, card, badge, tab, accordion, table, form, pagination, breadcrumb, hero, empty state),
layout (header + mega menu, footer, page templates, microsite template) আর পাতাগুলো: home, program listing +
program page, faculty / department microsite home, notice board + notice, news / event, admission + tuition +
waiver calculator, search, 404। Reference ভাব: <আপনার link>। প্রতিটি পাতার static HTML mockup দাও
(desktop + phone), DIU-র নাম নয় — "Example University" demo লেখা। শেষে আমার "oky"-এর জন্য থামো।
```

**যাচাই করুন:** mockup দেখে রঙ, font, ঘনত্ব, home-এর section ক্রম পছন্দ কিনা। মন্তব্য দিয়ে সংশোধন করান, তারপর "oky"।

## ধাপ ৩ — কাঠামো: scaffold command + theme চালু

```
docs/00-MODERN-THEME.md-এর ধাপ ৩। (১) Theme Guide §3-এর `php artisan uwms:theme:make {slug} [--from=] [--blank]`
command বানাও (platform/Console/Commands, test সহ)। (২) `uwms:theme:make modern --blank` দিয়ে themes/modern:
theme.json (generic — কোনো DIU default নয়), layouts (main + microsite), token থেকে Tailwind 4.3.3, bundled font,
header (mega menu, menu locations), footer, logo / identity Admin থেকে, 404 / 503। override না করা পাতা module-এর
view-এ চলবে। ThemeChecker / ThemeEngineTest pass, default theme-এর কোনো test ভাঙবে না। Admin থেকে theme বদলে
দুটোই চলে দেখাও। থামো।
```

**যাচাই করুন:** নিজের machine-এ branch এনে Admin › Themes-এ modern চালু করে site ঘুরে দেখুন; আবার default-এ ফিরলে DIU site অক্ষত কিনা।

## ধাপ ৪ — Homepage section

```
docs/00-MODERN-THEME.md-এর ধাপ ৪। ধাপ ২-এর design অনুযায়ী modern theme-এর homepage section: hero,
program finder, notice + event, news, faculties, admission / fee call-to-action, rankings / achievements,
testimonials, gallery, partners। প্রতিটি Admin-এর homepage builder থেকে চালু-বন্ধ ও লেখা বদলানো যায়, data module
source থেকে, কোনো hard-coded লেখা নয়। বাংলা ও phone-এ দেখাও। থামো।
```

## ধাপ ৫ — মূল পাতা (module view override)

একবারে বড় না করে দুই ভাগে:

```
docs/00-MODERN-THEME.md-এর ধাপ ৫ক। modern theme-এ override: program listing + program page, faculty /
department / program microsite home, notice board + notice, news ও event (listing + detail), search। Presenter
(Support\*Page) পুনর্ব্যবহার করো; কিছু কম পড়লে presenter-এ যোগ করো, theme-এ logic নয়। থামো।
```

```
ধাপ ৫খ। admission, tuition fees + fee calculator, waiver calculator ও scholarship, documents (flipbook),
gallery, office microsite, administration / people, FAQ, contact। Calculator-এর হিসাব বদলাবে না। থামো।
```

## ধাপ ৬ — বাকি module ও page template

```
docs/00-MODERN-THEME.md-এর ধাপ ৬। বাকি সব module-এর পাতা modern design-এ (অথবা module view যথেষ্ট ভালো হলে
তালিকা দিয়ে রেখে দাও), page templates (standard, full width, left / right sidebar, landing), HTML block-এর
editor_css। থামো।
```

## ধাপ ৭ — মান যাচাই

```
docs/00-MODERN-THEME.md-এর ধাপ ৭। modern theme-এর জন্য Browser test (JS error / ভাঙা ছবি নেই, desktop /
phone / বাংলা), axe (critical / serious শূন্য), Lighthouse mobile ≥ 90, 360–1440px-এ পাশে scroll নেই,
print.css, reduced motion, screenshot.png, version 1.0.0। default theme-এর test-ও pass। ফল জানিয়ে থামো।
```

**যাচাই করুন:** এখানে full Browser suite চলবে — এটা আপনি নিজে অনুমতি দিচ্ছেন (সময় লাগে)।

## ধাপ ৮ — Default করা ও doc

```
docs/00-MODERN-THEME.md-এর ধাপ ৮। নতুন install-এ modern = default theme (config platform.themes.default,
BRL-THEME-01), বর্তমান DIU install `default` theme-এই থাকবে — দুটো পথই test করো। Theme Guide-এ modern-কে
উদাহরণ করো, User Manual-এ theme বদলানো ও রঙ / logo Admin থেকে বদলানোর অংশ, HANDOFF আপডেট। থামো।
```

## মনে রাখবেন

- একসাথে দুটো session-এ একই theme-এ কাজ দেবেন না — conflict হবে।
- প্রতিটি ধাপ merge হওয়ার পরেই পরের session শুরু করুন (নতুন session `main` থেকে শুরু হয়)।
- DIU-এর live চেহারা (default theme) এই কাজে বদলাবে না; বদলালে সেটা ভুল — Claude-কে জানান।
- Credit বাঁচাতে: ধাপ ১–৩ ছোট ও দ্রুত; ধাপ ৫–৭ বড়।

---

## অগ্রগতি — একটানা session (branch `theme/modern`, 2026-11-02)

Owner-এর নির্দেশে ধাপ ১ → ৮ একটি session-এ, প্রশ্ন ও অনুমোদনের বিরতি ছাড়া; প্রতিটি ধাপ শেষে commit ও `theme/modern` push।
সিদ্ধান্তগুলো নিচের "Decisions" তালিকায়।

| ধাপ | অবস্থা | কোথায় |
|---|---|---|
| ১ Audit | ✔ | UI/UX Part B §11 |
| ২ Design + mockup | ✔ | UI/UX Part B §12, `docs/05-UIUX/mockups/modern/index.html` |
| ৩ Scaffold command + theme চালু | ✔ | `uwms:theme:make` (`platform/Console/Commands/ThemeMakeCommand.php`, `tests/Feature/Kernel/ThemeMakeTest.php`), `themes/modern` chrome, `tests/Feature/Theme/ModernThemeTest.php`, `npm run build:theme:modern` |
| ৪ Homepage section | ✔ | theme.json-এ ১৩টি section (hero, program-finder, notices, events, news, faculties, programs, stats, achievements, admission-cta, testimonials, gallery, partners) + core section-এর view (`sections/core-*`) |
| ৫ক মূল পাতা | ✔ | override: notice board + notice, news listing + post, event, program card / listing (card layout) / program page, search (`views/modules/*`, `views/search.blade.php`) |

## Decisions

| # | সিদ্ধান্ত | কারণ |
|---|---|---|
| D-1 | সব ধাপ এক branch `theme/modern`-এ (ধাপভিত্তিক `theme/modern-m{n}` নয়) | Owner-এর এই কাজের নির্দেশ |
| D-2 | Cloud machine-এ PHP 8.4 পাওয়া যায়নি (ondrej PPA network policy-তে বন্ধ) — PHP 8.3 + `composer install --ignore-platform-req=php` দিয়ে test | Project code-এ 8.4-only syntax নেই; owner-এর machine-এ 8.4-এ আবার চালানো উচিত |
| D-3 | Module Settings-এর DIU default লেখা (§11.1) এই কাজে বদলানো হবে না | Default বদলালে DIU live site-এর লেখা বদলে যেতে পারে (scope: default theme-এর চেহারা অপরিবর্তিত); আলাদা ধাপ সুপারিশ |
| D-4 | Design: Inter + Noto Sans Bengali (bundled, npm `@fontsource-variable`), primary `#1D4ED8`, secondary `#0F172A`, accent `#F59E0B`, pill button, radius 8/12/20 | Modern, শান্ত, generic; owner reference link দেননি — নিজের সুপারিশ |
| D-5 | Mockup = static HTML (`docs/05-UIUX/mockups/modern/`), header / footer একটি ছোট JS দিয়ে সব পাতায় | পর্যালোচনার জন্য যথেষ্ট, theme code নয় |
| D-6 | `uwms:theme:make` blank = validator-এর ন্যূনতম file + Tailwind source + Vite config; `--from` কপি করে build output বাদ দিয়ে; build নিজে চালায় না (`npx vite build --config themes/{slug}/vite.config.js` দেখায়) | Command-এ Node চালানো platform-এর কাজ নয় (server-এ npm চলে না, T-6) |
| D-7 | Modern theme-এ Tailwind palette বন্ধ (`--color-*: initial`) — শুধু token রং; package.json-এ `build:theme:modern` / `dev:theme:modern` | Admin-এর রং পুরো site-এ নিশ্চিত; ভুল করে `gray-500` লিখলে build-এ আসে না |
| D-8 | Header variant `modern` (utility row + mega panel) ও `minimal`; footer `columns` ও `simple`; header button ও footer লেখা theme option, default খালি | Generic: কোনো university-র link default-এ নয় |
| D-9 | Homepage section = theme.json section, module section-কে `source` করে (data module থেকে, লেখা / link builder-এর field থেকে); শিরোনাম খালি থাকলে theme-এর lang (en / bn) থেকে; "View all" link default `{base}/notices` ইত্যাদি (microsite-এ নিজের ঠিকানা) | Theme-এ PHP / `Modules\` নিষেধ, তাই URL module-এর `url()` বা builder-এর field থেকে; দুই ভাষাতেই শিরোনাম |
| D-10 | Slider module-এর hero view override করা হয়নি — module-এর নিজের view token দিয়ে চলে; modern-এর নিজের `theme.hero` ও core `core.hero` আছে | সময়; module view ভাঙা নয় |
| D-11 | Screenshot-এর জন্য আলাদা throwaway DB `university_demo` (`.env.demo`, git-এ নয়) — dev DB-তে demo row নয় | CLAUDE.md: dev DB-তে demo data নিষেধ |
| D-12 | Events listing (calendar / timeline layout সহ), faculty / department microsite home, program listing-এর list / table / detailed layout override হয়নি — module view + modern-এর card ও contract style দিয়ে চলে | Module view গোছানো; calendar layout জটিল, ঝুঁকি কম রাখা |
| D-13 | Office composer-এর `$office` (office-এর নিজস্ব frame) modern-এ আলাদা frame পায় না — সাধারণ page hero দিয়ে দেখায় | Office microsite frame পরে (ধাপ ৫খ-এর বাকি) |
