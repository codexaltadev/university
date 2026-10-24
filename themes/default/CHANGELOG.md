# Changelog

## Unreleased (2.0.0, Phase 4)

- 4.1: Tailwind CSS 4.3.3 build (`vite.config.js`, `resources/`), tokens bound with
  `@theme inline reference`, default palette removed (T-2); styles for the module UI
  contract (`btn`, `badge`, `card`, `prose`, `table`, `accordion__*` …).
- 4.2: the theme becomes a copy of daffodilvarsity.edu.bd (owner 2026-10-17): DIU's floating
  header (utility links, mega menu, department dropdowns, search window, Apply button, Services
  grid, mobile menu), microsite name in Mina beside the logo, DIU's footer (blue band, "Start
  Your Journey", Subscribe Us, social links, link columns), back-to-top; Tailwind palette and
  type scale back for the copy (brand colours still tokens); no top bar / microsite bar;
  `<x-theme::icon>`; footer options; themed instant search and newsletter form.
- 4.3: DIU homepage as theme sections (theme.json "sections"): hero, mission, degree,
  scholarships, research, events, testimonials, alumni-news, link-button; components
  heading / button / link-button / reveal / counter; Alpine carousel and counter.
- 4.4b: DIU department and faculty pages as theme sections: page-hero, programs, notices,
  labs, partners, ambitions, microsite-news, event-cards, campus-services, faculty-stats,
  faculty-departments; slider controls hide when every card fits; `eye` icon.
- 4.4c: DIU program page — `modules/program/microsite/home`, on the Program module's `$page` (no PHP in
  the theme, T-1) (full-screen hero with figures, key facts and
  the video window; "Browse by Section" with Quick Links and Downloadable Materials, a mobile version with
  See More; AI Summary; Overview, Variations, Explore Your {category} with the text on hover, Eligibilities,
  Memberships, Facilitators slider, Future Career) and DIU looks for the tabs `tuition/tab`,
  `accreditation/tabs/program`, `testimonials/tabs/program`, `facilities/tabs/program`; the header's Apply
  button opens a program's own application link; `.program-content` tables; icons sparkles, download,
  file-text, clock, quote; `images/program/facility.jpg`.
- 4.4d: DIU `/faculties` — `modules/faculty/listing/{grid,list,card}` (hero, Browse by Section with
  Faculties / Deans / Department Heads tabs and links, faculty cards, deans and heads by faculty, Essential
  Campus Services); partials `diu/hero`, `diu/browse-sidebar`, `diu/quick-links`, `faculty/person-card`;
  `images/faculty-banner.jpg`.
- 4.4e: DIU `/programs` — `modules/program/listing/{card,list,table,detailed}` (Browse by Faculty tabs, UG / PG
  with counts, program table, phone drawer); option `programs_faculty`; `images/programs-banner.jpg`; icon `sliders`.
- 4.4f: page texts and links from Admin (Settings › Faculties / Programs, entities' Quick Links).
- 4.4g: page templates as DIU's article page — `partials/page/hero`, `partials/page/body` with the
  `<diu-rich-html>` element (Bootstrap 5.0.2 in a shadow root, `public/css`); option
  `page_hero_image`; `images/page-hero.jpg`.

## 1.0.0 — 2026-10-09

- First version: base layouts with every slot, four page templates, header / footer
  variants, dropdown / multi-level / mega menus, token-only CSS.
