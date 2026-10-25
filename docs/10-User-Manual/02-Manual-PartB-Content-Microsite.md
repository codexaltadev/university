# User Manual — Part B: Content ও Microsite

| বিষয় | তথ্য |
|---|---|
| Document | User Manual (Admin Panel) — Part B (SDLC ধাপ ১০) |
| Version | 0.1 (Draft) |
| Date | 2026-10-08 |
| Status | Review-এর জন্য অপেক্ষমাণ |
| পাঠক | Content editor; Faculty / Department / Program / Office / Lab-এর microsite editor; Admission Office ও Waiver Office-এর user |

> আগে [Part A](01-Manual-PartA-Basics.md) পড়ুন — সব form-এর সাধারণ নিয়ম (Active, Publish date, Show in, ভাষা, Media Library, Trash) সেখানে। এখানে শুধু প্রতিটি কাজের ধাপ ও বিশেষ ঘর।
>
> আপনার role-এ যে module-এর অনুমতি নেই বা যে module বন্ধ, তার অংশ আপনার screen-এ দেখাবে না।

## সূচি

**Content**
1. [Notice](#1-notice)
2. [News](#2-news)
3. [Event](#3-event)
4. [Page](#4-page)
5. [Gallery ও Video](#5-gallery-ও-video)
6. [Slider ও Banner](#6-slider-ও-banner)
7. [Documents (Prospectus, Guidebook, Flipbook)](#7-documents-prospectus-guidebook-flipbook)
8. [FAQ](#8-faq)
9. [Announcement (Popup, Ticker, Countdown)](#9-announcement-popup-ticker-countdown)
10. [Achievement ও Testimonial](#10-achievement-ও-testimonial)

**My Microsite**
11. [Overview](#11-overview)
12. [Menu](#12-menu)
13. [Homepage সাজানো](#13-homepage-সাজানো)
14. [Header ও Footer](#14-header-ও-footer)
15. [Statistics](#15-statistics)
16. [People (Head, Team, Coordinator …)](#16-people-head-team-coordinator-)

**Academic**
17. [Program](#17-program)
18. [Tuition Fee ও Waiver](#18-tuition-fee-ও-waiver)
19. [Scholarship](#19-scholarship)
20. [Admission](#20-admission)
21. [Academic Calendar](#21-academic-calendar)

**Communication**
22. [Inquiry (website-এর যোগাযোগ form)](#22-inquiry-websiteএর-যোগাযোগ-form)
23. [Newsletter](#23-newsletter)

---

## 1. Notice

**Content › Notices › + New notice**

| ঘর | কী দেবেন |
|---|---|
| Title * | Notice-এর শিরোনাম |
| Reference no. | যেমন `DIU/REG/2026/551` — website-এ এই নম্বর দিয়েও খোঁজা যায় |
| Body | বিস্তারিত (ঐচ্ছিক, যদি PDF-ই মূল হয়) |
| **Attachments** tab | এক বা একাধিক PDF / ছবি (Media Library থেকে); প্রতিটির আলাদা title (যেমন "Slot A", "Slot B") |
| Category | Exam, Admission, Holiday … |
| **Pinned** (ডানে) | চালু করলে তালিকার শীর্ষে, আলাদা রঙে (📌 জরুরি notice-এর জন্য) |
| **Expires** | এই তারিখের পর Notice Board থেকে সরবে, archive-এ থাকবে |

**ধাপ:** Title → Attachment যোগ → Category → Show in ঠিক আছে কিনা দেখুন → **Save**।

* প্রকাশের পর ৭ দিন "New" চিহ্ন দেখায়।
* প্রতি semester-এর একই ধরনের notice: আগেরটি তালিকা থেকে **⋯ › Duplicate** করে বদলান।
* Website-এ (DIU-র মতো) `/notices`: **All Departments** ও প্রতিটি faculty-র tab — তার উপর রাখলে / চাপলে faculty-র department-গুলো (যাদের notice main site-এ আছে), অন্য জায়গা (office …) **Others**-এ; খোঁজা ও **Archive**; প্রতিটি notice-এ তারিখের বাক্স, কোথাকার, ধরন, তারিখ-সময়, কতবার দেখা। Notice-এর পেজে উপরে **View/Download** (প্রথম file), নিচে সব file তাদের title-সহ, PDF হলে Preview। উপরের শিরোনাম ও ছবি **Settings › Notices**-এ। DIU-র পুরনো `/noticeboard` ও `/noticeboard/{slug}` ঠিকানা নিজে নতুনটিতে যায়।

---

## 2. News

**Content › News › + New news**

| ঘর | কী দেবেন |
|---|---|
| Title *, Summary | Summary তালিকা ও homepage card-এ দেখায় (২–৩ বাক্য) |
| Body | Rich text — heading, list, ছবি, link, YouTube embed |
| Featured image * | Card ও detail-এর বড় ছবি (১৬০০ × ৯০০ ভালো) |
| Gallery | অতিরিক্ত ছবি (Media Library থেকে একাধিক) |
| Category / Tags | Achievement, Seminar … |

* Homepage-এ সামনে আনতে: **Highlight** (Part A §9)।
* মূল website-এ চাইলে: **Request main site**।
* Website-এ (DIU News-এর মতো) `/news`: প্রথম পাতায় **Latest** — বড় শিরোনাম, লেখা, নতুনতম দুটি সংবাদ; তারপর ছবির উপর category ও শিরোনামসহ card; পাশে **All News**, খোঁজা, **Filter by Category** ও মাস অনুযায়ী archive। সংবাদের পেজে ছবির উপর category, তারিখ ও **Hit** (কতবার পড়া), লেখা, তারপর হলুদ "Explore the Inspiring Stories…" ও আরও তিনটি সংবাদ। লেখাগুলো **Settings › News**-এ। Appearance › Layouts-এ news detail **Full width** দিলে DIU News-এর মতো চওড়া, **Standard** দিলে লেখা একটু সরু।

---

## 3. Event

**Content › Events › + New event**

| ঘর | কী দেবেন |
|---|---|
| Title *, Description | — |
| Starts * / Ends | তারিখ ও সময়; Upcoming / Ongoing / Past নিজে থেকে হিসাব হয় |
| Venue | স্থান; ঐচ্ছিক map link |
| Speakers | **+ Add person** → directory থেকে খুঁজুন (Faculty Member, Administration …), না পেলে **Add new person** (নাম, পদবি, প্রতিষ্ঠান, ছবি) |
| Registration link, Registration ends | শেষ তারিখ পেরোলে "Registration closed" নিজে থেকে |
| Featured image | — (তালিকার card ও event-এর পেজের উপরের ছবি) |

Website-এ (DIU-র মতো) `/events`: খোঁজা, **Upcoming Events / Archive Events / Calendar**, **Select Faculty** (সেই faculty বা তার department-এ দেখানো event, যা main site-এও আছে), ধরন থাকলে তার chip, তারপর তারিখসহ card। Event-এর পেজে ছবি, বিবরণ আর পাশে **Event Details** (department, তারিখ, সময়, স্থান, ধরন, faculty), registration / online / calendar-এর বোতাম, speaker ও ছবি। উপরের শিরোনাম, লেখা, ছবি ও শিরোনামের নিচের লাইন **Settings › Events**-এ। DIU-র পুরনো `/events-detail/{id}` ঠিকানা নিজে নতুনটিতে যায় (Site › Redirects)।

**Event শেষে ছবি:** Event-টি edit করে **Gallery** tab-এ ছবি যোগ করুন — website-এ event page-এর নিচে gallery দেখাবে।

---

## 4. Page

About, Message from Head, Lab Facilities-এর মতো স্থায়ী page।

**My Microsite › Pages** (নিজের microsite) বা **Content › Pages** → **+ New page**

| ঘর | কী দেবেন |
|---|---|
| Title * | — |
| **Template** | Default, Full width, With sidebar, Landing |
| Content blocks | **+ Add block** → Text, Image, Gallery, Video, File list, Accordion, Contact form, People list, Call-to-action, **HTML (যেমন লেখা)** … টেনে ক্রম বদলানো যায়। HTML block-এ class সহ HTML (table, card, accordion) রাখা যায় — পুরনো site থেকে আসা page-গুলো এতেই আছে; default theme এগুলো DIU-র মতো Bootstrap 5 দিয়ে দেখায়, script ও frame বাদ যায় |
| Parent page | Page-এর ভেতরে page (যেমন About › History) |

Page তৈরি করে **Menu**-তে যোগ না করলে visitor সহজে খুঁজে পাবেন না (Section 12)।

---

## 5. Gallery ও Video

**Content › Gallery › + New album**

* ছবি থাকে **Media Library**-তে — album শুধু সেগুলো ব্যবহার করে, নতুন copy হয় না। একই ছবি অনেক album, News বা Event-এ দেওয়া যায়; Media Library-তে ছবি বদলালে সবখানে বদলায়।
* অনেক ছবি একসাথে: আগে **Media › Upload** দিয়ে একটি folder-এ (যেমন "Convocation 2026") তুলে নিন, তারপর album-এ **Add a folder** — folder-এর সব ছবি ও video একবারে যোগ হয়। অথবা **Pick from the library** দিয়ে কয়েকটি বাছুন (সেখান থেকেও upload করা যায়)।
* ছবির ক্রম টেনে বদলান। **Caption here** ঐচ্ছিক — শুধু এই album-এর জন্য; খালি রাখলে Media Library-র caption দেখায়।
* নাম, বিবরণ, তারিখ, **Cover** (খালি = প্রথম ছবি), **Series** (Documentary / Lecture series), **Event** (দিলে Event-এর page-এ album দেখাবে), **Category**, **Show in**।
* YouTube / Vimeo / Facebook video: album save করার পর নিচে **Videos** tab → **New** — link, শিরোনাম, পর্ব, দৈর্ঘ্য, বক্তা (Person Picker)। Video file হলে ছবির মতোই Media Library থেকে যোগ করুন।
* Website: `/gallery` (Photos / Videos, category), album page-এ ছবি বড় করে দেখা (← → Esc, phone-এ swipe); layout Appearance › Layouts-এ (masonry / grid / carousel)। Homepage: **Photo Gallery** ও **Video Gallery** section।
* Default theme-এ (DIU-র মতো): বাম পাশে **Browse by Category** — প্রতিটি category খুললে তার album-গুলো (category ছাড়া album **Other albums**-এ); category বাছলে তার সব album-এর ছবি, album বাছলে শুধু সেটির — প্রতি পাতায় ২০টি, "Showing 1 - 20 of 159 photos", চাপলে বড় করে দেখা। কিছু না বাছলে album-এর card। ছবিগুলো Media Library-র item-ই (copy নয়)। উপরের শিরোনাম, লেখা ও ছবি **Settings › Gallery**-এ।

---

## 6. Slider ও Banner

**Content › Slider › + New slide**

একটি জায়গার (main site, department, program, যেকোনো microsite) **hero = সেখানে দেখানো slide-গুলো**: **একটি slide = স্থির বড় ছবি / video**, একাধিক = slider।

| ঘর | কী দেবেন |
|---|---|
| Use | **Hero slide** (homepage-এর উপরের বড় অংশ) বা **Page banner** (microsite-এর cover photo — hero slide না থাকলে এটি দেখায়) |
| Title, Subtitle, Points, Buttons | ঐচ্ছিক; সর্বোচ্চ ৪টি point (যেমন "96% Placement Record"), ২টি button |
| Picture * | Media Library থেকে, ১৯২০ × ১০৮০; **Picture on phones** ১০৮০ × ১৩৫০ (না দিলে উপরেরটি crop) |
| Background video | Media Library-র ছোট, শব্দহীন MP4 / WebM — DIU-র এখনকার homepage-এর মতো লেখার পেছনে চলতে থাকে; ছবিটি poster। **Video on phones too** বন্ধ থাকলে phone-এ শুধু ছবি (data বাঁচে) |
| Text, Darken | লেখা বামে / মাঝে / ডানে; ছবি কতটা অন্ধকার (লেখা পড়ার জন্য) |
| Show in | **একসাথে অনেক জায়গা** — একই slide CSE, EEE ও main site-এ; বারবার বানাতে হয় না |
| Show from, Expires | Admission campaign-এর মতো সাময়িক slide — নির্দিষ্ট দিন থেকে নির্দিষ্ট দিন পর্যন্ত |

ক্রম: তালিকায় **Reorder** দিয়ে টেনে সাজান। Homepage-এ **Hero** section বসান (My Microsite › Homepage) — autoplay, কত সেকেন্ড পর পর, slide / fade, arrow, dot, উচ্চতা (পুরো screen / বড় / মাঝারি)। কোনো microsite-এর নিজের slide বা banner না থাকলে তার parent-এর (যেমন department-এর না থাকলে faculty-র) hero দেখায়। ছবির উপর লেখা পড়া যায় কিনা দেখে নিন।

---

## 7. Documents (Prospectus, Guidebook, Flipbook)

**Content › Documents › + New document**

* **Type:** Form, Policy, Prospectus, Guidebook, Charter, Report, Other — ধরন অনুযায়ী ঘর বদলায়: **Policy** হলে Version, অনুমোদনের তারিখ, কার্যকর তারিখ; **Prospectus** হলে Session ("Fall 2026") ও Program।
* **File:** Media Library থেকে (copy হয় না)। File অন্য কোথাও থাকলে (Google Drive) **Or an outside address**। **Cover:** কার্ড ও Prospectus section-এর ছবি।
* **Flipbook:** চালু করলে PDF website-এ বইয়ের মতো খোলে — desktop-এ পাশাপাশি দুই পাতা, phone-এ এক পাতা; পাতা উল্টানো, পাতার ছোট ছবি (Pages), zoom, পুরো screen, নির্দিষ্ট পাতায় যাওয়া, পাতার link (`?page=12`)। শুধু PDF upload করলেই হবে — আলাদা ZIP বা software লাগে না।
* **Can be downloaded:** বন্ধ করলে শুধু পড়া যাবে, Download button থাকবে না। **Private:** শুধু login করা user দেখবেন।
* **নতুন version** (যেমন নীতিমালা হালনাগাদ): document খুলে উপরে **New version** → নতুন file, version ও তারিখ দিন। নতুনটি একই জায়গায় দেখাবে; পুরোনোটি তালিকা থেকে সরে যায়, তবে তার link কাজ করে এবং "নতুন version আছে" জানায়।
* **Show in** দিয়ে department / office-এ; website: `/downloads` (ধরন, category, বছর, খোঁজা; table / cards / list)। Download সংখ্যা তালিকায় দেখা যায়। Homepage: **Downloads** ও **Prospectus** section; যেকোনো Page-এ **Documents** block (তালিকা বা flipbook)।
* Default theme-এ (DIU-র "Digital Brochures"-এর মতো) `/downloads`: উপরে শিরোনাম ও একটি লাইন (**Settings › Documents**-এ বদলানো যায়; শুরুতে "Downloads"), **Type** ও **Category** বাছার pill, খোঁজা; প্রতিটি document একটি card — thumbnail (Media Library থেকে; না থাকলে রঙিন জায়গা), ধরন, শিরোনাম, session / version / program / file-এর আকার, **View Flipbook** (flipbook চালু থাকলে) বা **View PDF**, পাশে download চিহ্ন; file না থাকলে **Details**। Document-এর page-এ উপরে ধরন, শিরোনাম, তথ্য ও **Download**, তারপর flipbook বা PDF।

---

## 8. FAQ

**Content › FAQ › + New FAQ**

* **Question** ও **Answer** (দুই ভাষায়)।
* **Category** (যেমন Admission, Waiver) — website-এ category অনুযায়ী ভাগ হয়ে দেখায়।
* তালিকায় ⠿ দিয়ে ক্রম বদলান।
* Website-এ visitor "এই উত্তর কাজে লেগেছে?" 👍 / 👎 দিতে পারেন — তালিকায় সংখ্যা দেখে কোন উত্তর ভালো করতে হবে বুঝুন।

---

## 9. Announcement (Popup, Ticker, Countdown)

**Content › Announcements › + New**

| Type | কোথায় দেখায় | উদাহরণ |
|---|---|---|
| **Bar** | সব page-এর একদম উপরে, সরু লাইন | "Spring 2027 admission চলছে — Apply now →" |
| **Ticker** | Header-এর নিচে চলমান লেখা | জরুরি notice শিরোনাম |
| **Popup** | Page খুললে ছোট window | ঈদ শুভেচ্ছা, admission ঘোষণা |
| **Countdown** | Homepage-এ সময় গণনা | "Admission শেষ হতে ৫ দিন" |

> **আগে চালু করুন — Settings › Announcements:** চারটি ধরনের প্রতিটি (Popup, News ticker, Announcement bar, Countdown) website-এ দেখাবে **শুধু সেখানে চালু করলে**; শুরুতে সবগুলো বন্ধ। বন্ধ থাকলে সেই ধরনের কোনো announcement-ই কোথাও দেখাবে না (তৈরি করা থাকলেও)। Announcements তালিকার উপরে লেখা থাকে কোন ধরনগুলো বন্ধ; form-এ বন্ধ ধরন বাছলেও সতর্কবার্তা আসে। একই জায়গায় ticker-এর নাম ("Latest") ও popup বন্ধের button-এর লেখা ("Maybe Later") বদলানো যায়।

* Default theme-এ (DIU-র greeting popup-এর মতো): popup-এ উপরে ছবি (link থাকলে ছবিতে চাপলেও যায়), নিচে লেখা, শুরুর তারিখ ও "Valid till …", **Maybe Later** ও **Explore Now** (button-এর লেখা না দিলে)। Bar ও countdown header-এর নিচে DIU-র নীল রঙে (অথবা বাছা রঙে), countdown-এ দিন / ঘণ্টা / মিনিট / সেকেন্ডের ঘর; ticker গাঢ় নীল লাইনে, বাঁয়ে "Latest"।
* **Show from / Expires** দিন — পুরনো popup যেন থেকে না যায়।
* **Popup:** ছবি (Media Library), লেখা, button; **How often** — প্রতি visit / দিনে একবার / শুধু একবার (দর্শকের browser মনে রাখে); একই page-এ একটিই popup দেখায় — যার **Priority** বেশি। দর্শক Esc চেপে বা × দিয়ে বন্ধ করতে পারেন।
* **Ticker:** এক লাইনের লেখা ও link; **Add the latest notices** চালু করলে এই site-এর notice board-এর নতুন notice-ও চলে।
* **Countdown:** কোন মুহূর্ত পর্যন্ত; শেষ হলে লুকাবে নাকি বার্তা ("শুরু হয়ে গেছে!") দেখাবে।
* **Bar:** রং (theme-এর রং থেকে), দর্শক বন্ধ করতে পারবেন কিনা।
* **Pages:** শুধু homepage, সব page, বা **Chosen pages** — module page (Notices, Downloads …) অথবা এই site-এর যেকোনো ঠিকানা লিখে যোগ করুন, যেমন `/admission` (তার নিচের page-গুলোতেও)।
* **Show in:** কোন department / program / faculty-র site-এ; main site-এরটা শুধু main site-এ দেখায়।

---

## 10. Achievement ও Testimonial

* **Achievements** (**Content › Achievements**): পুরস্কার, প্রতিযোগিতা জয়।
  * Title, তারিখ, **Level** (International / National / Regional / Institutional), প্রতিযোগিতা / পুরস্কার, প্রদানকারী, স্থান (Champion, Runner-up …), দলের নাম, বিস্তারিত।
  * **Achieved by** — Person Picker দিয়ে শিক্ষার্থী, শিক্ষক বা alumni (তাঁদের directory থেকে নাম-ছবি আসে) বা নতুন ব্যক্তি; প্রত্যেকের note (যেমন "Team lead")।
  * ছবি ও আরো ছবি Media Library থেকে; **Related news** — একই অর্জনের সংবাদ থাকলে বেছে দিন।
  * **Show in** দিয়ে department / program / club-এ; Admin চাইলে main site-এ। Website: `/achievements` (বছর, level, category; cards / timeline / grid)। Homepage: **Recent Achievements** section (চাইলে শুধু International)। Default theme-এ (DIU-র event card-এর মতো): উপরে **All / International / National / Regional / Institutional** switch, বছর ও category; প্রতিটি card-এ ছবি, তার উপর level ও স্থান (যেমন "International · Champion"), শিরোনাম, তারিখ; অর্জনের page-এ পাশে **Details** (তারিখ, level, প্রতিযোগিতা, প্রদানকারী …)। Statistics-এ counter: **Achievements** metric, level দিয়ে (যেমন "100+ International Awards")।
* **Testimonials** (**Content › Testimonials**): শিক্ষার্থী, alumni, অভিভাবক, নিয়োগকর্তা বা অংশীদারের কথা।
  1. **Person** — Faculty Members, Administration বা Alumni থেকে খুঁজুন (নাম, ছবি, পদবি সেখান থেকে আসবে), না পেলে **+** দিয়ে নতুন ব্যক্তি যোগ করুন।
  2. **Their title here** — নামের নিচে পদবির বদলে দেখাবে, যেমন "Parent of a CSE student"। খালি রাখলে পদবি দেখাবে।
  3. **What they say** — প্রতিটি ভাষায়; **Type** (Student / Alumni / Parent / Employer / Partner), ইচ্ছা হলে **Rating** (১–৫ ★) ও **Video link** (YouTube / Google Drive)।
  4. **Photo** — শুধু তখনই দেখাবে যখন ব্যক্তির নিজের ছবি নেই।
  5. **Show in** — কোন Program / Department-এ দেখাবে; Admin চাইলে main site-এ। Program-এ দিলে Program-এর page-এ **Testimonials** tab আসে।
  * ক্রম: তালিকায় **Reorder** চেপে টেনে সাজান — website ও homepage সেই ক্রমেই দেখায়।
  * Website: `/testimonials` (microsite-এ `/{department}/testimonials`) — ধরন অনুযায়ী filter; Appearance › Layouts-এ Slider / Grid / Masonry / Video (Video = শুধু ভিডিও-ওয়ালাগুলো)।
  * Homepage: **What People Say** section — চাইলে শুধু কিছু ধরন (যেমন শুধু Parents), Slider বা Grid।
  * Default theme-এ (DIU-র মতো) `/testimonials`: দুই কলামে card — কথার শুরু, ছবি, নাম, পদবি, ধরন, **Read more**; চাপলে পুরো কথা একটি window-এ খোলে (`/testimonials#testimonial-12` link-এও খোলে)। উপরের শিরোনাম, লেখা ও ছবি **Settings › Testimonials**-এ।
  * **কে দিতে পারে:** শিক্ষার্থী, alumni, অভিভাবক, শিক্ষক, নিয়োগকর্তা, অংশীদার — দেশের বা বিদেশের। **From (country)** খালি = বাংলাদেশ; অন্য দেশ দিলে website-এ দেশের নামসহ (আন্তর্জাতিক মতামত)। Department, Faculty, Program, Office বা যেকোনো microsite নিজের testimonial দিতে পারে (**Show in**)।
  * **Homepage-এ শুধু বাছাই করা:** যেটি homepage-এ চান সেটির পাশে **Highlight** (Part A §9)। "What People Say" section শুধু highlight করা গুলো দেখায় (section-এর **Source** "Highlighted first" দিলে বাকিগুলো দিয়ে ভরে)। `/testimonials`-এ সব দেখায়।

---

## 11. Overview

**My Microsite › Overview** — আপনার microsite-এর এক নজরে অবস্থা:

* **Content freshness:** কোন section কত দিন আগে update হয়েছে (✅ ⚠️ ❌), পাশে **Add …**।
* **Your microsite uses:** Menu, Header, Footer, Homepage, Statistics নিজের নাকি parent-এর (⤴ চিহ্ন = Faculty বা মূল website থেকে নেওয়া)।
* **View microsite ↗:** আপনার microsite নতুন tab-এ।

---

## 12. Menu

> **Microsite ▾ কীভাবে:** Faculty, Department বা Program-এর page খুলে উপরের **Microsite ▾** — নিচের প্রতিটি screen সেই জায়গার জন্য খোলে। উপরের **Working in** দেখায় এখন কোন microsite-এ কাজ করছেন; বদলালে Menu, Homepage ইত্যাদি সেটার জন্য খোলে।

**My Microsite › Menu**

### 12.1 নিজের menu নেই (শুরুর অবস্থা)

Screen-এ লেখা থাকে, যেমন: *"This microsite uses Engineering Faculty's Primary menu."* — অর্থাৎ এখন Faculty-র menu দেখাচ্ছে। তিনটি পথ:

| Button | ফল |
|---|---|
| **Create own menu** | খালি menu থেকে শুরু |
| **Copy …'s menu and edit** | Faculty-র menu কপি করে নিজের মতো বদলানো (সবচেয়ে সহজ) |
| **Use main** | মূল website-এর menu |

### 12.2 Menu সাজানো

📷 *Menu builder*

1. উপরে **Location** বাছুন: Primary (header), Footer, Quick links …
2. বামে **Add items** থেকে যোগ করুন:
   * **Pages** — আপনার page
   * **Programs** — নির্দিষ্ট program
   * **Module pages** — News, Notices, Events তালিকা
   * **Categories**, **File** (যেমন PDF), **Custom link** (বাইরের website), **Heading** (link ছাড়া শিরোনাম)
3. ডানে ⠿ টেনে ক্রম বদলান; ডানে সরিয়ে ভেতরে ঢোকান (সর্বোচ্চ ৩ স্তর)।
4. প্রতিটি item-এর লেখা দুই ভাষায় (EN ● বাং ●)।
5. **Save**।

* কোনো page বা program বন্ধ / মুছে গেলে তার menu item নিজে থেকে লুকায় (⚠ চিহ্ন দেখায়) — menu ভাঙে না।
* নিজের menu মুছে দিলে আবার Faculty বা মূল website-এর menu দেখাবে।

---

## 13. Homepage সাজানো

**My Microsite › Homepage**

📷 *Homepage builder*

* প্রতিটি সারি একটি **section** (Hero slider, Statistics, About, Latest News, Notice Board …)।
* ⠿ টেনে ক্রম; 👁 দিয়ে সাময়িক লুকানো; ✕ দিয়ে বাদ; **+ Add section** দিয়ে নতুন।
* ⚙ (section setting): শিরোনাম (দুই ভাষায়), কতগুলো দেখাবে, layout (Grid / List), background, কোন তারিখ থেকে কোন তারিখ পর্যন্ত, এবং **Source**:

| Source | অর্থ |
|---|---|
| Latest | সবচেয়ে নতুনগুলো |
| Highlighted | শুধু highlight করা |
| Highlighted first | আগে highlight, তারপর নতুনগুলো (প্রস্তাবিত) |
| Manual | আপনি নিজে বেছে দেবেন |

* **Preview** দেখে **Save**।
* কোনো module বন্ধ থাকলে তার section ⚠ চিহ্নসহ লুকানো থাকে।

**সব Department (Faculty, Program, Office, Lab)-এর একটি সাধারণ homepage:** Owner তালিকায় 🧩 **All Departments** ইত্যাদি (যাঁরা main homepage সাজাতে পারেন তাঁদের জন্য)। এটি একবার সাজালে প্রতিটি department একই section দেখায়, কিন্তু নিজের news, program, faculty member দিয়ে ভরা। কোনো department একেবারে আলাদা চাইলে নিজের microsite বেছে **Create** (শূন্য থেকে) বা **Start from «All Departments»** (সাধারণটি কপি করে বদলানো) — তখন শুধু ওই department-এর জন্য সেটি দেখাবে; মুছে দিলে আবার সাধারণটি।

**Theme-এর section:** default theme কিছু section দেয় (DIU — Hero, Rankings and mission, Find your degree, Scholarships, Research, Recent events …)। খালি field-এ ধূসর লেখা = website-এ যা দেখাচ্ছে (theme-এর default); নিজে লিখলে সেটিই দেখাবে।

---

## 14. Header ও Footer

**My Microsite › Header & Footer**

* নিজের না থাকলে parent-এর বা মূল website-এর দেখায় (Menu-র মতো)।
* Header: logo (আপনার Department-এর নিজস্ব logo থাকলে), কোন menu।
* Footer: column সংখ্যা ও প্রতিটি column-এ কী (About লেখা, menu, যোগাযোগ, social link, newsletter form, **Important Links** — চাইলে একটি group)। চালু থাকা module-গুলো নিজেদের footer block এখানে যোগ করে।

**Important Links** (**Content › Important Links**): UGC, Student Portal, Library-র মতো link — শিরোনাম, ঠিকানা (`https://…` বা এই site-এর `/…`), নতুন tab-এ খুলবে কিনা, Media Library থেকে লোগো, **Group** (যেমন "Government", "Student Services"), **Show in** (প্রতিটি department-এর নিজের link)। তালিকায় টেনে ক্রম সাজান। Website: `/links` (group অনুযায়ী — প্রতিটি link একটি tile: লোগো, শিরোনাম, বিবরণ, নতুন tab-এ খুললে তীর চিহ্ন; Settings › Layouts-এ list-ও করা যায়); homepage-এ **Important Links** section; footer-এ উপরের block। প্রতি সোমবার system বাইরের link-গুলো যাচাই করে — কোনোটি না খুললে আপনি notification পাবেন; তালিকায় **Broken** filter ও **Check now**।

---

## 15. Statistics

**My Microsite › Statistics** — homepage-এর সংখ্যার সারি (যেমন "6 Departments · 18 Programs · 8,500+ Students")।

📷 *Statistics editor*

| ঘর | অর্থ |
|---|---|
| Label | যেমন "Programs" (দুই ভাষায়) |
| Source | **Auto** = system নিজে গোনে (Program, Department, Faculty Member সংখ্যা …); **Manual** = আপনি লিখবেন (যেমন ছাত্র সংখ্যা) |
| Value / Display | Display-এ দেখায় যেভাবে website-এ যাবে (যেমন rounding: 8,512 → "8,500+") |
| Prefix / Suffix, Icon | যেমন "+" |
| Active | লুকানো / দেখানো |

* Auto সংখ্যা ভুল মনে হলে — যে content গোনা হচ্ছে তা Active ও প্রকাশিত কিনা দেখুন; প্রয়োজনে **Override** দিয়ে নির্দিষ্ট সংখ্যা।
* নিজের statistics না থাকলে parent-এর দেখায়।

---

## 16. People (Head, Team, Coordinator …)

**My Microsite › People** (Office / Committee / Department-এর জন্য একই)

📷 *People manager*

একজন ব্যক্তির তথ্য system-এ **একবারই** থাকে (Administration, Faculty Members বা Alumni directory-তে)। এখানে শুধু তাকে আপনার জায়গায় একটি **ভূমিকায়** যুক্ত করেন — তাই ছবি বা পদবি বদলালে সব জায়গায় একসাথে বদলায়।

**ব্যক্তি যোগ:**
1. **+ Add person** → নাম লিখে খুঁজুন।
2. পেলে বেছে নিন; না পেলে **Add new person (not in any directory)** — নাম, পদবি, ছবি, যোগাযোগ।
3. **Role:** Head, Dean, Officer, Coordinator, Team … (তালিকা থেকে)।
4. ঐচ্ছিক: **Since / Until** (মেয়াদ), **Display role** (website-এ ভিন্ন লেখা দেখাতে, যেমন "Head (In-charge)"), এই জায়গার জন্য আলাদা phone / extension।
5. **Public** বন্ধ করলে শুধু admin-এ দেখায়।

* ⠿ দিয়ে ক্রম।
* মেয়াদ শেষ হলে (Until পেরোলে) নিজে থেকে **Former**-এ চলে যায়।
* ব্যক্তির মূল তথ্য (ছবি, পদবি) বদলাতে নামে click → মূল profile খোলে।

**Website-এ Administration directory (`/administration`, DIU-র মতো):** উপরে বড় ছবি ও শিরোনাম; বাঁয়ে **Browse by Section** — প্রতিটি public category ও তার মানুষের সংখ্যা ("Management (5)"), একটি বাছা (শুরুতে প্রথমটি), নিচে **Quick Links**; ডানে খোঁজার ঘর (নাম বা পদবি, সব section জুড়ে) ও table — ছবি, নাম, পদবি, email, phone, Ext. (phone-এ card)। Profile পাতা চালু থাকলে নামে চাপলে নীল card-এ ছবি, নাম, পদবি, office, যোগাযোগ, জীবনী ও **Roles**। শিরোনাম, উপরের ছবি, উপরের লেখা ও Quick Links: **Settings › Administration**। Category-র ক্রম ও কোনটি public: **People › Staff categories**।

**Faculty Members (শিক্ষক):** শিক্ষকের তথ্য আসে DIU-র **Faculty Management System (FMS)** থেকে — এখানে কেউ যোগ বা মুছতে পারেন না, শুধু email / phone / extension দেখানো বন্ধ করা যায়। **Settings › Faculty Members:** **Source** — FMS-এ শিক্ষকদের data এলে **DIU Faculty Management System (FMS)** বাছুন (ততদিন একই format-এর নমুনা JSON file চলে), তারপর তালিকার **Sync now** (প্রতিদিন 05:30-এ নিজে থেকেও হয়); **Faculty website** — প্রতিটি শিক্ষকের **View Profile** এই ঠিকানার faculty / department / শিক্ষক পাতায় খোলে (এই website-এ নিজের কোনো profile পাতা নেই); পাতার শিরোনাম, উপরের ছবি ও লেখা। Website `/faculty-members` (DIU-র মতো): বাঁয়ে faculty অনুযায়ী শিক্ষকের সংখ্যা, খোঁজা (নাম, পদবি, গবেষণা), department ও পদবি বাছা, প্রতিটি শিক্ষকের card — ছবি (না থাকলে নামের আদ্যক্ষর), নাম, পদবি, department, email, phone, room, **View Profile ↗**; Faculty ও Department site-এ `/…/faculty-members` সেখানকার শিক্ষক।

**Alumni (প্রাক্তন শিক্ষার্থী):** **People › Alumni**-তে প্রত্যেকের নাম, department, program, batch, পাসের বছর, পদবি, প্রতিষ্ঠান, ছবি, গল্প, video; **Pride** চালু করলে তালিকার শুরুতে ও homepage-এর "Our Pride Alumni" section-এ আসে; website-এর **Share your story** ফর্মে জমা পড়া তথ্য অনুমোদন না করা পর্যন্ত দেখায় না। Website `/alumni` (DIU-র মতো): উপরে শিরোনাম ও লেখা, **Share your story** ও **Join the alumni portal**, খোঁজা ও filter, প্রতিজনের card (ছবি, নাম, পদবি, প্রতিষ্ঠান, department) — card চাপলে তাঁর পাতা। প্রতিটি microsite নিজের alumni দেখায়: Department-এর `/…/alumni` সেই department-এর, **Faculty**-র তার সব department-এর (department বাছা যায়), **Program**-এর সেই program-এর — কাউকে program দেওয়া না থাকলে তার department-এর। শিরোনাম, লেখা, ছবি ও alumni portal-এর ঠিকানা: **Settings › Alumni**।

---

## 17. Program

**Academic › Programs** (Department user শুধু নিজের Department-এর program দেখেন)

**+ New program** ধাপে ধাপে চলে: প্রতিটি ধাপ পূরণ করে **Next**। কোনো ধাপের তথ্য এখনো না থাকলে পরের ধাপে চলে যান, পরে edit-এ দিতে পারবেন। শেষ ধাপে **Create**।

Program খুললে বাম দিকে একই অংশগুলো দেখায়। পাশে **✓** মানে পূরণ, **!** মানে বাকি, সংখ্যা মানে কয়টি item আছে। উপরে লেখা থাকে কয়টি অংশ পূরণ হয়েছে। সব অংশ শেষে একবার **Save changes**।

| অংশ | কী আছে |
|---|---|
| **Basic info** | নাম, subtitle, degree, Department, **Level** (Undergraduate / Graduate / Diploma / Post Graduate Diploma), short name, web address, Tags, ছবি, video, brochure, প্রকাশ |
| **Program details** | Summary, objective, **Credit ও সময়কাল (লেখা)**, মেয়াদ, মোট credit, semester, medium, shift, class mode, পরবর্তী ভর্তির তারিখ, Apply link; নিচে "অন্য module-এর অংশের ভূমিকা" (Accreditation, সুযোগ-সুবিধা, Testimonial) |
| **Eligibility** | ভর্তির যোগ্যতা — General ও English-medium আলাদা |
| **Majors** | Major / specialization তালিকা (**Add major**) |
| **Program options** | Dual degree, Evening ইত্যাদি — না থাকলে খালি রাখুন |
| **Career & industry** | Career path ও industry-র তালিকা |
| **Facilitators & people** | Industry expert / facilitator; coordinator ও যোগাযোগের ব্যক্তি |
| **Materials & memberships** | Learning material, professional membership / accreditation |
| **Summary & SEO** | ছোট সারসংক্ষেপ, search engine-এর জন্য তথ্য |

* প্রতিটি list-এর উপরে (এবং Eligibility-তে) ঐচ্ছিক **intro text** — website-এ সেই অংশের শুরুতে দেখায়।
* Website-এ Program page-এর "Program Overview"-তে দেখায় **objective** ও **Credit ও সময়কাল**; দুটোই খালি থাকলে Summary। বাম পাশের "Browse by Section"-এ শুধু যে অংশে কিছু আছে সেগুলোই থাকে; Brochure ও Learning material থাকে "Downloadable Materials"-এ, আর উপরের **Apply Now** যায় Program-এর Apply link-এ (না থাকলে সাধারণ ভর্তির link)।
* list-এর item ⠿ দিয়ে টেনে ক্রম বদলান; item-এ click করলে খোলে।
* **Quick Links** — Faculty, Department, Program ও Office form-এ (Program-এ "Materials & memberships" অংশে): লেখা (প্রতি ভাষায়) ও link, ⠿ দিয়ে ক্রম। এগুলো সেই page-এর "Quick Links"-এ দেখায়। Program-এ খালি রাখলে **Settings › Programs**-এর "প্রতিটি Program page-এর Quick Links" দেখায়, Office-এ খালি রাখলে **Settings › Offices**-এর; Faculty / Department-এ খালি রাখলে Homepage builder-এর section-এর link।
* **Office page** (`/office/{slug}`): বাম পাশে Overview ও office-এর নিজের menu-র page (My Microsite › Menus), তারপর Quick Links; ডান পাশে Overview ও যোগাযোগ (Location, Phone, Email, Extension, Office Hours), Head's message, Services, Office Team (প্রথম ৪ জন — **View Full Team** দিলে `/office/{slug}/team`-এ সবাই), Sections ও FAQ (প্রথম ৫টি — **All FAQs**)। যে অংশ খালি তা দেখায় না। Homepage builder-এ office-এর নিজের homepage-এ section যোগ করলে তখন এর বদলে সেটি দেখায়।
* Department user নাম, short name, web address, Department ও Active বদলাতে পারেন না — Admin পারেন।
* যাঁদের শুধু দেখার অনুমতি (যেমন Admission Office), তাঁরা একই page read-only দেখেন।
* **Tuition Fees** বাম দিকের অংশগুলোর একদম নিচে — শুধু দেখা যায়। যোগ বা বদল **Academic › Tuition Fees**-এ (Admission Office); অনুমতি থাকলে এখানে "Add tuition fee" ও প্রতিটির link থাকে। Waiver rule **Academic › Waiver Calculator**-এ।
* Program page-এর **Curriculum**, **Calendar** অংশ আসে অন্য module থেকে — নিচের অংশ দেখুন।
* Program-এরও নিজস্ব microsite আছে: Program-এর page-এর উপরে **Microsite ▾** থেকে তার Menu, Homepage, Header & Footer, Statistics, Pages খোলে (✓ = সাজানো, ! = এখনো নেই)। অথবা উপরের **Working in**-এ Program বেছে নিয়ে Appearance-এর screen খুলুন।
* **Programs website-এর সবচেয়ে বেশি দেখা page-গুলোর একটি — প্রকাশের আগে credit, মেয়াদ ও তারিখ দুবার মিলিয়ে নিন।**

---

## 18. Tuition Fee ও Waiver

**Academic › Tuition Fees** — Admission Office-এর user; **Academic › Waiver Calculator** ও **Scholarships** — Waiver Office-এর user (default role; Admin অন্য role-কেও permission দিতে পারেন)। Faculty / Department / Program user নিজের program-এর tuition তাঁর Hub-এ শুধু দেখেন।

### 18.1 Tuition fee

**Tuition Fees › Tuition fees › + New** (অথবা Program-এর Hub › Tuition Fees): Program, **Fee category** (Local / International), Session → খরচের সারি (Admission fee, প্রতি credit, Semester fee, Lab fee …) → মোট নিজে হিসাব হয় (প্রয়োজনে override)।

* নতুন session-এর fee নতুন record হিসেবে দিন — পুরনো session archive-এ থাকে।
* অনেক program একসাথে: **Import** (Excel) — আগে **Export** করে নমুনা file নিন, পূরণ করে import; import-এর আগে preview ও ভুলের তালিকা দেখায়।

### 18.2 Waiver rule

**Waiver Calculator › Rules › + New**: কোন Level ও Fee category, কোন Board ও Gender, SSC / HSC GPA বা CGPA-র সীমা, Golden A+ লাগবে কিনা, Waiver category, **Waiver %**, website-এ দেখানোর বার্তা।

**Rule test (প্রকাশের আগে অবশ্যই):** **Waiver Calculator › Rule test** → একটি নমুনা ছাত্রের তথ্য দিন → কোন rule মিলছে ও কত % আসছে দেখুন। একাধিক rule মিললে বা কোনোটিই না মিললে সতর্কতা দেখায়।

* একাধিক rule মিললে কোনটি প্রযোজ্য (সর্বোচ্চ, নাকি যোগফল একটি সীমা পর্যন্ত) — **Waiver Calculator › Settings**।
* **Waiver categories**-এ প্রতিটির **ছোট বর্ণনা** দিতে পারেন (যেমন "SSC ও HSC GPA অনুযায়ী") — calculator-এ নামের নিচে দেখায়; খালি রাখলে শুধু নাম। প্রথম ৬টি category দেখায়, বাকিগুলো "Show all"-এ — তাই বেশি ব্যবহৃতগুলো ক্রমে উপরে রাখুন।
* `/waiver-calculator` page-এর উপরের শিরোনাম ("Calculate Your Fees"), নিচের লাইন ও ছবি, এবং form-এর উপরের **"Important Notice"** — শিরোনাম, লেখা (disclaimer; খালি রাখলে notice দেখায় না), button-এর নাম ("View Details") ও link (default `/scholarships`) — একই **Settings**-এ।
* **Waiver Calculator website-এর সবচেয়ে বেশি ব্যবহৃত tool — rule বদলানোর পর সবসময় Rule test চালান।**

---

## 19. Scholarship

**Academic › Scholarships › + New**: নাম, category, কারা পাবে, শর্ত, পরিমাণ / %, আবেদনের পদ্ধতি ও শেষ তারিখ, সংযুক্ত file। Website-এর `/scholarships` page-এ category অনুযায়ী দেখায়।

---

## 20. Admission

**Academic › Admission**

### 20.1 Circular

**Circulars › + New**: Session, শিরোনাম, বিবরণ, **গুরুত্বপূর্ণ তারিখ** (আবেদন শুরু, শেষ, পরীক্ষা, ফল) আলাদা ঘরে — এগুলো থেকেই homepage-এর countdown ও "Important Dates" তৈরি হয়। আবেদনের শেষ তারিখ পেরোলে "Closed" নিজে থেকে।

**Apply Now** button কোথায় যাবে: **Site › Settings › Admission** tab-এর **Application portal URL** (Admin সেট করেন — Part C)।

### 20.2 Admission Highlights ও Contact

* **Highlights:** ভর্তির সুবিধা (scholarship, lab, club …) — admission page-এর card।
* **Contacts:** কাকে ফোন করবেন — type অনুযায়ী (Admission office, Department …), People-এর মতো ব্যক্তি যোগ।

### 20.3 ফলাফল প্রকাশ

1. **Results › Import** → Session বেছে Excel / CSV upload (Program, Application serial, নাম, Result)।
2. **Preview:** কত সারি ঠিক, কোনগুলোতে ভুল (কারণ সহ) — ঠিক করে আবার upload করা যায়।
3. **Messages:** Selected / Waiting / Not selected / Not found-এর জন্য website-এ কী বার্তা দেখাবে।
4. সব ঠিক থাকলে **Publish** switch চালু — তখনই visitor Admission page-এর **Result** অংশে application serial দিয়ে ফল দেখতে পাবেন।
5. ভুল ধরা পড়লে **Publish** বন্ধ করে ঠিক করুন।

### 20.4 Subscriber

**Subscribers** — যাঁরা admission update পেতে চেয়েছেন; **Export** (Excel)।

---

## 21. Academic Calendar

**Academic › Academic Calendar**

1. **+ New calendar:** title, Session (যেমন "Spring 2026"), বছর, সময়কাল, Faculty (যে faculty-গুলোর জন্য; খালি রাখলে সবার), PDF বা ছবি (hard copy, ঐচ্ছিক), homepage-এ দেখাবে কিনা।
2. **Events** অংশ: প্রতিটি তারিখ — শুরু ও শেষ তারিখ (ঘরে), title, ধরন (Class, Exam, Holiday …), audience (Undergraduate / Graduate; খালি = সবার), প্রয়োজনে লিখিত ব্যাখ্যা ("1st week of March")। ঘরে তারিখ না দিলে event শুধু লেখা হিসেবে দেখায়, Google Calendar ও "Upcoming Academic Dates"-এ আসে না।
3. অনেক তারিখ একসাথে: **Import** (Excel)।

Website-এ `/academic-calendar`-এ চলতি calendar আগে দেখায় — DIU Registrar-এর মতো card: নীল মাথায় "Academic Calendar" ও session বদলানোর তালিকা, তারপর **Academic Year / Duration / Total Events**, তারপর তারিখের table (ধরনের রঙিন badge); উপরে বছর, session, faculty, audience বাছা যায়; Table / Month / Timeline view, PDF download, এবং visitor Google Calendar-এ যোগ করতে পারেন (`.ics`)। Faculty microsite ও Program page-এ সেই faculty-র calendar দেখায়।

---

## 22. Inquiry (website-এর যোগাযোগ form)

**Communication › Inquiries** — visitor-দের প্রশ্ন। আপনি শুধু আপনার Department / Office-এ আসা প্রশ্ন দেখেন; নতুন প্রশ্নে 🔔 notification ও লাল সংখ্যা।

1. বাম তালিকা থেকে প্রশ্ন খুলুন।
2. **Status:** New → In progress → Closed।
3. **Assign:** নিজে বা সহকর্মীকে।
4. **Internal note:** শুধু staff দেখেন।
5. **Reply by email** → **Send** — উত্তর visitor-এর email-এ যায় ও ইতিহাসে থাকে।

Closed প্রশ্ন নির্দিষ্ট সময় (default ২৪ মাস) পর ব্যক্তিগত তথ্য রক্ষায় নিজে থেকে মুছে যায়।

---

## 22a. Services: Facilities, CSR, Partners, Accreditation, International, Lab

* **Services › Facilities** — শিরোনাম, সংক্ষেপ, বিস্তারিত (থাকলে নিজের পেজ, না থাকলে card লিংকে যায়), icon / ছবি Media Library থেকে, ক্যাটাগরি; **Show in** দিয়ে কোন department / program-এ দেখাবে; টেনে ক্রম। Homepage section "Campus Facilities"। Default theme-এ `/facilities` (DIU-র program পাতার facility card-এর মতো): ক্যাটাগরির pill ও প্রতিটি সুবিধার বড় ছবির card (শিরোনাম, দুই লাইন, তীর — নিজের পাতা বা বাইরের ঠিকানা); ছবি না দিলে theme-এর সাধারণ ছবি। পাতার শিরোনাম, লাইন ও উপরের ছবি: **Settings › Facilities**।
* **Services › CSR** — কার্যক্রম (তারিখ, ভিডিও, ছবি, গ্যালারি, ক্যাটাগরি)। ক্যাটাগরি ও উপ-ক্যাটাগরি Site › Taxonomy-তে (CSR category); প্রতিটির ছবি **Services › CSR category pictures**-এ। Website `/csr` (DIU-র CSR পাতার মতো): উপরে **Highlight** করা প্রথম কার্যক্রম বড় ছবিতে, তার নিচে প্রভাবের সংখ্যা ("50,000+ Individuals Supported" …), "Our Approach…"-এ পরের দুটি Highlight করা কার্যক্রম, **CSR Report** button; তারপর গাঢ় নীল অংশে "Where We Create Impact" — খোঁজা, ক্যাটাগরি বাছা, ছবি বা বিবরণ থাকা ক্যাটাগরিগুলোর card — ও "Stories of Change" (প্রতিটি কার্যক্রমের card, পাতা ধরে)। সব লেখা, সংখ্যা, উপরের ছবি ও CSR Report PDF: **Settings › Social Responsibility** (Report না দিলে button থাকে না)।
* **Services › Partners** — ধরন (Membership, Accreditation, Industry, Sponsor, Discount, Other), লোগো, ওয়েবসাইট; Discount হলে ছাড়, শর্ত, ক্যাটাগরি (icon সহ) ও **যেদিন পর্যন্ত** — তারপর নিজে থেকে লুকায়। Website `/partners`, `/student-discounts`; homepage-এ লোগো carousel। Default theme-এ (DIU-র মতো): `/partners`-এ ধরনগুলো tab, প্রতিটি প্রতিষ্ঠানের লোগো card (নাম, website-এর ঠিকানা) — কোনটি Accreditation আর কোনটি Membership তা card-এর tag-এ লেখা থাকে; `/student-discounts`-এ বাঁয়ে প্রতিটি ক্যাটাগরি ও তার অংশীদার সংখ্যা, Quick Links, ডানে নীল মাথায় ক্যাটাগরি ও খোঁজা, table (প্রতিষ্ঠান, ছাড় — **Show More**-এ পুরো ছাড় ও শর্ত — website)। দুই পাতার শিরোনাম, লাইন, উপরের ছবি ও Quick Links: **Settings › Partners**।
* **Services › Accreditations / Rankings** — accreditation-কে **Show in** দিয়ে program / department / faculty-তে দিন (program পেজে "Accreditation" tab হয়)। Ranking-এ একই র‍্যাংকিংয়ের প্রতি বছরে একই **Short key** (qs, the …) — তাতে `/rankings`-এ বছরওয়ারি তালিকা হয়; পরের বছরের জন্য তালিকায় **Next year**। Default theme-এ (DIU-র মতো): `/rankings`-এ "All Ranking" — প্রতিটি ফলাফল এক সারিতে (লোগো, নাম ও র‍্যাংকিংয়ের link, অবস্থান, বছর, বিবরণ), নতুনটি আগে, নিচে Quick Links; `/accreditations`-এ accreditation ও membership একসাথে card-এ, প্রতিটির গায়ে ক্যাটাগরির tag (Accredited গাঢ়, Membership হালকা)। দুই পাতার শিরোনাম, লাইন, উপরের ছবি ও Quick Links: **Settings › Accreditation**। (Main site-এর `/accreditations`-এ শুধু main site-এ দেওয়া accreditation দেখায়।)
* **International** — **Countries** (ISO কোড, পতাকা), **Partners** (দেশ, চুক্তি, মেয়াদ; বিশেষ partner = Highlight; Excel / CSV **Import** — আগে যাচাই, তারপর সংরক্ষণ), **Conferences**, **Visitors** — DIU-তে আসা দেশের বা বিদেশের অতিথি ও তাঁদের মন্তব্য (নাম, পদবি, প্রতিষ্ঠান হাতে লেখা; দেশ; গ্যালারি অ্যালবাম বাছা যায়)। Website `/international`; অতিথিরা `/visitors`-এ (DIU-র "Testimonials" page-এর চেহারায়)। Homepage-এর "Meet our distinguished …" slider শুধু **Highlight** করা অতিথি দেখায়। অতিথির কথা মতামতের তালিকাতেও চাইলে Visitor খুলে **Show as a testimonial too** — একবারই তৈরি হয় (ধরন Visitor, তাঁর দেশ ও ছবি), পরে বোতামটি "Open its testimonial"। পাতার উপরের লেখা **Settings › International**-এ। Default theme-এ (DIU-র মতো): `/international`-এ সংখ্যাগুলো (partner, দেশ, সম্মেলন, অতিথি) ও **Highlight** করা partner-দের card (লোগো, দেশের পতাকা, নাম, বিবরণ) এবং আন্তর্জাতিক পাতার link; `/international/partners` (DIU-র "International Linkage") — বাঁয়ে **Browse by Section** ও Quick Links, ডানে "Select Country to View Universities" (দেশ ও তার বিশ্ববিদ্যালয়ের সংখ্যা) ও প্রতিটি বিশ্ববিদ্যালয়ের card (লোগো, নাম, দেশ, **Visit Website**); সম্মেলন ছবির card-এ। প্রতিটি পাতার শিরোনাম, লাইন, ছবি, Browse by Section-এর link ও Quick Links: **Settings › International**।
* **Services › Labs** — ল্যাবের নাম, ধরন, department (দিলে পেজ department-এর নিচে: `/fsit/cse/lab/…`), যোগাযোগ, লোগো, কভার, **People**-এ দায়িত্বপ্রাপ্ত ও টিম। নিচে চারটি তালিকা: Scopes & Services, Equipment & Tools (সংখ্যাসহ), Student Projects ও Products (এ দুটির নিজের পেজ)। ল্যাবের news, event, gallery, slider, partner — সেই module-এ **Show in** দিয়ে ল্যাব বেছে নিন। ল্যাবের নিজের user শুধু নিজের ল্যাব বদলাতে পারে (Manage:Lab permission)। Website-এ (DIU-র মতো): `/labs` — faculty অনুযায়ী প্রতিটি department-এর card (**View Labs**), উপরে খোঁজা ও ধরন; `/fsit/cse/labs` — বাম পাশে department-এর সব ল্যাব, পাশে প্রথমটির পেজ; ল্যাবের পেজে বিবরণ, Location, Instruments (Equipment summary), দায়িত্বপ্রাপ্ত, তালিকাগুলোর link, ভিডিও, আর ল্যাবে দেখানো album-এর ছবি (Gallery)। পুরনো `/lab-facilities/…` ঠিকানা নিজে নতুনটিতে চলে যায়। উপরের লেখা ও ছবি **Settings › Labs**-এ।

## 23. Newsletter

**Communication › Newsletter** (সাধারণত Admin বা PR)

* **Subscribers / Lists:** কারা subscribe করেছেন (যেমন General, Admission Updates); Import / Export।
* **Campaign › + New:** বিষয়, লেখা, কোন list → **Send test** (নিজের email-এ দেখে নিন) → **Send now** বা নির্দিষ্ট সময়।
* News বা Notice থেকে সরাসরি campaign — *পরে আসবে*; এখন campaign-এ **Link** ঘরে খবরের ঠিকানা দিন।
* Footer-এ subscribe form: **Header & Footer**-এ "Newsletter" block যোগ করুন; `/newsletter` পেজেও form আছে। নতুন subscriber email-এর link-এ click করলে তবেই সক্রিয় হন।
* প্রতিটি email-এ unsubscribe link নিজে থেকে থাকে; **Report**-এ কতগুলো গেল, কতগুলো ব্যর্থ।
