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
- 4.4h: DIU office page — `modules/office/microsite/home` and `modules/office/team` on the Office
  module's `$page` (Overview + contact cards, Head's message, Services, Office Team, Sections,
  FAQs), `partials/office/member`; `diu/browse-sidebar` takes an optional `$width`.
- 4.4i: DIU lab pages — `modules/lab/listing` (/labs = Laboratory Facilities by faculty, a
  department's labs = its labs + the first one) and `modules/lab/microsite/home` on the Lab
  module's `$page`; `partials/lab/sidebar`, `partials/lab/details`; `wrench` icon.
- 4.5a: DIU events — `modules/events/index` (search, Upcoming / Archive / Calendar, Select
  Faculty, category chips, cards with dates) and `modules/events/show` (Event Details beside the
  event) on the Events module's `$page`; `partials/diu/pagination`; `images/event-hero.jpg`;
  icons `tag`, `calendar`, `archive`, `filter`.
- 4.5b: DIU Notice Board — `modules/notice/index` (All Departments, faculty tabs with their
  departments, phone list, search and archive, date-box cards, "Page x of y") and
  `modules/notice/show` (Notice Details: date, views, category, View/Download, files with
  titles and PDF preview) on the Notice module's `$page`.
- 4.5c: DIU gallery — `modules/gallery/index` and `modules/gallery/show` through
  `partials/gallery/page`: "Browse by Category" with each category's albums, the chosen
  category's or album's pictures (Media Library items, 20 a page) with a lightbox, album cards,
  videos, on the Gallery module's `$page`.
- 4.5d: DIU News (news.daffodilvarsity.edu.bd) — `modules/news/index` ("Latest" with the two
  newest, overlay cards, All News / Search / Filter by Category / archive) and
  `modules/news/show` (picture with category, date and hits, text, "Explore the Inspiring
  Stories" + three more); `partials/news/overlay`, `partials/news/explore`.
- 4.5e: DIU testimonials — `modules/testimonials/index`: two columns of cards (words, photo,
  name, title, type, Read more) opening the whole testimonial in a window (#testimonial-{id});
  `images/testimonials-banner.jpg`.
- 4.5e+: testimonials ≠ visitors (owner) — section `visitors` (DIU's "Meet our distinguished …"
  slider, from the chosen visitors) beside `testimonials` ("What People Say", the chosen
  testimonials); `modules/international/visitors` (/visitors in DIU's testimonial look); a
  testimonial's country shown when from abroad. Section `testimonials` becomes DIU testimonial
  cards (three columns, country when abroad), each opening its testimonial on /testimonials.
- 4.5f: DIU downloads, calendar, achievements, links — `modules/documents/index` / `show`
  (Digital Brochures cards: thumbnail, kind, View Flipbook / View PDF, download),
  `modules/calendar/index` / `show` with `partials/calendar/card` (Registrar card: session
  switcher, Academic Year / Duration / Total Events, coloured type badges),
  `modules/achievements/index` / `show` (event-card look, level switch, Details sidebar),
  `modules/links/index` (tiles or list per group).
- 4.5g: announcements — `modules/announcement/slots/popup` (DIU's greeting popup: picture,
  text, dates, Maybe Later / Explore Now), `slots/announcement` (bar and countdown tiles in the
  navy → blue gradient) and `slots/ticker` (navy strip, "Latest" tab, endless run); above the
  homepage hero (z-40); icon `megaphone`.
- 4.6a: DIU administration — `modules/administration/directory` (sections with their people,
  Quick Links, search, DIU's table and phone cards) and `modules/administration/profile` (blue
  card, contact boxes, biography, roles); `partials/administration/photo`; `partials/diu/browse-sidebar`
  items may be the page shown (`current`) and drop the arrow (`arrow` false).
- 4.6b: Faculty Members — `modules/facultymember/directory` (sections with each faculty's
  teachers, search with department and designation, DeanCard-style cards) and
  `modules/facultymember/sections/list`; `partials/faculty-member/card` (photo or initials,
  "View Profile" to the faculty website in a new tab — no profile page here).
- 4.6c: DIU committees — `modules/committee/index` and `show` through `partials/committee/page`
  ("Committee Categories" sidebar with member counts, blue-headed members table, phone cards
  and select; /committees opens the first committee).
- 4.6d: DIU alumni — `modules/alumni/index`, `show`, `submit`, `sections/pride` with
  `partials/alumni/card` (DIU's AlumniList card: 3:4 photo, briefcase / building lines,
  department badge, Pride).

## 1.0.0 — 2026-10-09

- First version: base layouts with every slot, four page templates, header / footer
  variants, dropdown / multi-level / mega menus, token-only CSS.
