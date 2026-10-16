# Software Requirements Specification (SRS)
## Part 4 — Website Content Modules (M-18 থেকে M-30)

| বিষয় | তথ্য |
|---|---|
| Document | SRS Part 4: Website Content Modules |
| Version | 0.7 — 2026-10-16: FR-NEWS-01 `active_at` (owner: নতুন table-এ `published_at` নয়); v0.3: BRL-GAL-01 — album-এর ছবি Media Library-র ব্যবহার, আলাদা item record নয়; v0.4: FR-SLD-04/05 — hero video, একটি slide = স্থির hero; v0.5: FR-ACH-01 — অর্জনকারী Person Picker; v0.6: FR-DOC-02/03 — ধরন তালিকা, নিজস্ব flipbook; v0.7: FR-LINK-02 — footer block hook |
| Date | 2026-10-08 |
| Status | Review-এর জন্য অপেক্ষমাণ |
| Based on | [BRD v1.1](../01-BRD.md), [SRS Part 0](00-SRS-Overview.md), [Part 1](01-SRS-Core.md), `project_diu` analysis |

> Part 0-এর Common Requirement (FR-GEN) সব module-এ প্রযোজ্য।

## সূচি

| # | Module | Code | Attachable | একত্রিত (BRD Q-17) |
|---|---|---|---|---|
| M-18 | [News](#m-18-news) | NEWS | ✔ | Blog post, entity article (news-ধরনের) |
| M-19 | [Events](#m-19-events) | EVT | ✔ | |
| M-20 | [Notice / Announcement](#m-20-notice--announcement) | NOT | ✔ | |
| M-21 | [Testimonials](#m-21-testimonials) | TEST | ✔ | |
| M-22 | [Gallery](#m-22-gallery) | GAL | ✔ | Photo Album, Video, Documentary, Lecture Series |
| M-23 | [Slider / Banner](#m-23-slider--banner) | SLD | ✔ | Cover photo, Program slider |
| M-24 | [Achievements](#m-24-achievements) | ACH | ✔ | |
| M-25 | [Publications](#m-25-publications) **(Optional)** | PUB | ✔ | |
| M-26 | [Research](#m-26-research) **(Optional)** | RES | ✔ | |
| M-27 | [Downloads / Documents](#m-27-downloads--documents) | DOC | ✔ | Prospectus, Flipbook, Charter / Guidebook, Policy, Content file |
| M-28 | [FAQ](#m-28-faq) | FAQ | ✔ | Accordion |
| M-29 | [Important Links](#m-29-important-links) | LINK | — | |
| M-30 | [Announcement Widgets](#m-30-announcement-widgets) | ANW | ✔ | Greetings popup, News Ticker, Countdown Timer |

---

## 1. Attachable Content-এর Common Requirement

নিচের requirement-গুলো এই part-এর সব Attachable module-এ প্রযোজ্য। প্রতিটি module-এ আবার লেখা হয়নি, শুধু ব্যতিক্রম উল্লেখ করা হয়েছে।

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-CNT-C01 | **Related To** (FR-GEN-50): এক বা একাধিক Faculty, Department, Program, Course, Office, Lab, Campus। বর্তমান system-এর `department_id`, `lab_facility_id`, `assign_department`, `show_departments` column-এর বদলে | BR-CNT-02 | Must |
| FR-CNT-C02 | **Context-ভিত্তিক Highlight** (বর্তমান `is_home`-এর বদলে): content প্রতিটি context-এ আলাদাভাবে highlight করা যাবে — "Main Site" এবং প্রতিটি related entity (Faculty, Department, Program, Office, Lab)। প্রতিটি highlight-এর ক্রম ও ঐচ্ছিক "Highlight until" তারিখ থাকবে। বিস্তারিত Section 1.1 | BR-CNT-03 | Must |
| FR-CNT-C03 | **Category** (module-এর নিজস্ব Taxonomy vocabulary) ও shared **Tags** | Q-07 | Must |
| FR-CNT-C04 | Featured image (Media Picker), excerpt, rich text body (multi-language) — যেখানে প্রযোজ্য | BR-MED-05 | Must |
| FR-CNT-C05 | Listing page: category, related entity, বছর / মাস দিয়ে filter; search; pagination | BR-THM-02 | Must |
| FR-CNT-C06 | Entity microsite-এ automatically সেই entity-র related content (FR-HOME-05); microsite-এর ভেতরেও listing URL (যেমন `/fsit/cse/news`) | BR-MS-07 | Must |
| FR-CNT-C07 | Details page: breadcrumb, social share button, print, "Related content" (একই category / entity) | — | Should |
| FR-CNT-C08 | View counter (bot বাদ দিয়ে), admin-এ "Most viewed" sort | — | Should |
| FR-CNT-C09 | RSS feed প্রতিটি listing-এর জন্য (`/news/feed`) | — | Could |
| FR-CNT-C10 | Content duplicate (copy) | — | Should |

### 1.1 Context-ভিত্তিক Highlight (বর্তমান `is_home`-এর উন্নত রূপ)

**সমস্যা:** News, Notice, Event অনেক থাকবে, কিন্তু homepage-এ সব দেখানো যায় না — শুধু গুরুত্বপূর্ণগুলো (highlight) দেখাতে হয়, আর পূর্ণ তালিকা আলাদা listing page-এ থাকে। বর্তমান `is_home` একটি মাত্র flag, তাই:

* শুধু main homepage-এর জন্য highlight করা যায়; CSE homepage-এর জন্য আলাদা highlight সম্ভব নয়।
* যে কোনো editor content main homepage-এ তুলে দিতে পারে।
* পুরনো highlight কেউ সরিয়ে না দিলে চিরকাল থেকে যায়।

**সমাধান:**

| ID | Requirement | Priority |
|---|---|---|
| FR-CNT-H01 | Content-এর edit form-এ "Highlight" panel: প্রতিটি context (Main Site + related entity) পাশে একটি করে highlight switch, ক্রম ও "Highlight until" তারিখ | Must |
| FR-CNT-H02 | Main Site-এ highlight করার জন্য আলাদা permission (`{module}.highlight-main`, default: Admin)। Scoped / Office / Lab user শুধু নিজের entity context-এ highlight করতে পারবে | Must |
| FR-CNT-H03 | "Highlight until" তারিখ পার হলে highlight automatically বন্ধ হবে (content listing-এ থেকে যাবে) | Must |
| FR-CNT-H04 | Homepage Builder-এর module section-এ **source mode**: (ক) Latest — সর্বশেষ N টি; (খ) Highlighted only; (গ) Highlighted first, then latest — highlight আগে, বাকি জায়গা সর্বশেষ দিয়ে পূরণ (default); (ঘ) Manual pick — নির্দিষ্ট item বেছে ক্রম ঠিক করা | Must |
| FR-CNT-H05 | প্রতিটি section-এর "View all" button সেই context-এর পূর্ণ listing page-এ নিয়ে যাবে (যেমন CSE homepage → `/fsit/cse/news`) | Must |
| FR-CNT-H06 | Admin-এ "Highlights" view: একটি context-এ বর্তমানে কী কী highlight আছে, drag & drop করে ক্রম পরিবর্তন | Should |
| FR-CNT-H07 | **Main site-এ প্রকাশ (BRD BR-CNT-08, Q-26):** Content main site-এর listing (`/news`, `/notices` ...) ও main homepage-এ দেখাবে শুধু যদি "Main site-এ প্রকাশ" চালু থাকে। এটি চালু / বন্ধ করতে পারবেন শুধু `PublishMain` permission থাকা user (default: Admin)। Admin-এর তৈরি content-এ default চালু; entity user-এর form-এ option দেখায় না — তাঁদের content শুধু নিজের entity-তে | Must |
| FR-CNT-H08 | Admin-এর জন্য "Main site-এ প্রকাশের অপেক্ষায়" তালিকা: entity user-দের সাম্প্রতিক content, এক click-এ main site-এ প্রকাশ | Should |
| FR-CNT-H09 | Entity user content-এ "Main site-এ দেখানোর অনুরোধ" চালু করতে পারবেন; Admin-এর Queue-তে "Requested" চিহ্নসহ আগে দেখায় এবং Admin notification পান; প্রকাশ বা প্রত্যাখ্যানের সিদ্ধান্ত Admin-এর (Q-26) | Should |

**উদাহরণ:**

```
News: "CSE students win ICPC Regional"   Related: Engineering Faculty, CSE Department
  ├─ Main Site            [Publish ✔] [Highlight ✔]  ক্রম 1   until 2026-11-01   ← দুটোই শুধু Admin
  ├─ Engineering Faculty              [Highlight ✔]  ক্রম 2
  └─ CSE Department                   [Highlight ✔]  ক্রম 1   until —

Main homepage "Latest News" (Highlighted first, 6 টি):  এই news প্রথমে, বাকি ৫টি main site-এ প্রকাশিত সর্বশেষ news
/news page:          main site-এ প্রকাশিত সব news, তারিখ অনুযায়ী
/fsit/cse/news: CSE-related সব news (main site-এ প্রকাশিত হোক বা না হোক)
```

> Highlight তথ্য Relation Engine-এর relation-এর অংশ হিসেবে রাখা হবে (context = target)। "Main Site" একটি বিশেষ context। বিস্তারিত Database Design-এ।

---

## M-18 News

**Purpose:** বিশ্ববিদ্যালয়, Faculty, Department, Office ও Lab-এর সংবাদ।

**Data:** title, slug, subtitle, excerpt, body, featured image, gallery image (একাধিক), video embed, author / source, publish তারিখ, category, tags, related entity, featured, SEO।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-NEWS-01 | News CRUD; `is_active` ও `active_at` (ভবিষ্যৎ তারিখে schedule) — FR-GEN-10/11 *(v0.2)* | BR-CNT-04 | Must |
| FR-NEWS-02 | Body-তে image, video embed, quote, table; শেষে photo gallery (Media Library থেকে একাধিক) | BR-MED-05 | Must |
| FR-NEWS-03 | Listing layout: Grid, List, Card, Magazine; details layout: Standard, Full-width | BR-THM-02 | Must |
| FR-NEWS-04 | Homepage section: "Latest News", "Featured News" (slider / grid), "News by Category" | FR-GEN-102 | Must |
| FR-NEWS-05 | News archive: বছর ও মাস অনুযায়ী | — | Should |
| FR-NEWS-06 | Author: Admin Panel user অথবা free text (যেমন "Public Relations Office") | — | Should |
| FR-NEWS-07 | Publish হলে (ঐচ্ছিক) Newsletter subscriber-দের কাছে পাঠানোর জন্য queue-তে যোগ (M-39 active থাকলে) | — | Could |

> **News source (BRD Q-21):** বর্তমানে news একটি আলাদা site থেকে API-র মাধ্যমে আসে। নতুন system-এ news সরাসরি এই News module-এ তৈরি ও প্রকাশ হবে; external API থেকে টেনে আনার প্রয়োজন নেই।

### Permissions

`news.view`, `news.create`, `news.update`, `news.delete`

### Acceptance Criteria

* **AC-NEWS-01:** CSE ও Engineering Faculty-তে related একটি news Engineering ও CSE microsite-এর listing-এ দেখাবে; main homepage-এ দেখাবে শুধু Main Site context-এ highlight করা থাকলে।
* **AC-NEWS-03:** CSE Department user Highlight panel-এ "Main Site" switch পরিবর্তন করতে পারবে না।
* **AC-NEWS-04:** "Highlight until" গতকাল পার হওয়া news আজ main homepage-এর highlight থেকে সরে যাবে কিন্তু `/news`-এ থাকবে।
* **AC-NEWS-02:** আগামীকাল ১০টার জন্য scheduled news সেই সময়ের আগে frontend-এ দেখাবে না, পরে automatically দেখাবে।

---

## M-19 Events

**Purpose:** Seminar, workshop, conference, cultural program, convocation ইত্যাদি।

**Data:** title, slug, বিবরণ, featured image, শুরু ও শেষ তারিখ ও সময়, all-day কিনা, venue (Campus / building / free text), online link, organizer (entity বা free text), speaker তালিকা (Person Picker বা manual), registration link ও শেষ তারিখ, category, related entity, event gallery (ছবি), recurring নয় (একক event)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-EVT-01 | Event CRUD; শুরু তারিখ বাধ্যতামূলক; শেষ তারিখ শুরুর পরে | BR-CNT-01 | Must |
| FR-EVT-02 | Upcoming / Ongoing / Past অবস্থা তারিখ থেকে automatically | — | Must |
| FR-EVT-03 | Listing layout: Calendar (month view), Card, Timeline, List; Upcoming ও Past tab | BR-THM-02 | Must |
| FR-EVT-04 | Speaker: Person Picker অথবা manual (নাম, পদবি, প্রতিষ্ঠান, ছবি) | FR-REL-09 | Should |
| FR-EVT-05 | Event শেষ হলে photo gallery যোগ (বর্তমান `event_images`) — Media Library থেকে | BR-MED-05 | Must |
| FR-EVT-06 | External registration link ও registration শেষ তারিখ; শেষ হলে "Registration closed" | — | Should |
| FR-EVT-07 | "Add to calendar" (`.ics`, Google Calendar link) | — | Should |
| FR-EVT-08 | Homepage section: "Upcoming Events" (countdown সহ ঐচ্ছিক), "Past Event Highlights" | FR-GEN-102 | Must |
| FR-EVT-09 | JSON-LD Event structured data | NFR-SEO-03 | Should |
| FR-EVT-10 | Online / Hybrid event-এর জন্য meeting link (event শুরুর আগে দেখানো ঐচ্ছিক) | — | Could |

### Permissions

`events.view`, `events.create`, `events.update`, `events.delete`

### Acceptance Criteria

* **AC-EVT-01:** শেষ তারিখ পার হওয়া event automatically "Past Events" tab-এ যাবে এবং homepage-এর "Upcoming Events" থেকে সরে যাবে।

---

## M-20 Notice / Announcement

**Purpose:** দাপ্তরিক notice (exam, admission, holiday, result, tender ইত্যাদি) — বর্তমান system-এ ৩,০০০+ notice ও ৩,৫০০ file।

**Data:** title, slug, notice নম্বর / reference (ঐচ্ছিক), বিবরণ, একাধিক attachment (title সহ), category, issuing Office / entity, publish তারিখ, মেয়াদ শেষের তারিখ, urgent / pinned, view count।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-NOT-01 | Notice CRUD; একাধিক attachment, প্রতিটির আলাদা title (বর্তমান `notice_files`) | BR-CNT-01 | Must |
| FR-NOT-02 | **Pinned / Urgent:** listing-এর শীর্ষে ও আলাদা রঙে | — | Must |
| FR-NOT-03 | মেয়াদ শেষের তারিখ (`expires_at`) পার হলে "Notice Board" থেকে সরবে কিন্তু archive-এ থাকবে | BR-CNT-04 | Must |
| FR-NOT-04 | Notice Board page (`/notices`): category, entity, তারিখ পরিসর দিয়ে filter; search (title ও notice নম্বর); layout: Table, List, Card | BR-THM-02 | Must |
| FR-NOT-05 | Attachment-এর inline PDF preview ও download | — | Should |
| FR-NOT-06 | Homepage section: "Notice Board" (সর্বশেষ N টি, pinned আগে), "Notice Ticker" (header) | FR-GEN-102 | Must |
| FR-NOT-07 | "New" badge (প্রকাশের ৭ দিন পর্যন্ত, configurable) | — | Should |
| FR-NOT-08 | Notice বিজ্ঞপ্তি email / notification subscriber-দের কাছে (Newsletter-এর notice list, ঐচ্ছিক) | — | Could |

### Permissions

`notices.view`, `notices.create`, `notices.update`, `notices.delete`

### Acceptance Criteria

* **AC-NOT-01:** ৩টি PDF সহ একটি notice তৈরি করলে details page-এ তিনটি আলাদা title-সহ download দেখাবে।
* **AC-NOT-02:** Pinned notice পুরনো হলেও Notice Board-এর শীর্ষে থাকবে যতক্ষণ pinned।

---

## M-21 Testimonials

**Purpose:** ছাত্র, অভিভাবক, alumni, employer, partner-দের মন্তব্য।

**Data:** নাম, পদবি, প্রতিষ্ঠান, ছবি, উক্তি (multi-language), rating (ঐচ্ছিক), ধরন (Student / Alumni / Parent / Employer / Partner), video (ঐচ্ছিক), ব্যক্তি link (Person Picker: Alumni ইত্যাদি, ঐচ্ছিক), related entity, ক্রম।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-TEST-01 | Testimonial CRUD; Person Picker দিলে নাম, ছবি, পদবি সেখান থেকে আসবে | FR-REL-09 | Must |
| FR-TEST-02 | Layout: Slider, Grid, Masonry, Video | BR-THM-02 | Must |
| FR-TEST-03 | Homepage section: "What People Say" (ধরন ও entity দিয়ে filter) | FR-GEN-102 | Must |
| FR-TEST-04 | Drag & drop ক্রম | FR-GEN-14 | Must |

### Permissions

`testimonials.manage`

---

## M-22 Gallery

**Purpose:** Public website-এর photo ও video album (Media Library থেকে ভিন্ন — BRD Section 7.6 নোট)।

**Data:**

| অংশ | তথ্য |
|---|---|
| Album | title, slug, বিবরণ, cover, ধরন (Photo / Video / Mixed), category (hierarchical — বর্তমান category ও sub-category), তারিখ, related entity / Event |
| Album Item | Media Library-র image বা video, অথবা video URL (YouTube / Vimeo / Facebook), caption, ক্রম |
| Video বিশেষ field | speaker / বক্তা, পদবি, প্রতিষ্ঠান, পর্ব নম্বর (Lecture Series-এর জন্য), duration |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-GAL-01 | Album CRUD; Media Library থেকে bulk select বা bulk upload করে item যোগ; drag & drop ক্রম | BR-MED-05 | Must |
| FR-GAL-02 | Video item: YouTube / Vimeo / Facebook URL (thumbnail automatically) অথবা upload | Q-17 | Must |
| FR-GAL-03 | Hierarchical category (Photo Gallery → Convocation → 2025) | — | Must |
| FR-GAL-04 | Series: Documentary, Lecture Series ইত্যাদি video series হিসেবে, পর্ব নম্বর ও বক্তা সহ | Q-17 | Should |
| FR-GAL-05 | Layout: Masonry, Grid, Album (cover দিয়ে), Carousel; lightbox (keyboard ও swipe সহ) | BR-THM-02 | Must |
| FR-GAL-06 | Event-এর সাথে album link: Event page-এ gallery দেখাবে | FR-EVT-05 | Should |
| FR-GAL-07 | Homepage section: "Photo Gallery", "Video Gallery" | FR-GEN-102 | Must |
| FR-GAL-08 | Album-এর সব ছবি zip download (ঐচ্ছিক setting) | — | Could |

### Business Rules

* **BRL-GAL-01:** Album item Media Library file reference করবে, copy নয়; একই ছবি একাধিক album-এ থাকতে পারবে। *(v0.3: album-এর ছবি = Media Library item-এর ব্যবহার, আলাদা item record নেই — DB-05 v1.6 §7; YouTube / Vimeo / Facebook link-এর জন্য শুধু video row।)*

### Permissions

`gallery.view`, `gallery.create`, `gallery.update`, `gallery.delete`

---

## M-23 Slider / Banner

**Purpose:** Homepage ও microsite-এর hero slider, page banner ও cover photo।

**Data:** title, subtitle / text (multi-language), image (desktop ও mobile আলাদা), video background (ঐচ্ছিক), একাধিক button (text, link, style), bullet point (ঐচ্ছিক, বর্তমান program slider-এর মতো), text position / overlay, slider group / placement, related entity, শুরু ও শেষ তারিখ, ক্রম।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-SLD-01 | Slide CRUD; desktop ও mobile image আলাদা (mobile না দিলে desktop crop) | NFR-02 | Must |
| FR-SLD-02 | ০–২টি button, বর্তমান program slider-এর মতো ০–৪টি bullet point | — | Must |
| FR-SLD-03 | Placement: Main homepage, entity microsite hero (related entity দিয়ে), page banner | BR-MS-07 | Must |
| FR-SLD-04 | Schedule: শুরু ও শেষ তারিখ (যেমন admission campaign) *(v0.4: `active_at` / `expires_at`)* | — | Must |
| FR-SLD-05 | Slider settings (Homepage Builder section-এ): autoplay, interval, transition, arrow / dot *(v0.4: একটি slide = স্থির hero; slide-এ background video — autoplay, muted, loop, ছবি poster, phone-এ ঐচ্ছিক; একটি slide অনেক জায়গায় — DB-05 v1.7 §8)* | FR-HOME-04 | Must |
| FR-SLD-06 | Microsite-এর নিজস্ব slide না থাকলে cover photo / default banner (theme-এর default অথবা parent entity-র) | BR-MS-05 | Should |
| FR-SLD-07 | Image-এর জন্য প্রস্তাবিত size দেখানো ও crop tool | BR-MED-12 | Should |

### Permissions

`slider.manage` (scope অনুযায়ী)

### Acceptance Criteria

* **AC-SLD-01:** BSc in CSE-এর সাথে related ৩টি slide শুধু Program page-এর hero-তে দেখাবে, main homepage-এ নয় (Show on main homepage না দিলে)।

---

## M-24 Achievements

**Purpose:** বিশ্ববিদ্যালয়, ছাত্র, শিক্ষক ও দলের অর্জন (প্রতিযোগিতা, award, ranking-এ অবস্থান ইত্যাদি)।

**Data:** title, slug, বিবরণ, তারিখ, অর্জনকারী (ছাত্র / দল / শিক্ষক / প্রতিষ্ঠান — Person Picker বা free text), প্রতিযোগিতা / প্রদানকারী প্রতিষ্ঠান, পর্যায় (International / National / Regional), অবস্থান (Champion / Runner-up / Award), ছবি, category, related entity।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-ACH-01 | Achievement CRUD *(v0.5: অর্জনকারী Person Picker দিয়ে, দলের নাম ঐচ্ছিক — DB-05 v1.8 §9)* | FR-REL-09 | Must |
| FR-ACH-02 | Listing: বছর, পর্যায়, category, entity দিয়ে filter; layout: Card, Timeline, Grid | BR-THM-02 | Must |
| FR-ACH-03 | Homepage section: "Recent Achievements", counter ("100+ International Awards") | FR-GEN-102 | Should |
| FR-ACH-04 | Related News link (একই অর্জনের সংবাদ) | — | Could |

### Permissions

`achievements.manage` (scope অনুযায়ী)

---

## M-25 Publications

> **Optional module** (BRD Q-22): install থাকবে কিন্তু default-এ inactive; Super Admin activate করলে চালু হবে। Inactive থাকলে Faculty Member profile-এর Publication section ও সংশ্লিষ্ট Statistics metric লুকানো থাকবে।

**Purpose:** শিক্ষক ও গবেষকদের প্রকাশনা (journal article, conference paper, book, chapter) এবং বিশ্ববিদ্যালয়ের journal তালিকা।

**Data:** title, author তালিকা (অভ্যন্তরীণ: Person Picker — Faculty Member; বাইরের: নাম), প্রকাশের ধরন, journal / conference / publisher, volume, issue, page, বছর, DOI, URL, abstract, keyword, indexing (Scopus, WoS ইত্যাদি), quartile (Q1–Q4), PDF (ঐচ্ছিক), related entity।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-PUB-01 | Publication CRUD; author ক্রম সহ, অভ্যন্তরীণ author Faculty Member-এর সাথে link | FR-REL-09 | Must |
| FR-PUB-02 | DOI দিলে metadata (title, author, journal, বছর) automatically পূরণ (Crossref API) | — | Should |
| FR-PUB-03 | BibTeX / CSV import (bulk) | — | Should |
| FR-PUB-04 | Listing: বছর, ধরন, indexing, quartile, Department, author দিয়ে filter; citation format (APA / IEEE) দেখানো | BR-THM-02 | Must |
| FR-PUB-05 | Faculty Member profile-এ তাঁর publication তালিকা automatically (FR-FM-04) | — | Must |
| FR-PUB-06 | Statistics: Department / বছর অনুযায়ী publication সংখ্যা | — | Could |
| FR-PUB-07 | বিশ্ববিদ্যালয়ের নিজস্ব journal (বর্তমান `affiliate/diujst` ইত্যাদি): নাম, ISSN, website link, cover — "Our Journals" তালিকা | — | Should |

### Business Rules

* **BRL-PUB-01:** একই DOI দুইবার যোগ করা যাবে না।

### Permissions

`publications.manage` (scope অনুযায়ী), `publications.import`

---

## M-26 Research

> **Optional module** (BRD Q-22): install থাকবে কিন্তু default-এ inactive। Inactive থাকলে Lab microsite-এর Research section লুকানো থাকবে।

**Purpose:** গবেষণা প্রকল্প, research area ও funded project।

**Data:** title, slug, সারাংশ, বিস্তারিত, principal investigator ও team (Person Picker / manual), funding সংস্থা, অনুদানের পরিমাণ (ঐচ্ছিক, public কিনা), শুরু ও শেষ তারিখ, অবস্থা (Ongoing / Completed), research area (Taxonomy), output (Publication link), ছবি, related entity (Department, Lab ইত্যাদি)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-RES-01 | Research project CRUD; team Person Picker দিয়ে, ভূমিকা সহ (PI, Co-PI, Member) | FR-REL-09 | Must |
| FR-RES-02 | Research area Taxonomy; area page (`/research/area/{slug}`) সংশ্লিষ্ট project, publication ও শিক্ষক সহ | — | Should |
| FR-RES-03 | Project-এর output হিসেবে Publication link (M-25 active থাকলে) | — | Should |
| FR-RES-04 | Listing: অবস্থা, area, Department, Lab, funding সংস্থা, বছর দিয়ে filter | BR-THM-02 | Must |
| FR-RES-05 | Lab microsite-এ Lab-এর project (বর্তমান `lab_projects`, `lab_research`) — Relation দিয়ে | BR-MS-07 | Must |
| FR-RES-06 | Homepage section: "Research Highlights", counter (চলমান project, মোট অনুদান) | FR-GEN-102 | Could |

### Permissions

`research.manage` (scope অনুযায়ী)

---

## M-27 Downloads / Documents

**Purpose:** Download যোগ্য document-এর কেন্দ্রীয় ভাণ্ডার: form, policy, prospectus, flipbook, guidebook, charter, report, syllabus ইত্যাদি।

**Data:** title, slug, বিবরণ, document ধরন (Form / Policy / Prospectus / Flipbook / Guidebook / Charter / Report / Other — Taxonomy), file (Media Library) অথবা external link, flipbook (PDF থেকে online flip view), thumbnail, version / অনুমোদনের তারিখ (Policy-এর জন্য), কার্যকর তারিখ, প্রযোজ্য Session / বছর ও Program (Prospectus-এর জন্য), icon, related entity, download count।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-DOC-01 | Document CRUD; file Media Picker দিয়ে, অথবা external URL | BR-MED-05 | Must |
| FR-DOC-02 | Document ধরন Taxonomy; ধরন অনুযায়ী অতিরিক্ত field (Policy: অনুমোদনের তারিখ, version; Prospectus: Session, Program) *(v0.6: ধরন নির্দিষ্ট তালিকা, category আলাদা Taxonomy — DB-05 v1.9 §12)* | Q-17 | Must |
| FR-DOC-03 | Flipbook view: শুধু PDF upload; system PDF থেকে online পাতা-উল্টানো viewer দেখাবে (বর্তমান flipbook ZIP-এর বদলে) *(v0.6: নিজস্ব — PDF.js self-hosted + Alpine; দুই পাতা / এক পাতা, zoom, thumbnail, full screen, `?page=`, শুধু-পড়া option; পুরোনো paid flipbook লাগবে না)* | Q-17, Q-23 | Should |
| FR-DOC-04 | Downloads page (`/downloads`): ধরন, entity, বছর দিয়ে filter; search; layout: Table, Card, List | BR-THM-02 | Must |
| FR-DOC-05 | Policy-এর version history: নতুন version upload করলে পুরনোটি "Superseded" হিসেবে রাখা | — | Should |
| FR-DOC-06 | Download count | — | Should |
| FR-DOC-07 | Private document: শুধু logged-in admin user দেখতে পারবে (signed URL) | BRL-MEDIA-02 | Could |
| FR-DOC-08 | Homepage section: "Downloads", "Prospectus" | FR-GEN-102 | Should |

### Permissions

`documents.view`, `documents.create`, `documents.update`, `documents.delete`

### Acceptance Criteria

* **AC-DOC-01:** "Policy" ধরন select করলে অনুমোদনের তারিখ ও version field দেখাবে; "Prospectus" select করলে Session ও Program field দেখাবে।

---

## M-28 FAQ

**Purpose:** প্রায়শ জিজ্ঞাসিত প্রশ্ন (বর্তমানে ১,৩০০+), এবং accordion-ধরনের তথ্য section।

**Data:** প্রশ্ন, উত্তর (rich text, multi-language), category (hierarchical; category-র উপরে ও নিচে বিবরণ — বর্তমান accordion category-র মতো), related entity, ক্রম, helpful count (ঐচ্ছিক)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-FAQ-01 | FAQ CRUD; hierarchical category, category-র ছবি ও উপরের / নিচের বিবরণ | Q-17 | Must |
| FR-FAQ-02 | FAQ page (`/faq`): category অনুযায়ী accordion; instant search | BR-THM-02 | Must |
| FR-FAQ-03 | Entity page-এ সেই entity-র FAQ (যেমন Program page-এ Program FAQ, Admission page-এ Admission FAQ) | BR-MS-07 | Must |
| FR-FAQ-04 | FAQPage JSON-LD structured data | NFR-SEO-03 | Should |
| FR-FAQ-05 | "Was this helpful?" (Yes / No count) | — | Could |
| FR-FAQ-06 | Excel import | — | Should |

### Permissions

`faq.manage` (scope অনুযায়ী)

---

## M-29 Important Links

**Purpose:** গুরুত্বপূর্ণ external ও internal link-এর তালিকা (UGC, Student Portal, Library, Email ইত্যাদি)।

**Data:** title, URL, icon / logo, বিবরণ, group (Taxonomy), নতুন tab-এ খুলবে কিনা, ক্রম।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-LINK-01 | Link CRUD, group ও ক্রম | — | Must |
| FR-LINK-02 | Important Links page ও footer block / sidebar widget *(v0.7: page `/links` ও `/{entity}/links`, homepage section, footer block kernel `$r->footerBlock()` দিয়ে; sidebar widget M-30-এর সাথে — DB-05 v1.10 §14)* | BR-THM-05 | Must |
| FR-LINK-03 | Broken link check (সাপ্তাহিক), admin-এ সতর্কতা | — | Could |

### Permissions

`links.manage`

---

## M-30 Announcement Widgets

**Purpose:** দৃষ্টি আকর্ষণকারী ছোট widget — Greetings popup, News Ticker, Countdown Timer, Announcement bar।

**Data:**

| Widget ধরন | তথ্য |
|---|---|
| Popup / Greetings | title, বিষয়বস্তু (rich text / image), button, কোন page-এ (Main homepage / Admission / Faculty / Department / Program — related entity), শুরু ও শেষ তারিখ, কত বার দেখাবে (প্রতি visit / প্রতিদিন একবার / একবার) |
| News Ticker | text, link, ক্রম, related entity |
| Countdown Timer | title, target তারিখ ও সময়, link, শেষ হলে কী দেখাবে |
| Announcement Bar | text, link, রং (theme token থেকে), dismiss করা যাবে কিনা |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-ANW-01 | চার ধরনের widget CRUD | Q-17 | Must |
| FR-ANW-02 | Placement: Main homepage, সব page, নির্দিষ্ট entity microsite (Relation দিয়ে — বর্তমান `is_home`, `is_faculty`, `is_department`, `is_program`, `is_admission` flag-এর বদলে) | BR-CNT-02 | Must |
| FR-ANW-03 | Schedule: শুরু ও শেষ তারিখ | — | Must |
| FR-ANW-04 | Popup frequency control (cookie দিয়ে) এবং accessible (Esc দিয়ে বন্ধ, focus trap) | NFR-USE-02 | Must |
| FR-ANW-05 | একই page-এ একসাথে একটির বেশি popup দেখাবে না (priority ক্রম অনুযায়ী) | — | Must |
| FR-ANW-06 | News Ticker-এ Notice module-এর সর্বশেষ notice automatically যোগ করার option | FR-NOT-06 | Should |
| FR-ANW-07 | Countdown শেষ হলে লুকানো অথবা "Event started" বার্তা | — | Should |

### Permissions

`announcement-widgets.manage` (scope অনুযায়ী)

### Acceptance Criteria

* **AC-ANW-01:** Admission page-এর জন্য তৈরি popup শুধু Admission page-এ দেখাবে, দিনে একবার।

---

## বর্তমান System থেকে পরিবর্তন (Migration Note)

| বর্তমান (`project_diu`) | নতুন system |
|---|---|
| News (বর্তমানে আলাদা site থেকে API) | Migration নেই; নতুন News module-এ নতুন করে তৈরি হবে (Q-21) |
| `articles`, `blog_posts`, `blog_categories`, `blog_tags` | Pages (C-11), অথবা প্রয়োজনে News — বিষয়বস্তু দেখে নির্ধারণ |
| `department_articles`, `faculty_articles`, `program_articles`, `lab_articles` | Pages (C-11), owner entity সহ |
| `contents`, `content_categories` | Documents (M-27) বা Pages, বিষয়বস্তু অনুযায়ী |
| `events`, `event_categories`, `event_images` | Events (M-19) + Gallery album |
| `notices`, `notice_categories`, `notice_files` | Notice (M-20) |
| `testimonials` (ইতিমধ্যে polymorphic), `mba_testimonials` | Testimonials (M-21) |
| `gallery_categories`, `gallery_sub_categories`, `gallery_folders`, `gallery_photos`, `lab_galleries` | Gallery (M-22), hierarchical category |
| `documentaries`, `lecture_series`, `program_videos` | Gallery video series |
| `sliders`, `cover_photos`, `program_sliders`, `mba_sliders` | Slider / Banner (M-23) |
| `flipbooks`, `prospectuses`, `charter_guidebooks`, `policies` | Documents (M-27), ধরন অনুযায়ী |
| `faqs`, `faq_categories`, `accordions`, `accordion_categories` | FAQ (M-28) |
| `greetings`, `news_tickers`, `countdown_timers` | Announcement Widgets (M-30) |
| সব table-এর `department_id`, `lab_facility_id` | Relation Engine (C-13) |
| সব table-এর `is_home` | Main Site context-এ Highlight (Section 1.1) |

---

## Traceability Summary (BRD → SRS Part 4)

| BRD Requirement | SRS Requirement |
|---|---|
| BR-CNT-02/03 (Polymorphic relation) | FR-CNT-C01/C02/C06, FR-ANW-02, FR-SLD-03 |
| BR-CNT-07 (Context highlight) | FR-CNT-H01 থেকে H06 |
| BR-CNT-08 (Main site-এ প্রকাশ) | FR-CNT-H07/H08 |
| BR-CNT-04 (Status) | FR-NEWS-01, FR-NOT-03, FR-SLD-04, FR-ANW-03 |
| BR-THM-02 (Layout) | প্রতিটি module-এর listing layout requirement |
| BR-THM-04 (Homepage section) | প্রতিটি module-এর homepage section requirement |
| BR-MED-05 (Media Picker) | FR-CNT-C04, FR-GAL-01, FR-DOC-01 |
| Q-17 (একত্রীকরণ) | FR-GAL-02/04, FR-DOC-02/03, FR-FAQ-01, FR-ANW-01 |
