# Database Design
## DB-05 — Website Content Modules (M-18 থেকে M-30)

| বিষয় | তথ্য |
|---|---|
| Document | Database Design DB-05 |
| Version | 1.12 — 2026-10-11 (v1.1): §13 FAQ (question / answer ভাষা অনুযায়ী, `published_at` নেই — `is_active`; সময় লাগলে `active_at`), §18 FAQ mapping; 2026-10-16 (v1.2): §2 common column `active_at`, §3 News (website, homepage section, migration); 2026-10-16 (v1.3): §4 Events (Campus ও Gallery আসা পর্যন্ত লেখা venue ও Media gallery, website, section); 2026-10-16 (v1.4): §5 Notice (`active_at`, board / archive, attachment title, website, section); 2026-10-16 (v1.5): §6 Testimonials (`published_at` নেই, person, website, section); 2026-10-16 (v1.6): §7 Gallery (ছবি Media Library-তে, album-এ শুধু reference — `gallery_items` বাদ, link video `gallery_videos`; `album_type` বাদ; `event_images` → Event-এর Media); 2026-10-16 (v1.7): §8 Slider / Hero (এক slide = স্থির hero, অনেক = slider, background video; `active_at` / `expires_at`, `body` বাদ, `video_on_mobile`; section `slider.hero`, fallback); 2026-10-16 (v1.8): §9 Achievements (`active_at`, অর্জনকারী Person Picker, `team_name`, `related_post_id`, website, section, metric); 2026-10-16 (v1.9): §12 Documents (`document_type` তালিকা + `documents.category`, `session_label`, `allow_download`, `active_at`; নিজস্ব PDF.js flipbook); 2026-10-17 (v1.10): §14 Important Links ("Show in", website, section, footer block, সাপ্তাহিক check); 2026-10-17 (v1.11): §15 Announcement Widgets (`active_at` / `expires_at`, `pages` home / all / selected, `after_end`, layout slot); 2026-10-25 (v1.12): §6 Testimonials — `country`, `source_key`, ধরন `faculty` ও `visitor`, homepage-এ শুধু বাছাই করা; visitor আলাদা (DB-06 §6) |
| Date | 2026-10-08 |
| Status | ✔ Confirmed |
| Based on | [DB-01](01-DB-Core-Kernel.md), [DB-02](02-DB-Core-SiteBuilding.md), [SRS Part 4](../02-SRS/04-SRS-Content.md), BRD v1.6 (Q-26), `project_diu` (row সংখ্যা `COUNT(*)`, 2026-10-08) |

> Convention: DB-01 Section 1, DB-02 Section 1। সব content table-এর Slug, Media, SEO, Category / Tag, Relation, Highlight, View count, Search kernel থেকে।

---

## 1. Main Site-এ প্রকাশ ও Highlight (BRD Q-26)

### 1.1 সিদ্ধান্ত

বর্তমান system-এ `is_home` আসলে "main site-এও দেখাবে কিনা" (notice-এর ৩,৩৯১টির মধ্যে ২,৮০৮টি, event-এর ৫৬৩টির মধ্যে ৩৩৪টি)। নতুন system-এ:

* **কোন content main site-এ (main `/notices`, `/news`, main homepage) দেখাবে, তা শুধু Admin ঠিক করবেন** (Q-26)।
* Department / Faculty / Program / Office / Lab user-রা শুধু নিজের entity-তে প্রকাশ করতে পারবেন।
* Highlight আলাদা বিষয় (Part 4 Section 1.1) — main site-এ প্রকাশিত content-এর মধ্যে কোনগুলো homepage-এ সামনে থাকবে।

### 1.2 Data-তে কীভাবে

`core_relations`-এর Main Site row (`target_type = 'site'`, `target_id = 0`)-এর অর্থ এখন: **"main site-এ প্রকাশিত"**। Highlight সেই row-এরই `is_highlighted` column।

| অবস্থা | `core_relations` |
|---|---|
| শুধু CSE-তে | `department:12` row |
| CSE ও main site-এ | `department:12` row + `site:0` row |
| Main site-এ, homepage highlight সহ | `site:0` row, `is_highlighted = 1` |

| Query | Filter |
|---|---|
| Main site listing (`/notices`) ও main homepage section | `EXISTS core_relations (target = site:0)` |
| CSE microsite listing | `EXISTS core_relations (target IN CSE ও descendant)` |

**Permission:** `site:0` row তৈরি / মুছে ফেলা শুধু custom permission `PublishMain:{Resource}` থাকা user (default: Admin, Super Admin)। Admin-এর নিজের তৈরি content-এ "Main site-এ প্রকাশ" default চালু; entity user-এর form-এ এই option দেখায় না।

> এটি SRS Part 4 Section 1.1 ও SAD Part B Section 3.1-এর সংশোধন (Section 13 দেখুন)।

---

## 2. Module ও Prefix

| Module | Slug | Prefix | Attachable | Dependency (FK নীতি) |
|---|---|---|---|---|
| M-18 News | `news` | `news_` | ✔ | — |
| M-19 Events | `events` | `events_` | ✔ | Campus, Gallery (optional → index) |
| M-20 Notice | `notice` | `notice_` | ✔ | — |
| M-21 Testimonials | `testimonials` | `testimonials_` | ✔ | — |
| M-22 Gallery | `gallery` | `gallery_` | ✔ | Events (optional → index) |
| M-23 Slider / Banner | `slider` | `slider_` | ✔ | — |
| M-24 Achievements | `achievements` | `achievements_` | ✔ | — |
| M-25 Publications *(Optional)* | `publications` | `publications_` | ✔ | — |
| M-26 Research *(Optional)* | `research` | `research_` | ✔ | Publications (optional → index) |
| M-27 Documents | `documents` | `documents_` | ✔ | Session, Program (optional → index) |
| M-28 FAQ | `faq` | `faq_` | ✔ | — |
| M-29 Important Links | `links` | `links_` | — | — |
| M-30 Announcement Widgets | `announcement` | `announcement_` | ✔ | — |

### সব Content Table-এ Common Column (আলাদা করে লেখা হয়নি)

| Column | Type | বর্ণনা |
|---|---|---|
| ~~published_at~~ → `active_at` *(v1.2, owner 2026-10-10/11)* | TIMESTAMP N, IX | নতুন module-এ `published_at` নেই: দেখানো হবে কিনা = `is_active`; যে module-এ সময় ধরে প্রকাশ লাগে (News, Notice, Events …) সেখানে `active_at` — খালি বা অতীত = দেখায়, ভবিষ্যৎ = scheduled; তারিখ দেখানো ও ক্রম এটি দিয়ে (kernel `HasActiveAt`)। `status` column নেই (BRD Q-27) |
| expires_at | TIMESTAMP N | যেখানে প্রযোজ্য (FR-GEN-13) |
| sort_order | INT UNSIGNED | যেখানে ম্যানুয়াল ক্রম লাগে |
| ⚙ | | `platformColumns()` |

প্রতিটি content table-এ IX-5: `(is_active, deleted_at, active_at)` (`active_at` থাকলে)।

---

## 3. M-18 News — `news_posts` — B

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| title | JSON | T | |
| subtitle | JSON | N, T | |
| excerpt | JSON | N, T | |
| body | JSON | N, T | Rich text; image / video embed / quote / table |
| author_user_id | BIGINT UNSIGNED | N, FK users (set null) | FR-NEWS-06 |
| author_label | JSON | N, T | Free text ("Public Relations Office") |
| video_url *(v1.2)* | VARCHAR(2048) | N | YouTube / Drive embed (SRS Data: video embed) |
| + common | | `active_at` *(v1.2)* |

Media: `featured_image`, `gallery`। Vocabulary: `news.category`, shared tags।

*(v1.2, build-এর আগে)* কোথায় দেখাবে = "Show in" + main site (§1, `PublishMain:Post` শুধু Admin); "Featured" = সেই জায়গার Highlight (`core_relations.is_highlighted`, until সহ — AC-NEWS-04), আলাদা column নয়। Website: `/news` (main site-এ প্রকাশিত), `/{entity}/news` (সেই entity ও নিচের), `/news/{slug}`; filter category, tag, বছর / মাস archive (FR-NEWS-05); listing layout grid / list / cards / magazine, details standard / full-width (FR-NEWS-03)। Homepage section (FR-NEWS-04): `news.latest` (সর্বশেষ), `news.featured` (highlight, slider / grid), `news.by-category` (একটি বা কয়েকটি category)। Page block "News"। View count (P-17)। Migration: `blog_posts` (১) ও `lab_news` (১) → step S16N (Lab-এর news Lab module-এর পরে)।

---

## 4. M-19 Events — `events_events` — B (Assignable: speaker)

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| title | JSON | T | |
| summary | JSON | N, T | |
| body | JSON | N, T | |
| starts_at | DATETIME | IX | বাধ্যতামূলক (FR-EVT-01) |
| ends_at | DATETIME | N, IX | ≥ starts_at |
| is_all_day | TINYINT(1) | | |
| ~~campus_id~~ | | | *v1.3:* M-08 Campus এলে; এখন `venue`-এ লেখা |
| venue | JSON | N, T | Building / room / বাইরের স্থান |
| online_url | VARCHAR(2048) | N | FR-EVT-10 |
| organizer_label | JSON | N, T | |
| registration_url | VARCHAR(2048) | N | |
| registration_ends_at | DATETIME | N | FR-EVT-06 |
| ~~album_id~~ | | | *v1.3:* M-22 Gallery এলে; এখন Media `gallery` (FR-EVT-05 — Media Library থেকে) |
| + common | | `active_at` *(v1.3)* — কখন থেকে website-এ দেখাবে (event-এর তারিখ নয়) |

IX: `(starts_at, is_active)` — Upcoming / Past (FR-EVT-02)। Speaker: `core_assignments` (`role_key = speaker`)। Media: `featured_image`। Vocabulary: `events.category`।

*(v1.3, build-এর আগে)* অবস্থা তারিখ থেকে: Upcoming = `starts_at` ভবিষ্যৎ, Ongoing = শুরু হয়েছে কিন্তু `ends_at` (না থাকলে all-day-এ দিনের শেষ, নইলে শুরু) পার হয়নি, Past = বাকি (AC-EVT-01)। কোথায় দেখাবে = "Show in" + main site (§1, Admin)। Speaker / moderator = kernel `PeopleManager` (role `speaker`, `moderator`)। Website: `/events` ও `/{entity}/events` — tab Upcoming (ongoing সহ, শুরুর ক্রমে) / Past (নতুন আগে), layout list / cards / timeline / calendar (month), category filter; `/events/{slug}` — তারিখ ও সময়, venue, online link, organizer, speaker, registration (শেষ তারিখ পার হলে "Registration closed"), gallery, `.ics` ও Google Calendar link (FR-EVT-07), JSON-LD `Event` (FR-EVT-09)। Homepage section: `events.upcoming` (countdown ঐচ্ছিক), `events.past` (Past highlights — সেই জায়গার highlight, না থাকলে সর্বশেষ past)। Migration S16E: `events` (৫৬৩) — `event_date` + `event_time` → `starts_at` (সময় নেই = all-day), department → relation, `is_home` → main site, lab → Lab module-এর পরে; `event_images` (৭৭) ও ছবি S03-এর পরে।

---

## 5. M-20 Notice — `notice_notices` — B

| Column | Type | Key / N | Default | বর্ণনা |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | PK | | |
| title | JSON | T | | |
| reference_no | VARCHAR(100) | N, IX | | Notice নম্বর (FR-NOT-04 search) |
| body | JSON | N, T | | |
| is_pinned | TINYINT(1) | IX | 0 | FR-NOT-02 |
| is_urgent | TINYINT(1) | | 0 | |
| + common | | `active_at` *(v1.4)*, `expires_at` |

IX: `(is_pinned, active_at)` *(v1.4)*। Attachment: Media usage `field = attachments`, প্রতিটির title `core_media_usages.meta.title`-এ (FR-NOT-01)। Vocabulary: `notice.category`।

*(v1.4, build-এর আগে)* `expires_at` = board-এ থাকার শেষ: পার হলে Notice Board, homepage ও ticker থেকে সরে, কিন্তু archive-এ ও নিজের পাতায় থাকে (FR-NOT-03) — তাই kernel `HasExpiry` নয় (সেটি website থেকে পুরো লুকায়), Notice-এর নিজস্ব scope `onBoard()` / `archived()`। Attachment form = kernel `AttachmentList` (প্রতি সারিতে file ও ভাষা অনুযায়ী title; `MediaUsages::sync(..., meta)`), Documents module-ও এটি নেবে। কোথায় দেখাবে = "Show in" + main site (§1)। Website: `/notices` ও `/{entity}/notices` — pinned আগে, তারপর নতুন; filter category, entity (main board-এ), তারিখের পরিসর, search (title ও reference no.), `?archive=1`; layout table / list / cards; "New" badge (Settings › Notice: কত দিন, default ৭ — FR-NOT-07); urgent আলাদা রঙে (FR-NOT-02); `/notices/{slug}` — PDF attachment inline preview ও download (FR-NOT-05)। Homepage section `notice.board` (সর্বশেষ N, pinned আগে) ও `notice.ticker` (FR-NOT-06)। Migration S16 (notice অংশ, S16T): `notices` (৩,৩৯১) — department relation, `is_home` → main site, `view_counter` → view count, category; `notice_files` (৩,৫৩২) S03-এর পরে।

---

## 6. M-21 Testimonials — `testimonials_testimonials` — B (Assignable: person)

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| quote | JSON | T | |
| testimonial_type | VARCHAR(20) | IX | `student` / `alumni` / `parent` / `faculty` / `employer` / `partner` / `visitor` *(v1.12: faculty, visitor)* |
| country | CHAR(2) | N | *(v1.12)* লেখক কোন দেশের (ISO 3166, kernel `Platform\Support\Countries`); খালি = বাংলাদেশ, অন্য দেশ = আন্তর্জাতিক মতামত |
| rating | TINYINT UNSIGNED | N | ১–৫ |
| video_url | VARCHAR(2048) | N | |
| source_key | VARCHAR(100) | N, UQ | *(v1.12)* যে record থেকে তৈরি (`international.visitor:12` — অতিথির কথা মতামত হিসেবেও), একটি source-এ একটি |
| + common | | `sort_order` *(v1.5: `published_at` নেই — সময় ধরে প্রকাশ লাগে না, `is_active` যথেষ্ট)* |

ব্যক্তি (নাম, পদবি, প্রতিষ্ঠান, ছবি): `core_assignments` (`role_key = author`) — Alumni বা অন্য person source, অথবা manual person (FR-TEST-01)।

*(v1.5, build-এর আগে)* ঠিক একজন ব্যক্তি (form-এ Person Picker + role-এর নিজস্ব নাম, যেমন "Parent of a CSE student")। কোথায় দেখাবে = "Show in" + main site (§1); Program-এর testimonial তার page-এ tab। Website: `/testimonials` ও `/{entity}/testimonials` — `?type=` filter, layout slider / grid / masonry / video (FR-TEST-02); ক্রম drag (FR-TEST-04)। Homepage section `testimonials.list` "What People Say" — ধরন filter, সেই জায়গার testimonial (FR-TEST-03); homepage seeder-এর `testimonial.list` → `testimonials.list`। Migration S17 (testimonial অংশ, S17M): `testimonials` (২০) ও `mba_testimonials` (৫) → Program relation, ব্যক্তি manual person।

*(v1.12, owner 2026-10-24/25)* **Testimonial ≠ Visitor।** Testimonial = যে কারো অভিজ্ঞতা (শিক্ষার্থী, alumni, অভিভাবক, শিক্ষক, নিয়োগকর্তা, অংশীদার — দেশের বা বিদেশের, `country`); Visitor (DB-06 §6) = DIU-তে আসা অতিথি (দেশের বা বিদেশের) ও তাঁর মন্তব্য। দুটোর data আলাদা, copy নয়; অতিথির কথা চাইলে **"Show as a testimonial too"** দিয়ে একবার মতামত হিসেবেও (`testimonial_type = visitor`, `source_key`)। Homepage section `testimonials.list`-এর default **শুধু highlighted** (`Section::defaultSourceMode()`; builder চাইলে "highlighted first")। যেকোনো microsite (department, faculty, program, office …) নিজের testimonial দিতে পারে ("Show in") ও নিজের homepage-এ শুধু নিজের highlighted গুলো দেখায়। Migration: চালু testimonial main site-এও (DIU `/testimonials`-এ সব দেখায়)।

---

## 7. M-22 Gallery

*(v1.6, build-এর আগে — owner 2026-10-16: "image যেন আবার individual না হয়")* ছবি ও upload করা video **Media Library-তেই থাকে**; album শুধু reference রাখে — আলাদা item table নেই, copy নেই (BRL-GAL-01)। একই ছবি News, Event, Album সবখানে একটাই file।

### 7.1 `gallery_albums` — B (attachable)

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| title | JSON | T | |
| description | JSON | N, T | |
| series_type | VARCHAR(20) | N, IX | `documentary` / `lecture_series` (FR-GAL-04); খালি = সাধারণ album |
| event_id | BIGINT UNSIGNED | N, IX | Events optional (FR-GAL-06), FK নয় |
| occurred_on | DATE | N | কবের ছবি (listing-এর ক্রম, নতুন আগে) |
| sort_order | INT UNSIGNED | | |
| + common | | | `is_active`, `active_at` (`published_at` নেই) |

Media (kernel `core_media_usages`):

| field | কী | ক্রম / নিজস্ব তথ্য |
|---|---|---|
| `items` | album-এর ছবি ও upload করা video (Media Library item, kind image / video) | `sort_order` = album-এর ক্রম (drag); `meta.caption` (ভাষা অনুযায়ী) = এই album-এ নিজস্ব caption, না থাকলে Media item-এর caption |
| `cover` | cover ছবি | না দিলে `items`-এর প্রথম ছবি |

Vocabulary: `gallery.category` (hierarchical; FR-GAL-03)। কোথায় দেখাবে = "Show in" + main site (§1)। Album-এ ছবি / video আছে কিনা item থেকেই বোঝা যায় — `album_type` column নেই।

Form: Media Picker (একসাথে অনেক বাছাই, সেখানেই upload), **Add a folder** (Media Library-র একটি folder-এর সব ছবি একবারে), drag ক্রম, প্রতি ছবির caption (ঐচ্ছিক)।

### 7.2 `gallery_videos` — B (assignable: speaker)

শুধু বাইরের link (YouTube / Vimeo / Facebook) — এগুলো file নয়, তাই Media Library-তে রাখা যায় না।

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| album_id | BIGINT UNSIGNED | FK gallery_albums (cascade), IX | |
| url | VARCHAR(2048) | | |
| provider | VARCHAR(20) | | `youtube` / `vimeo` / `facebook` (url থেকে) |
| provider_id | VARCHAR(100) | N, IX | video id (embed ও thumbnail, FR-GAL-02) |
| title | JSON | N, T | |
| episode_no | SMALLINT UNSIGNED | N | Lecture series / documentary পর্ব |
| duration_seconds | INT UNSIGNED | N | |
| sort_order | INT UNSIGNED | | album-এর মধ্যে drag |
| + common | | | `is_active` |

IX: `(album_id, sort_order)`। বক্তা = kernel assignment (role `speaker`, Person Picker)। Thumbnail provider থেকে (YouTube); অন্যগুলোতে album-এর cover।

Website: `/gallery` (album list, category filter, Photos / Videos), `/gallery/{slug}` (ছবি masonry / grid / carousel + lightbox — keyboard ও swipe, Alpine; video embed, পর্ব ও বক্তা), `/{entity}/gallery`। Layout `gallery.album` = masonry / grid / carousel, `gallery.listing` = cards / grid। Event page-এ সেই event-এর album (FR-GAL-06)। Homepage section `gallery.photos` "Photo Gallery" (সেই জায়গার album-গুলোর ছবি) ও `gallery.videos` "Video Gallery" (FR-GAL-07)। Zip download (FR-GAL-08, Could) — পরে।

---

## 8. M-23 Slider / Hero — `slider_slides` — B (attachable)

*(v1.7, build-এর আগে — owner 2026-10-16: একটি slider যা বিভিন্ন জায়গায় — department site, অন্য microsite — ব্যবহার হবে; আধুনিক site-এ প্রায়ই একটি ছবি বা background video, যেমন DIU-র এখনকার homepage-এর full-screen video।)* একটি জায়গার **hero = সেখানে দেখানো slide-গুলো**: একটি slide = স্থির hero (arrow / autoplay নেই), একাধিক = slider; slide-এ video থাকলে full-screen background video (autoplay, muted, loop, playsinline; ছবি = poster)। একটি slide "Show in" দিয়ে একসাথে অনেক জায়গায় — বারবার বানাতে হয় না।

| Column | Type | Key / N | Default | বর্ণনা |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | PK | | |
| placement | VARCHAR(20) | IX | `hero` | `hero` / `page_banner` (cover photo; FR-SLD-03/06) |
| title | JSON | N, T | | |
| subtitle | JSON | N, T | | |
| buttons | JSON | N | | `[{label: {en, bn}, url}]` সর্বোচ্চ ২ (FR-SLD-02) |
| bullets | JSON | N, T | | ভাষা অনুযায়ী তালিকা, সর্বোচ্চ ৪ |
| text_position | VARCHAR(10) | | `left` | `left` / `center` / `right` |
| overlay_opacity | TINYINT UNSIGNED | | 40 | ০–৮০; theme-এর brand রঙে gradient |
| video_on_mobile | BOOLEAN | | false | false = phone-এ শুধু ছবি (data বাঁচায়) |
| sort_order | INT UNSIGNED | | | drag |
| + common | | | | `is_active`, `active_at` (কবে থেকে), `expires_at` (কবে পর্যন্ত — admission campaign; FR-SLD-04); `published_at` / `starts_at` / `ends_at` নেই |

Media (Media Library reference, copy নয়): `image_desktop` (দরকার), `image_mobile` (না দিলে desktop ছবি crop), `video` (mp4 / webm, kind video)। `body` column বাদ (title, subtitle, button, bullet যথেষ্ট)।

কোথায় দেখাবে = "Show in" + main site (§1); AC-SLD-01: BSc in CSE-এর slide শুধু তার hero-তে। Homepage section `slider.hero` "Hero" (যেকোনো homepage — main বা microsite): সেই জায়গার `hero` slide (ক্রমে); settings autoplay, interval, transition (slide / fade), arrows, dots, height (screen / large / medium)। Slide নেই → সেই জায়গার `page_banner` → parent entity-র hero / banner (`EntityHierarchy::ancestors`) → কিছু না (theme default; FR-SLD-06)। `prefers-reduced-motion` হলে autoplay ও video নয়। Alpine। Homepage-এর `core.hero` থাকে (Slider module বন্ধ থাকলে); migration-এ main homepage-এর `core.hero` → `slider.hero`। Recommended size form-এ (FR-SLD-07: desktop 1920×1080, mobile 1080×1350; crop Media Library-তে)।

---

## 9. M-24 Achievements — `achievements_achievements` — B (attachable, assignable)

*(v1.8, build-এর আগে — owner 2026-10-16: নতুন application-এর standard বজায় থাকবে, পুরোনো design দেখে বদলাবে না। Legacy-তে achievement table নেই — নতুন করে শুরু, migration step নেই।)*

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| title | JSON | T | |
| summary | JSON | N, T | |
| body | JSON | N, T | |
| achieved_on | DATE | IX | listing-এর ক্রম (নতুন আগে), বছর filter |
| team_name | JSON | N, T | দলের নাম ("Team DIU Mavericks"); ব্যক্তিরা assignment-এ |
| competition | JSON | N, T | প্রতিযোগিতা / award |
| issuer | JSON | N, T | প্রদানকারী প্রতিষ্ঠান |
| level | VARCHAR(20) | IX | `international` / `national` / `regional` / `institutional` |
| position_label | JSON | N, T | "Champion", "Runner-up" |
| related_post_id | BIGINT UNSIGNED | N, IX | একই অর্জনের News post (FR-ACH-04); News optional, তাই FK নয় |
| + common | | | `is_active`, `active_at` (`published_at` নেই) |

অর্জনকারী = kernel assignment (role `achiever`, Person Picker — Faculty Member, Alumni, Administration বা manual person; নিজস্ব note, যেমন "Team lead") — free-text `achievers` column বাদ। Media (Media Library reference): `image` (cover), `gallery`। Vocabulary: `achievements.category`। কোথায় দেখাবে = "Show in" + main site (§1), Highlight।

Website: `/achievements`, `/{entity}/achievements` — বছর, level, category filter; layout `achievements.listing` = cards / timeline / grid (FR-ACH-02); `/achievements/{slug}` — ছবি (lightbox), অর্জনকারী, প্রতিযোগিতা, প্রদানকারী, JSON-LD। Homepage section `achievements.recent` "Recent Achievements" (source mode, level filter); metric `achievements.count` (level অনুযায়ী — "100+ International Awards", FR-ACH-03)। Related News (FR-ACH-04, Could): `related_post_id` — News module থাকলে form-এ post বাছাই, detail page-এ link। View count, search, sitemap।

---

## 10. M-25 Publications *(Optional)*

| Table | শ্রেণী | Column (⚙ ও common ছাড়া) | Key | Ref |
|---|---|---|---|---|
| `publications_publications` | B (Assignable: author) | `title` (T), `publication_type` VARCHAR(20) (`journal` / `conference` / `book` / `chapter` / `other`), `venue` (T) (journal / conference নাম), `publisher` N, `volume`, `issue`, `pages` VARCHAR(30) N, `year` SMALLINT IX, `doi` VARCHAR(191) N, `url` N, `abstract` (T, N), `keywords` JSON N, `indexing` JSON N (`["scopus", "wos"]`), `quartile` VARCHAR(2) N IX | UQ `(doi, active_key)` (BRL-PUB-01) | FR-PUB-01 থেকে 04 |
| `publications_journals` | B | `name` (T), `issn` VARCHAR(20) N, `eissn` N, `website_url` N, `description` (T, N), `sort_order`; Media `cover` | | FR-PUB-07 |

Author: `core_assignments` (`role_key = author`, `sort_order` = author ক্রম) — অভ্যন্তরীণ author Faculty Member, বাইরের author manual person।

---

## 11. M-26 Research *(Optional)*

| Table | শ্রেণী | Column | Key | Ref |
|---|---|---|---|---|
| `research_projects` | B (Assignable: team) | `title` (T), `summary` (T, N), `body` (T, N), `funding_agency` (T, N), `grant_amount` DECIMAL(14,2) N, `grant_currency` CHAR(3) N, `show_grant_amount` TINYINT(1), `starts_on`, `ends_on` DATE N, `project_status` VARCHAR(20) IX (`ongoing` / `completed`), + common | IX `(project_status, starts_on)` | FR-RES-01/04 |
| `research_project_publications` | P | `project_id` (FK cascade), `publication_id` (index — Publications optional, FK নয়), timestamps | PK দুটি মিলে | FR-RES-03 |

Team: `core_assignments` (`role_key` = `pi` / `co_pi` / `member`)। Vocabulary: `research.area` (FR-RES-02)।

---

## 12. M-27 Documents — `documents_documents` — B (attachable)

*(v1.9, build-এর আগে — owner 2026-10-16: application standard বজায়; পুরোনো project-এর paid flipbook-এর চেয়ে ভালো নিজস্ব flipbook Blade / Livewire / Alpine দিয়ে, তাহলে পুরোনোটা লাগবে না।)*

| Column | Type | Key / N | Default | বর্ণনা |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | PK | | |
| document_type | VARCHAR(20) | IX | `other` | `form` / `policy` / `prospectus` / `guidebook` / `charter` / `report` / `other` — নির্দিষ্ট তালিকা, কারণ ধরন অনুযায়ী field বদলায় (AC-DOC-01) |
| title | JSON | T | | |
| description | JSON | N, T | | |
| external_url | VARCHAR(2048) | N | | File না থাকলে (FR-DOC-01) |
| version_label | VARCHAR(30) | N | | Policy |
| approved_on | DATE | N | | Policy |
| effective_on | DATE | N | | |
| session_label | VARCHAR(50) | N | | Prospectus — "Fall 2026" (M-05 Session আসা পর্যন্ত লেখা, Admission-এর মতো) |
| program_id | BIGINT UNSIGNED | N, IX | | Prospectus (Program optional, FK নয়) |
| superseded_by_id | BIGINT UNSIGNED | N, FK self (set null), IX | | নতুন version (FR-DOC-05): পুরোনোটি "Superseded" — তালিকায় নয়, link কাজ করে ও নতুনটি দেখায় |
| show_as_flipbook | BOOLEAN | | false | PDF পাতা-উল্টানো viewer-এ (FR-DOC-03) |
| allow_download | BOOLEAN | | true | false = শুধু পড়া (flipbook / preview), download button নেই |
| is_private | BOOLEAN | | false | শুধু logged-in admin user; file signed URL (FR-DOC-07) |
| download_count | INT UNSIGNED | | 0 | FR-DOC-06 |
| sort_order | INT UNSIGNED | | | |
| + common | | | | `is_active`, `active_at` (`published_at` নেই) |

Media (Media Library reference, copy নয়): `file` (একটি; PDF হলে preview / flipbook), `thumbnail` (cover; না দিলে flipbook-এ PDF-এর প্রথম পাতা)। Vocabulary `documents.category` (hierarchical — "Academic", "Admission" …; ধরন নয়)। কোথায় দেখাবে = "Show in" + main site (§1)। IX: `(document_type, is_active, active_at)`।

**Flipbook (FR-DOC-03):** শুধু PDF upload — ZIP বা আলাদা software নয়। Browser-এ PDF.js (Mozilla, Apache-2.0, module-এর সাথে self-hosted — `public/modules/documents/pdfjs`, CDN নয়) পাতা আঁকে; Alpine viewer: desktop-এ দুই পাতা (spread), phone-এ এক পাতা; 3D পাতা উল্টানো (reduced-motion-এ সরাসরি); ← → / swipe / কোণে click; thumbnail strip; zoom; full screen; পাতা নম্বরে যাওয়া ও `?page=` link; শুধু আশেপাশের পাতা render (৩০০ পাতার prospectus-ও দ্রুত); download (`allow_download` হলে); screen reader-এর জন্য পাতা নম্বর ঘোষণা। Server-এ কোনো tool (Imagick, Ghostscript) লাগে না।

Website: `/downloads`, `/{entity}/downloads` — ধরন, category, বছর, search; layout `documents.listing` = table / cards / list (FR-DOC-04); `/downloads/{slug}` — বিবরণ, policy-র version / তারিখ ও পুরোনো version-গুলো, PDF preview বা flipbook, download; `/downloads/{slug}/file` — গণনা করে file-এ পাঠায় (private হলে login ও signed URL)। Homepage section `documents.downloads` "Downloads" ও `documents.prospectus` "Prospectus" (FR-DOC-08); page block "Document / Flipbook" (যেকোনো Page-এ)। Search, sitemap (private নয়)।

Kernel: module-এর `public/` → `public/modules/{slug}/` (install / activate-এ copy; SAD Part A §9 asset path), Blade-এ `module_asset('documents', 'pdfjs/pdf.min.mjs')`।

---

## 13. M-28 FAQ — `faq_faqs` — B

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| question | JSON | T | *(v1.1: আগে VARCHAR — Phase 1-এর fixture)* |
| answer | JSON | T | rich text |
| helpful_yes, helpful_no | INT UNSIGNED | | FR-FAQ-05 |
| sort_order | INT UNSIGNED | | |
| ⚙ | | | `is_active` |

* *(v1.1, owner 2026-10-11)* **`published_at` নেই** — FAQ সময় ধরে প্রকাশ হয় না, দেখানো হবে কিনা = `is_active`। নিয়ম: নতুন table-এ `published_at` নয়; কোনো module-এ সত্যিই নির্দিষ্ট সময়ে প্রকাশ লাগলে column-এর নাম `active_at` (News, Notice, Events-এর সময় ঠিক হবে)।
* Category: vocabulary `faq.category` (hierarchical; category-র উপরের বিবরণ `core_terms.description` — FR-FAQ-01)।
* কোথায় দেখাবে: Attachable (`core_relations`) — Department / Program / Office-এর FAQ (FR-FAQ-03, entity page-এর "FAQ" tab), main site = `site:0` relation।
* Website: `/faq` — category অনুযায়ী accordion, instant search (browser-এ, Alpine), `?category={slug}`, FAQPage JSON-LD; "Was this helpful?" (Yes / No, প্রতি visitor একবার, rate limit); page block "FAQ" (category / entity / বাছাই করা)। Excel / CSV import (Question, Answer, Category)।

---

## 14. M-29 Important Links — `links_links` — B (attachable)

*(v1.10, build-এর আগে — application standard: প্রতিটি জায়গার (main site, department, office …) নিজের link — "Show in" + main site; legacy-তে link table নেই, তাই migration step নেই। Menu-র "Quick links" location মেনুর জন্য; এটি বিবরণ, icon ও group সহ link-এর তালিকা।)*

| Column | Type | Key / N | Default | বর্ণনা |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | PK | | |
| title | JSON | T | | |
| url | VARCHAR(2048) | | | `https://…` বা site-এর ভেতরের `/…` |
| description | JSON | N, T | | |
| open_in_new_tab | BOOLEAN | | true | |
| last_checked_at | TIMESTAMP | N | | FR-LINK-03 |
| last_status_code | SMALLINT UNSIGNED | N | | 0 = উত্তর নেই; ≥ 400 = ভাঙা |
| sort_order | INT UNSIGNED | | 0 | drag |
| + common | | | | `is_active` |

Media (Media Library reference): `icon`। Vocabulary: `links.group` ("Student Services", "Government" …)। কোথায় দেখাবে = "Show in" + main site (§1)।

Website: `/links`, `/{entity}/links` — group অনুযায়ী (group-এর ক্রমে, ভেতরে drag-এর ক্রমে); layout `links.listing` = tiles / list। Homepage section `links.list` "Important Links" (group filter, tiles / list); **footer block** `links.footer` (Header & Footer-এর footer column-এ — kernel `$r->footerBlock()`, theme-এ নিজস্ব view না থাকলে module-এর view)। Sidebar widget — M-30 Widgets-এর সাথে। FR-LINK-03: `links:check` প্রতি সপ্তাহে (সোমবার ০৪:০০) বাইরের link-গুলো দেখে (HEAD, না হলে GET; ১০ সেকেন্ড), ভাঙা পেলে link-এর দায়িত্বে থাকা user-দের notification `links.broken`; admin তালিকায় "Broken" filter ও status।

---|---|---|---|---|
| id | BIGINT UNSIGNED | PK | | |
| title | JSON | T | | |
| url | VARCHAR(2048) | | | |
| description | JSON | N, T | | |
| open_in_new_tab | TINYINT(1) | | 1 | |
| last_checked_at | TIMESTAMP | N | | FR-LINK-03 |
| last_status_code | SMALLINT UNSIGNED | N | | |
| sort_order | INT UNSIGNED | | 0 | |
| ⚙ | | | | |

Media: `icon`। Vocabulary: `links.group`।

---

## 15. M-30 Announcement Widgets — `announcement_widgets` — B (attachable)

*(v1.11, build-এর আগে — application standard: `published_at` / `starts_at` / `ends_at` নয়, `is_active` + `active_at` + `expires_at`; কোথায় = "Show in" + main site; কোন page = homepage / সব page / বাছাই করা module page।)*

| Column | Type | Key / N | Default | বর্ণনা |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | PK | | |
| widget_type | VARCHAR(20) | IX | | `popup` / `ticker` / `countdown` / `bar` |
| title | JSON | N, T | | |
| body | JSON | N, T | | Popup-এর লেখা (rich text) / ticker ও bar-এর লেখা |
| link_url | VARCHAR(2048) | N | | |
| button_text | JSON | N, T | | |
| target_at | DATETIME | N | | Countdown (FR-ANW-07) |
| after_end | VARCHAR(10) | | `hide` | Countdown শেষে: `hide` / `message` |
| after_end_text | JSON | N, T | | "Event started" |
| frequency | VARCHAR(20) | | `daily` | Popup: `each_visit` / `daily` / `once` (browser-এ মনে রাখে; FR-ANW-04) |
| is_dismissible | BOOLEAN | | true | Bar |
| color_token | VARCHAR(30) | N | | Theme token: `color-primary` / `color-secondary` / `color-accent` / `color-danger` |
| priority | SMALLINT | | 0 | বড় আগে; একটি page-এ একটিই popup (FR-ANW-05) |
| pages | VARCHAR(10) | | `home` | `home` (সেই site-এর homepage) / `all` (সেই site-এর সব page) / `selected` |
| route_keys | JSON | N | | `pages = selected` হলে module page key (kernel website page, যেমন `"notice.index"`, `"admission.result"`) অথবা site-এর ঠিকানা (`"/admission"` — নিচের page-গুলোতেও) — বর্তমান `is_admission` |
| include_latest_notices | BOOLEAN | | false | Ticker: সেই জায়গার board-এর সর্বশেষ ৫টি notice-ও (Notice module থাকলে; FR-ANW-06) |
| sort_order | INT UNSIGNED | | | ticker / bar-এর ক্রম |
| + common | | | | `is_active`, `active_at` (কবে থেকে), `expires_at` (কবে পর্যন্ত; FR-ANW-03) |

Media (Media Library reference): `image` (popup-এর ছবি)। কোথায় = "Show in" + main site (§1) — শুধু সেই জায়গার site-এ (main site-এর popup microsite-এ নয়)।

Website: theme layout-এর slot — `announcement` (bar ও countdown), `ticker` (সব ticker একসাথে, চলমান; reduced-motion-এ থেমে থাকে), `popup` (একটি, priority অনুযায়ী; Esc / বোতামে বন্ধ, focus trap; Alpine)। Kernel: `<x-platform::slot>` theme-এর partial না থাকলে `SiteData` provider `slot:{name}`-এর content দেখায় — Announcement module তিনটি slot provide করে; module বন্ধ থাকলে খালি।

---|---|---|---|---|
| id | BIGINT UNSIGNED | PK | | |
| widget_type | VARCHAR(20) | IX | | `popup` / `ticker` / `countdown` / `bar` |
| title | JSON | N, T | | |
| body | JSON | N, T | | Popup বিষয়বস্তু / ticker text |
| link_url | VARCHAR(2048) | N | | |
| button_text | JSON | N, T | | |
| target_at | DATETIME | N | | Countdown (FR-ANW-07) |
| after_end_text | JSON | N, T | | |
| frequency | VARCHAR(20) | | `daily` | `each_visit` / `daily` / `once` (FR-ANW-04) |
| is_dismissible | TINYINT(1) | | 1 | Bar |
| color_token | VARCHAR(30) | N | | Theme token |
| priority | SMALLINT | | 0 | একসাথে একটি popup (FR-ANW-05) |
| show_on_all_pages | TINYINT(1) | | 0 | |
| route_keys | JSON | N | | নির্দিষ্ট module page, যেমন `["admission.index"]` (বর্তমান `is_admission`) |
| include_latest_notices | TINYINT(1) | | 0 | Ticker (FR-ANW-06) |
| starts_at, ends_at | DATETIME | N, IX | | FR-ANW-03 |
| + common | | published_at |

Entity / main site placement: `core_relations` (Section 1)। Media: `image`।

> SRS Part 4-এ module code `ANW`; slug `announcement` (table prefix ছোট রাখতে)।

---

## 16. Vocabulary

| Key | Module | Hierarchical | বর্তমান উৎস |
|---|---|---|---|
| `news.category` | News | | `blog_categories` (৩) |
| `events.category` | Events | | `event_categories` (৫) |
| `notice.category` | Notice | | `notice_categories` (৫) |
| `gallery.category` | Gallery | ✔ | `gallery_categories` (১৪) + `gallery_sub_categories` (১৮) |
| `achievements.category` | Achievements | | — |
| `research.area` | Research | ✔ | — |
| `documents.type` | Documents | | flipbook, prospectus, charter / guidebook, policy, content category |
| `faq.category` | FAQ | ✔ | `faq_categories` (৯) *(`accordion_categories` → Scholarship, DB-03 v1.4 §11.3)* |
| `links.group` | Links | | — |
| `shared.tags` | সব | | `blog_tags` (৩) |

---

## 17. Table সারসংক্ষেপ (DB-05)

| Module | Table | সংখ্যা |
|---|---|---|
| News | news_posts | ১ |
| Events | events_events | ১ |
| Notice | notice_notices | ১ |
| Testimonials | testimonials_testimonials | ১ |
| Gallery | gallery_albums, gallery_videos | ২ |
| Slider | slider_slides | ১ |
| Achievements | achievements_achievements | ১ |
| Publications | publications_publications, publications_journals | ২ |
| Research | research_projects, research_project_publications | ২ |
| Documents | documents_documents | ১ |
| FAQ | faq_faqs | ১ |
| Links | links_links | ১ |
| Announcement | announcement_widgets | ১ |
| **মোট** | | **১৬** |

বর্তমান system-এর ~৪৫টি content table (প্রতিটি entity-র আলাদা gallery, slider, article, video table ইত্যাদি) নতুন system-এ ১৬টি — entity সংযোগ Relation দিয়ে, file Media Library-তে, category Taxonomy-তে।

---

## 18. `project_diu` থেকে Migration Mapping

| বর্তমান (row) | নতুন | রূপান্তর |
|---|---|---|
| `notices` (৩,৩৯১; সবগুলোতে `department_id`) | notice_notices | `department_id` → Department relation; **`is_home = 1` (২,৮০৮) → `site:0` relation (main site-এ প্রকাশিত, highlight নয়)**; `view_counter` → core_view_counts; `notice_category_id` → term |
| `notice_files` (৩,৫৩২) | Media Library + usage (`attachments`, title `meta`) | `notices.file_name` সব খালি — file শুধু `notice_files`-এ |
| `events` (৫৬৩) | events_events | `department_id` (৫৪০) → relation; `lab_facility_id` (১২) → Lab relation; `is_home = 1` (৩৩৪) → `site:0`; `event_date` + `event_time` → starts_at; `location` → venue; `url` → registration_url বা link (manual যাচাই) |
| `event_images` (৭৭) | Event-এর Media `gallery` (v1.6) | S03-এর পরে S16E আবার চালালে |
| `event_categories` (৫) | `events.category` | |
| `blog_posts` (১) + categories / tags | news_posts | News নতুন করে শুরু (Q-21); এই একটি post migrate |
| `articles` (২৮৫) | page_pages | DB-02 Section 18 |
| `contents` (৩৪) + `content_categories` (২) | documents_documents (file / url থাকলে) অথবা page_pages | বিষয়বস্তু দেখে; `department_id` → relation |
| `testimonials` (২০; সবগুলো Program-এর — `App\Models\DepartmentProgramType`) | testimonials_testimonials | `testimonialable_id` → Program relation; নাম / পদবি / প্রতিষ্ঠান / ছবি → manual person + assignment |
| `mba_testimonials` (৫) | testimonials_testimonials | MBA Program relation |
| `gallery_categories` (১৪), `gallery_sub_categories` (১৮) | `gallery.category` (hierarchical) | `assign_department` → album-এর Department relation |
| `gallery_photos` (২৪৬) | gallery_albums (sub-category অনুযায়ী; sub-category নেই → category-র নামে) + Media `items` | ছবি S03-এ Media Library-তে একবার, album-এ শুধু reference; `photo_title` → `meta.caption`; department → album-এর relation; `is_home = 1` (২৮) → album main site-এ |
| `gallery_folders` (৪) | Media Library folder | |
| `lab_galleries` (৫৬৪) | gallery_albums (Lab-প্রতি) + Media `items`, Lab relation | Lab module-এর পরে (DB-06 Lab id mapping) |
| `documentaries` (২৭) | gallery_videos — department-প্রতি একটি album "Documentaries" (`series_type = documentary`) | `department_id` → album-এর relation; lab-এরগুলো Lab module-এর পরে |
| `lecture_series` (১৬) | gallery_videos — album "Lecture Series" (`lecture_series`, main site) | `lecture_no` → `episode_no`, `serial` → ক্রম, বক্তা → manual person (role `speaker`) |
| `program_videos` (২) | gallery_videos — album "Program Videos" | কোন Program — review |
| `sliders` (১৫৩) | slider_slides (`hero`) | `department_id` → relation; `show_departments` → একাধিক Department relation; `is_home` → main site; `slider_title` / `slider_text` → title / subtitle, `url` + `button_text` → button; lab (২) Lab-এর সাথে; ছবি S03-এর পরে reference (v1.7) |
| `cover_photos` (১৩) | slider_slides (`page_banner`), Department relation | ছবি S03-এর পরে |
| `program_sliders` (৩), `mba_sliders` (৪) | slider_slides (`hero`), Program relation | `first_point` … `fourth_point` → bullets; দুটি button → buttons |
| `flipbooks` (১৬) + categories (৩) | documents_documents (`show_as_flipbook = 1`) | **ঝুঁকি:** বর্তমানে ZIP (`filpbook_zip`); মূল PDF (`file_name`) না থাকলে PDF সংগ্রহ করতে হবে |
| `prospectuses` (৪৫) | documents_documents (type Prospectus) | `semester_id` → session_id, `department_program_type_id` → program_id |
| `charter_guidebooks` (৮৬) + categories (৪) | documents_documents (Charter / Guidebook) | |
| `policies` (১) + categories (২) | documents_documents (Policy) | `approve_date` → approved_on |
| `faqs` (১,৩০৯) + `faq_categories` (৯) | faq_faqs + `faq.category` | `question` / `answer` → en; `serial` → sort_order; `publication_status` → is_active (১০টি inactive); `department_id` (১,১৮১) → সেই Department-এর relation (legacy-র office-"department" হলে সেই Office-এর); *(2026-10-11 যাচাই)* `is_home` কোনো row-এ "1" নয় (enum: "0" ১,২৫৪, খালি ৫৫) — তাই department নেই এমন ১২৮টি FAQ → `site:0` relation (legacy-র `/faq`-এ যা দেখাত); category (৯টি, slug রাখা) → term |
| `accordions` (৩০) + `accordion_categories` (৬) | *(2026-10-10)* **Scholarship module**-এ (DB-03 v1.4 §11.3) — এগুলো DIU-র waiver / scholarship scheme, FAQ নয় | — |
| `greetings` (৫) | announcement_widgets (`popup`) | `is_home` → main site; `is_faculty` / `is_department` / `is_program` → `faculty_id` / `department_id` / `department_program_type_id` relation; `is_admission` → `pages = selected`, `route_keys = ["/admission"]`; `start_date` / `expiry_date` → `active_at` / `expires_at` (v1.11) |
| `news_tickers` (৪) | announcement_widgets (`ticker`) | |
| `countdown_timers` (১) | announcement_widgets (`countdown`) | |
| `lab_news` (১) | news_posts, Lab relation | |
| `lab_research` (৩) | research_projects, Lab relation | Research optional module — data migrate হবে, module inactive থাকলে দেখাবে না |
| `department_articles` (২৯৫), `faculty_articles` (৩৩), `program_articles` (২৪), `lab_articles` (৯) | page_pages (owner অনুযায়ী) | DB-02 Section 18 |

---

## 19. অন্য Document-এ সংশোধন (Q-26-এর কারণে)

| Document | সংশোধন |
|---|---|
| BRD | BR-CNT-08 ও Q-26 যোগ (v1.6) |
| SRS Part 4 Section 1.1 | FR-CNT-H07: Main site-এ প্রকাশ শুধু Admin; main listing = `site` relation থাকা content |
| SRS Part 0 FR-GEN-15 | Main site প্রকাশ ও highlight আলাদা |
| SAD Part B Section 3.1, 3.4 | Main Site row-এর অর্থ "main site-এ প্রকাশিত"; Latest mode-এ main context = site row থাকা content |
| SAD Part A Section 7.4 | Custom permission `PublishMain:{Resource}` |
| DB-01 Section 8.1 | `core_relations` Main Site row-এর অর্থ |
| DB-04 Section 11.1 | `alumnis.is_home` (৬০২) → `site:0` (main site-এ প্রকাশিত), বাদ দেওয়া নয় |
