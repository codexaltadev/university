# Changelog

## 2.1.0 — 2026-10-30

- Office page as DIU's registrar-office / proctor-office: with a cover picture, the picture and
  the overview under it; the office's highlights as cards (About the Office then); Quick Service
  icon tiles; contact facts moved to `partials/office/facts`. Icons `folder`, `handshake`,
  `target`, `scale` added.
- An office's pages, team and notice board open in the office's frame as DIU's
  /registrar-office/{slug}: `<x-theme::office-frame>` (office hero, "Browse by Section" with the
  page shown marked, Quick Links); `partials/office/page` (title, text card, the pages under it
  as cards) from the default page template; `partials/office/notices` (DIU's blue "Notices &
  Announcements"). In the side list only links leaving the site open a new tab with ↗.
- `modules/office/blocks/heads` (Honour Board: the head now in a blue card, former heads on a
  timeline with their terms, initials without a photo) and `modules/office/blocks/contact`
  (location / phone / email / hours cards, campus and map, who to contact).
- `modules/calendar/blocks/sessions`: the Academic Calendar block in DIU's card, the session
  dropdown switching calendars in place (`partials/calendar/card` gains `$switch`).
- Administration: a section at its own address (`/administration/{slug}`) gets its name in the
  breadcrumb. "Browse by Section" everywhere: a full address of this site opens in the same tab
  without ↗ (before, every `http…` link opened a new tab).

## 2.0.0 — 2026-10-30 (Phase 4)

MAJOR: the theme becomes a copy of daffodilvarsity.edu.bd (4.2–4.4), a different look and markup
throughout; check any copy of the theme or view override made from 1.0.0.

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
- 4.6e-1: DIU CSR — `modules/csr/index` (featured activity, impact numbers, "Our Approach"
  picture cards, CSR Report; on dark navy "Where We Create Impact" with search, category and
  focus-area cards, "Stories of Change") and `modules/csr/show`.
- 4.6e-2: DIU partners — `modules/partners/index` (DIU's /mps logo cards with type tabs) and
  `modules/partners/discounts` (DIU's /discount-partners: categories sidebar, blue head with
  search, table with "Show More", phone cards); every partner card tagged with its type.
- 4.6e-3: DIU rankings — `modules/accreditation/rankings` ("All Ranking": logo, title, rank,
  year, text; Quick Links); `modules/accreditation/index` and `show` (accreditations and
  memberships as cards tagged with their category).
- 4.6e-4: DIU international — `modules/international/home` (numbers, featured partners),
  `partners` (DIU's International Linkage: Browse by Section, country picker, university
  cards, table layout), `conferences` and `conference` (picture cards); flag pictures.
- 4.6e-5: facilities — `modules/facilities/index` (category pills, DIU's program-page facility
  picture cards in a grid) and `show`.
- 4.7a: DIU's Waiver Calculator (/tuition-fee-calculator) — `modules/waiver/calculator/page` (hero
  "Calculate Your Fees", Important Notice card with View Details) and `modules/waiver/livewire/waiver-calculator`
  (Basic Information, waiver category tiles with icons and a check badge — a select on phones, Program & Details
  with duration / credits / total, Academic Results with Golden GPA, Additional Information, "Calculate My Fees";
  result panel: Congratulations!, the waiver %, Program, Fee Summary, Payable During Admission, total after
  waiver, Print / Reset); icons award, star, shield, trending-up, check, zap, banknote, alert-circle, home.
  Then shorter (owner): one card with three numbered steps, a program search grouped by faculty, one-line
  waiver categories (first 6, "Show all") with their own description, Golden GPA only at GPA 5, gender buttons,
  an empty "Your fee estimate" card until Calculate, phones scrolled to the result.
- 4.7b-1: DIU admission — page blocks each in `partials/page/block` (page text in DIU's article look, a module's
  block in the theme's), `partials/page/blocks` for templates; the with-sidebar template as DIU's /admission (excerpt
  in the hero, `partials/menus/browse` "Browse by Section (NN)", Quick Links); `modules/admission/blocks/*` (notice
  card, links, outline buttons, steps, contacts) and `modules/admission/livewire/subscribe-form` (NotificationForm).
- 4.7b-2: DIU admission eligibility — `modules/program/blocks/eligibility` ("Faculty of …", round level buttons with
  counts, Full Program Name / Short Tag rows that open the eligibility and DIU's Quick Links); `partials/menus/browse`
  headings fold their children.
- 4.7b-3: DIU admission pages — `modules/admission/result` and `livewire/result-lookup` (/admission-test-result),
  `modules/admission/contact` (/admission-contact), `modules/admission/circulars` / `circular` with
  `partials/admission/circular`.
- 4.7c: DIU tuition fees — `modules/tuition/listing/grouped` (notice, Local / International, level tabs and
  search, folding faculty cards with the blue-headed table or phone cards, yearly columns for international fees,
  Quick Links, "Need Assistance?").
- 4.7d: DIU scholarships — `modules/scholarship/listing` and `show` with `partials/scholarship/item`
  ("Browse by Section", Quick Links, the texts in DIU's article look, folding scholarships by category).
- 4.7e: DIU transport — `modules/transport/index` (Transport Pass, schedule card, stop search, Regular / Shuttle /
  Friday route cards with greyed past trips and the stops line, Transport Fees).
- 4.7f: DIU FAQ — `modules/faq/index` (centred title, search, categories with counts, question cards opening
  with + / −).
- 4.7g: search — `search` (DIU-look results page with shortcuts, module tabs, result cards, popular searches) and
  `livewire/instant-search` (excerpts, keyboard, recent and popular searches, shortcuts).
- 4.7g-2: newsletter — `modules/newsletter/index` (sign-up card, past issues) and `issue`.
- 4.7h: `errors/404` (Page not found with search, Go Back, Home), `modules/settings/maintenance` (DIU's Under
  Maintenance with duration and help cards), component `bare` (a page without menus).
- 4.8a–b: browser tests (Pest Browser, every module page and record on desktop / phone, English / Bangla)
  and axe: one `<main>`, link names, contrast fixes.
- 4.8c: Lighthouse budgets (`lighthouserc.cjs`): Manrope, Hind Siliguri and Mina bundled (theme.json
  "fonts", no Google Fonts request), size-adjusted "Manrope Fallback" so text does not jump when the font
  arrives (CLS), small hero pictures for phones (`*-sm.webp`), phones keep the hero poster instead of the video,
  pictures in page text load lazily, `--header-h` defaults so heroes do not jump.
- 4.8d: checklist — `resources/css/print.css` (content only, black on white, no header / footer / windows,
  heroes without pictures, link addresses in text, counters at their number); reduced motion also stops the
  hero video and smooth scrolling; the page never scrolls sideways (body clips sliding content; admission
  contact cards, pagination on phones); logo and menu no longer overlap between 1024 and 1279px (narrower
  menu links, the microsite name wraps and is 18px below 1536px); `screenshot.png` is the real homepage.
- DIU `/departments` — `modules/department/listing/{grid,list,card}` through `partials/department/listing` (hero
  "All Departments", Browse by Faculty with Browse ALL and Quick Links, the faculty's text and "Visit Faculty Page",
  grey department cards; the Program Filters drawer on phones); text, picture and links from Settings › Departments.

## 1.0.0 — 2026-10-09

- First version: base layouts with every slot, four page templates, header / footer
  variants, dropdown / multi-level / mega menus, token-only CSS.
