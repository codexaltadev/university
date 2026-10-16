# Database Design
## DB-05 — Website Content Modules (M-18 থেকে M-30)

| বিষয় | তথ্য |
|---|---|
| Document | Database Design DB-05 |
| Version | 1.5 — 2026-10-11 (v1.1): §13 FAQ (question / answer ভাষা অনুযায়ী, `published_at` নেই — `is_active`; সময় লাগলে `active_at`), §18 FAQ mapping; 2026-10-16 (v1.2): §2 common column `active_at`, §3 News (website, homepage section, migration); 2026-10-16 (v1.3): §4 Events (Campus ও Gallery আসা পর্যন্ত লেখা venue ও Media gallery, website, section); 2026-10-16 (v1.4): §5 Notice (`active_at`, board / archive, attachment title, website, section); 2026-10-16 (v1.5): §6 Testimonials (`published_at` নেই, person, website, section) |
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
| testimonial_type | VARCHAR(20) | IX | `student` / `alumni` / `parent` / `employer` / `partner` |
| rating | TINYINT UNSIGNED | N | ১–৫ |
| video_url | VARCHAR(2048) | N | |
| + common | | `sort_order` *(v1.5: `published_at` নেই — সময় ধরে প্রকাশ লাগে না, `is_active` যথেষ্ট)* |

ব্যক্তি (নাম, পদবি, প্রতিষ্ঠান, ছবি): `core_assignments` (`role_key = author`) — Alumni বা অন্য person source, অথবা manual person (FR-TEST-01)।

*(v1.5, build-এর আগে)* ঠিক একজন ব্যক্তি (form-এ Person Picker + role-এর নিজস্ব নাম, যেমন "Parent of a CSE student")। কোথায় দেখাবে = "Show in" + main site (§1); Program-এর testimonial তার page-এ tab। Website: `/testimonials` ও `/{entity}/testimonials` — `?type=` filter, layout slider / grid / masonry / video (FR-TEST-02); ক্রম drag (FR-TEST-04)। Homepage section `testimonials.list` "What People Say" — ধরন filter, সেই জায়গার testimonial (FR-TEST-03); homepage seeder-এর `testimonial.list` → `testimonials.list`। Migration S17 (testimonial অংশ, S17M): `testimonials` (২০) ও `mba_testimonials` (৫) → Program relation, ব্যক্তি manual person।

---

## 7. M-22 Gallery

### 7.1 `gallery_albums` — B

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| title | JSON | T | |
| description | JSON | N, T | |
| album_type | VARCHAR(10) | IX | `photo` / `video` / `mixed` |
| series_type | VARCHAR(20) | N, IX | `documentary` / `lecture_series` (FR-GAL-04) |
| event_id | BIGINT UNSIGNED | N, IX | Events optional (FR-GAL-06) |
| occurred_on | DATE | N | |
| + common | | published_at, sort_order |

Media: `cover`। Vocabulary: `gallery.category` (hierarchical; FR-GAL-03)।

### 7.2 `gallery_items` — B

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| album_id | BIGINT UNSIGNED | FK gallery_albums (cascade), IX | |
| item_type | VARCHAR(10) | | `image` / `video` |
| media_item_id | BIGINT UNSIGNED | N, FK core_media_items (restrict) | Image বা upload করা video (BRL-GAL-01: reference, copy নয়) |
| video_url | VARCHAR(2048) | N | YouTube / Vimeo / Facebook |
| video_provider | VARCHAR(20) | N | |
| caption | JSON | N, T | |
| speaker_name, speaker_designation, speaker_organization | JSON | N, T | Lecture series (বর্তমান `lecture_series`) |
| episode_no | SMALLINT UNSIGNED | N | |
| duration_seconds | INT UNSIGNED | N | |
| sort_order | INT UNSIGNED | | |
| ⚙ | | | |

IX: `(album_id, sort_order)`।

> Gallery item `core_media_items`-কে সরাসরি FK দিয়ে reference করে (kernel table, তাই FK নিরাপদ) — `core_media_usages`-এর বদলে, কারণ প্রতিটি item-এর নিজস্ব caption ও ক্রম আছে। Usage tracking-এর জন্য item save হলে `core_media_usages`-এ একটি row-ও লেখা হয় (FR-MEDIA-09)।

---

## 8. M-23 Slider / Banner — `slider_slides` — B

| Column | Type | Key / N | Default | বর্ণনা |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | PK | | |
| placement | VARCHAR(20) | IX | `hero` | `hero` / `page_banner` (FR-SLD-03) |
| title | JSON | N, T | | |
| subtitle | JSON | N, T | | |
| body | JSON | N, T | | |
| buttons | JSON | N | | `[{text: {en, bn}, url, style}]` সর্বোচ্চ ২ (FR-SLD-02) |
| bullets | JSON | N, T | | সর্বোচ্চ ৪ |
| text_position | VARCHAR(20) | | `left` | |
| overlay_opacity | TINYINT UNSIGNED | | 40 | |
| starts_at, ends_at | DATETIME | N, IX | | Schedule (FR-SLD-04) |
| + common | | published_at, sort_order |

কোথায় দেখাবে (main homepage / CSE hero / BSc in CSE hero): `core_relations` (Section 1)। Media: `image_desktop`, `image_mobile`, `video_background`।

---

## 9. M-24 Achievements — `achievements_achievements` — B

| Column | Type | Key / N | বর্ণনা |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK | |
| title | JSON | T | |
| summary | JSON | N, T | |
| body | JSON | N, T | |
| achieved_on | DATE | IX | |
| achievers | JSON | N, T | ছাত্র / দল / শিক্ষকের নাম (free text) |
| competition | JSON | N, T | প্রতিযোগিতা / award |
| issuer | JSON | N, T | প্রদানকারী প্রতিষ্ঠান |
| level | VARCHAR(20) | IX | `international` / `national` / `regional` / `institutional` |
| position_label | JSON | N, T | "Champion", "Runner-up" |
| + common | | published_at |

Media: `image`, `gallery`। Vocabulary: `achievements.category`।

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

## 12. M-27 Documents — `documents_documents` — B

| Column | Type | Key / N | Default | বর্ণনা |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | PK | | |
| type_term_id | BIGINT UNSIGNED | FK core_terms (restrict), IX | | Vocabulary `documents.type`: Form, Policy, Prospectus, Flipbook, Guidebook, Charter, Report, Other (FR-DOC-02) |
| title | JSON | T | | |
| description | JSON | N, T | | |
| external_url | VARCHAR(2048) | N | | File না থাকলে |
| version_label | VARCHAR(30) | N | | Policy |
| approved_on | DATE | N | | Policy |
| effective_on | DATE | N | | |
| session_id | BIGINT UNSIGNED | N, IX | | Prospectus (Session optional) |
| program_id | BIGINT UNSIGNED | N, IX | | Prospectus (Program optional) |
| superseded_by_id | BIGINT UNSIGNED | N, FK self (set null) | | Policy version history (FR-DOC-05) |
| show_as_flipbook | TINYINT(1) | | 0 | PDF থেকে flip viewer (FR-DOC-03, Q-23) |
| is_private | TINYINT(1) | | 0 | Signed URL (FR-DOC-07) |
| download_count | INT UNSIGNED | | 0 | FR-DOC-06 |
| + common | | published_at, sort_order |

Media: `file`, `thumbnail`। IX: `(type_term_id, is_active, published_at)`।

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

## 14. M-29 Important Links — `links_links` — B

| Column | Type | Key / N | Default | বর্ণনা |
|---|---|---|---|---|
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

## 15. M-30 Announcement Widgets — `announcement_widgets` — B

| Column | Type | Key / N | Default | বর্ণনা |
|---|---|---|---|---|
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
| Gallery | gallery_albums, gallery_items | ২ |
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
| `event_images` (৭৭) | gallery_albums (event-প্রতি একটি) + gallery_items | album.event_id = নতুন event |
| `event_categories` (৫) | `events.category` | |
| `blog_posts` (১) + categories / tags | news_posts | News নতুন করে শুরু (Q-21); এই একটি post migrate |
| `articles` (২৮৫) | page_pages | DB-02 Section 18 |
| `contents` (৩৪) + `content_categories` (২) | documents_documents (file / url থাকলে) অথবা page_pages | বিষয়বস্তু দেখে; `department_id` → relation |
| `testimonials` (২০; সবগুলো Program-এর — `App\Models\DepartmentProgramType`) | testimonials_testimonials | `testimonialable_id` → Program relation; নাম / পদবি / প্রতিষ্ঠান / ছবি → manual person + assignment |
| `mba_testimonials` (৫) | testimonials_testimonials | MBA Program relation |
| `gallery_categories` (১৪), `gallery_sub_categories` (১৮) | `gallery.category` (hierarchical) | `assign_department` → album-এর Department relation |
| `gallery_photos` (২৪৬) | gallery_albums (sub-category অনুযায়ী) + gallery_items | `is_home = 1` (২৮) → album-এর `site:0` |
| `gallery_folders` (৪) | Media Library folder | |
| `lab_galleries` (৫৬৪) | gallery_albums (Lab-প্রতি) + items, Lab relation | DB-06-এ Lab id mapping |
| `documentaries` (২৭) | gallery album (`series_type = documentary`, video) | `department_id` / `lab_facility_id` → relation |
| `lecture_series` (১৬) | gallery album (`lecture_series`) + items | speaker field |
| `program_videos` (২) | gallery video item, Program relation | |
| `sliders` (১৫৩) | slider_slides (`hero`) | `department_id` / `lab_facility_id` → relation; `show_departments` → একাধিক Department relation; `is_home` → `site:0` |
| `cover_photos` (১৩) | slider_slides (`page_banner`), Department relation | |
| `program_sliders` (৩), `mba_sliders` (৪) | slider_slides (`hero`), Program relation | `first_point` … `fourth_point` → bullets; দুটি button → buttons |
| `flipbooks` (১৬) + categories (৩) | documents_documents (`show_as_flipbook = 1`) | **ঝুঁকি:** বর্তমানে ZIP (`filpbook_zip`); মূল PDF (`file_name`) না থাকলে PDF সংগ্রহ করতে হবে |
| `prospectuses` (৪৫) | documents_documents (type Prospectus) | `semester_id` → session_id, `department_program_type_id` → program_id |
| `charter_guidebooks` (৮৬) + categories (৪) | documents_documents (Charter / Guidebook) | |
| `policies` (১) + categories (২) | documents_documents (Policy) | `approve_date` → approved_on |
| `faqs` (১,৩০৯) + `faq_categories` (৯) | faq_faqs + `faq.category` | `question` / `answer` → en; `serial` → sort_order; `publication_status` → is_active (১০টি inactive); `department_id` (১,১৮১) → সেই Department-এর relation (legacy-র office-"department" হলে সেই Office-এর); *(2026-10-11 যাচাই)* `is_home` কোনো row-এ "1" নয় (enum: "0" ১,২৫৪, খালি ৫৫) — তাই department নেই এমন ১২৮টি FAQ → `site:0` relation (legacy-র `/faq`-এ যা দেখাত); category (৯টি, slug রাখা) → term |
| `accordions` (৩০) + `accordion_categories` (৬) | *(2026-10-10)* **Scholarship module**-এ (DB-03 v1.4 §11.3) — এগুলো DIU-র waiver / scholarship scheme, FAQ নয় | — |
| `greetings` (৫) | announcement_widgets (`popup`) | `is_home` → `site:0`; `is_faculty` / `is_department` / `is_program` → `faculty_id` / `department_id` / `department_program_type_id` relation; `is_admission` → `route_keys = ["admission.index"]`; `start_date` / `expiry_date` → starts_at / ends_at |
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
