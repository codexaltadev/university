# User Manual — Part C: Admin ও Super Admin

| বিষয় | তথ্য |
|---|---|
| Document | User Manual (Admin Panel) — Part C (SDLC ধাপ ১০) |
| Version | 0.1 (Draft) |
| Date | 2026-10-08 |
| Status | Review-এর জন্য অপেক্ষমাণ |
| পাঠক | **Admin** (central web / PR team) ও **Super Admin** (IT / web team lead) |

> [Part A](01-Manual-PartA-Basics.md) ও [Part B](02-Manual-PartB-Content-Microsite.md)-এর সব কাজ Admin-ও করতে পারেন — সব Department-এর জন্য। এখানে শুধু Admin ও Super Admin-এর অতিরিক্ত কাজ। প্রতিটি অংশের শিরোনামে কে করতে পারেন লেখা আছে।

## সূচি

**Admin**
1. [Main Site Queue — Department-এর content মূল website-এ](#1-main-site-queue--departmentএর-content-মূল-websiteএ)
2. [মূল website-এর Highlight](#2-মূল-websiteএর-highlight)
3. [মূল website-এর Menu, Homepage, Header ও Footer](#3-মূল-websiteএর-menu-homepage-header-ও-footer)
4. [Theme ও Design](#4-theme-ও-design)
5. [Layouts](#5-layouts)
6. [Website Settings](#6-website-settings)
7. [Admin Panel Branding](#7-admin-panel-branding)
8. [Categories ও Tags](#8-categories-ও-tags)
9. [SEO ও Redirect](#9-seo-ও-redirect)
10. [Content Freshness ও Reminder rule](#10-content-freshness-ও-reminder-rule)
11. [নতুন Faculty, Department, Office বা Lab](#11-নতুন-faculty-department-office-বা-lab)
12. [Website-এর লেখা অনুবাদ (Languages)](#12-websiteএর-লেখা-অনুবাদ-languages)

**Super Admin**
13. [User](#13-user)
14. [Role ও Permission](#14-role-ও-permission)
15. [Role Labels](#15-role-labels)
16. [Module — install, চালু, বন্ধ, update, uninstall](#16-module--install-চালু-বন্ধ-update-uninstall)
17. [Theme install](#17-theme-install)
18. [Activity Log](#18-activity-log)
19. [Backup](#19-backup)

**Checklist**
20. [নিয়মিত কাজের checklist](#20-নিয়মিত-কাজের-checklist)

---

## 1. Main Site Queue — Department-এর content মূল website-এ

*Admin*

Department / Office-এর content শুধু তাদের microsite-এ দেখায়। মূল website-এর তালিকায় (`/news`, `/notices` …) কোনটি যাবে, তা আপনি ঠিক করেন।

**Content › ⭐ Main Site Queue**

📷 *Main Site Queue*

| অংশ | কাজ |
|---|---|
| **Requests** tab | Department user যেগুলোর জন্য **Request main site** চেয়েছেন — **Approve** বা **Reject** (কারণ লিখুন); অনুরোধকারী notification পান |
| **New entity content** tab | মূল website-এ নেই এমন নতুন Department content — অনুরোধ ছাড়াই আপনি **Publish 🌐** করতে পারেন |
| Filter | Type, Entity, তারিখ |
| Bulk | একাধিক বেছে **Publish to main site** বা **Dismiss** (তালিকা থেকে সরানো, content-এ পরিবর্তন নেই) |

যেকোনো content-এর form-এও **🌐 Main site** switch আছে (শুধু আপনার জন্য)। বন্ধ করলে মূল website থেকে সরে যায়, microsite-এ থাকে।

---

## 2. মূল website-এর Highlight

*Admin*

**Content › ⭐ Highlights** → উপরে **Context: Main site**

* মূল homepage-এ highlight করা content-এর তালিকা; ⠿ টেনে ক্রম।
* **Until** তারিখ — পেরোলে নিজে থেকে highlight থেকে সরে যায়।
* ✕ — highlight থেকে সরানো (content থাকে)।
* Context বদলে অন্য microsite-এর highlight-ও দেখা ও ঠিক করা যায়।

---

## 3. মূল website-এর Menu, Homepage, Header ও Footer

*Admin*

**Appearance › Main Menu / Main Homepage / Header & Footer** — Part B §12–§15-এর মতোই, তবে মূল website-এর জন্য।

* **মূল menu বদলালে** যে microsite-গুলোর নিজস্ব menu নেই, তাদেরও বদলায় — বড় পরিবর্তনের আগে **Preview** দেখুন।
* Header-এর top bar: phone, email, quick links, ভাষা পরিবর্তন, social icon — কোনটি দেখাবে তা switch দিয়ে।
* Footer-এর copyright লেখায় `{year}` লিখলে প্রতি বছর নিজে বদলায়।

---

## 4. Theme ও Design

*Admin*

**Appearance › Theme & Design**

📷 *Theme & Design*

| অংশ | কী বদলানো যায় |
|---|---|
| Theme | Install করা theme-এর মধ্যে active কোনটি (install — Super Admin, §17) |
| Colors | Primary, Secondary, Accent, Background, Text — ⚠ চিহ্ন দেখালে লেখা পড়তে কষ্ট হবে (contrast কম), অন্য রং নিন |
| Typography | Heading ও body font; বাংলার আলাদা font |
| Branding | Logo (light ও dark), favicon, footer logo |
| Theme options | Theme অনুযায়ী (যেমন sticky header) |

* ডানে **Live preview** — শুধু আপনি দেখেন, visitor নন। Desktop / Mobile ও ভাষা বদলে দেখুন।
* **Save & publish** — সব page-এ সাথে সাথে প্রযোজ্য।
* **Reset** — theme-এর মূল রঙে ফেরা।
* Theme বদলালে content-এ কোনো পরিবর্তন হয় না।

---

## 5. Layouts

*Admin*

**Appearance › Layouts** — প্রতিটি তালিকা ও detail page কীভাবে দেখাবে:

| উদাহরণ | বিকল্প |
|---|---|
| News listing | Grid, List, Card, Magazine |
| Events listing | Calendar, Card, Timeline, List |
| Notice board | Table, List, Card |
| Faculty listing | Grid, List, Card |

কিছু layout-এর option আছে (প্রতি সারিতে কয়টি, sidebar দেখাবে কিনা)।

---

## 6. Website Settings

*Admin*

**Site › Settings** — tab অনুযায়ী:

| Tab | কী আছে |
|---|---|
| **General** | University-র নাম (দুই ভাষায়), short name, ঠিকানা, phone, email, map, social link |
| **Languages** | কোন ভাষা চালু, default ভাষা |
| **Admission** | Application portal URL (Apply Now button) |
| **Fees & Waiver** | একাধিক waiver rule মিললে নিয়ম (সর্বোচ্চ / সীমা পর্যন্ত যোগ), calculator-এর disclaimer |
| **Notices** | "New" চিহ্ন কত দিন |
| **Inquiries** | Inquiry type ও কোন type কোথায় যাবে, captcha চালু কিনা, কত দিন পর মুছবে |
| **Maintenance** | Website সাময়িক বন্ধের বার্তা |
| অন্যান্য module | চালু module-এর নিজস্ব tab (Transport, FAQ …) |

**Transport** (বাম menu-র **Transport**):

* **Service types** — Regular, Final Exam, Mid-Term, Semester Break …; একটি **Default**।
* **Stops** — নাম (বাংলা ও English), চিহ্ন, ম্যাপের অক্ষাংশ / দ্রাঘিমাংশ (Google Maps-এ right-click করে প্রথম সংখ্যা); তালিকায় ম্যাপে খুলে দেখা যায়।
* **Routes** — নাম, কোন ভাগে দেখাবে (নিয়মিত / শাটল / শুক্রবার), ম্যাপ, বার্তা; তালিকায় টেনে ক্রম। Route খুলে নিচে **Stops and times per service** → **New**: service, কোন দিন bus চলে না, stop-গুলো টেনে ক্রমে সাজান (প্রথমটি শুরু, শেষটি DSC), সময়গুলো একসাথে যোগ করুন (Campus-এ যেতে / Campus থেকে, নিয়মিত / শাটল)। পরীক্ষার সূচি বানাতে **Copy to another service** দিয়ে নিয়মিতটি copy করে সময় বদলান।
* **Settings › Transport** — **এখন যে service চলছে** (পরীক্ষার সময় Final Exam বেছে দিন — website সঙ্গে সঙ্গে সেই সূচি দেখায়; কোনো route-এ সেটি না থাকলে default-এরটি), সূচির শিরোনাম, সেমিস্টার, নিয়ম ও নীতিমালা PDF, page-এর পরিচিতি, Transport Pass, পরিবহন ফি।

Website: `/transport` (stop দিয়ে খোঁজা সহ); homepage-এ "Find your bus"। পুরোনো frontend-এর জন্য একই data `/api/v2/public/transport`-এ।

---

## 7. Admin Panel Branding

*Admin (Reset — Super Admin)*

**Site › Settings › Admin Branding** — Admin Panel-এর চেহারা (website নয়):

* Panel-এর নাম, logo, favicon, primary রং, font, login page-এর ছবি ও বার্তা।
* **Use website branding** — এক click-এ website-এর logo ও রং।
* Menu ও screen-এর গঠন বদলায় না — তাই এই manual সব সময় মিলবে।

---

## 8. Categories ও Tags

*Admin*

**Site › Categories & Tags** — সব module-এর category এক জায়গায় (Notice category, FAQ category, Program level ও tag, Event type …)।

* Category-র ভেতরে category (যেমন Gallery: Campus › Events)।
* নাম দুই ভাষায়; ক্রম টেনে।
* **Program Level** (Undergraduate, Graduate, Diploma, Post Graduate Diploma) — নতুন level যোগ করা যায়, তবে বিদ্যমানগুলো মুছবেন না।
* ব্যবহৃত category মুছতে গেলে কতগুলো content-এ আছে দেখায়; আগে অন্য category-তে সরান।

---

## 9. SEO ও Redirect

*Admin*

**Site › SEO & Redirects**

| অংশ | কাজ |
|---|---|
| **Global SEO** | Website-এর default title, description, share ছবি; Google Search Console / Analytics code |
| প্রতিটি content-এর **SEO** tab | সেই page-এর title, description, share ছবি (খালি রাখলে নিজে থেকে) |
| **Redirects** | পুরনো ঠিকানা → নতুন ঠিকানা (301 স্থায়ী / 302 সাময়িক); কতবার ব্যবহৃত হয়েছে |
| **Import** | অনেক redirect একসাথে CSV দিয়ে |
| **404 log** | Visitor কোন ঠিকানায় গিয়ে "Page not found" পেয়েছেন, কতবার — পাশে **Create redirect** |

**সাপ্তাহিক অভ্যাস:** 404 log-এর শীর্ষ ঠিকানাগুলোর redirect তৈরি করুন — বিশেষ করে go-live-এর প্রথম মাসে।

---

## 10. Content Freshness ও Reminder rule

*Admin*

**Site › Content Freshness**

📷 *Freshness report*

* টেবিল: প্রতিটি Faculty / Department / Office × প্রতিটি section — ✅ ঠিক, ⚠ পুরনো (কত দিন), ❌ কিছু নেই।
* Cell-এ click → সেই জায়গার সেই তালিকা।
* **Export** — মাসিক meeting-এর জন্য।

**Reminder rules › + New**: কোন section (যেমন Notices), কোন ধরনের জায়গা (Department), সর্বোচ্চ কত দিন update ছাড়া থাকতে পারে, কত দিন পর পর মনে করাবে, বাধ্যতামূলক কিনা, email-এর বার্তা।

---

## 11. নতুন Faculty, Department, Office বা Lab

*Admin*

**Academic › Faculties / Departments**, **People › Offices**, **Services › Labs** → **+ New**

1. নাম (দুই ভাষায়), short name, parent (Department-এর Faculty; Program-এর Department)।
2. Logo, banner, পরিচিতি।
3. **Has microsite** (Office / Lab) চালু করলে নিজস্ব microsite।
4. **Save** — সাথে সাথে:
   * Microsite তৈরি (`/{faculty}/{department}`, যেমন `/fsit/cse`), menu / homepage parent থেকে নেয় যতক্ষণ না নিজেরটা বানানো হয়।
   * Office / Lab-এর জন্য নতুন permission ("Manage: … Office") — Role-এ দিন (§14)।
   * Content-এর **Show in**-এ নতুন নাম দেখা দেয়।
5. User দিন: §13-এ user-এর **Scope**-এ নতুন Department / Office।

কোনো code বা developer লাগে না।

---

## 12. Website-এর লেখা অনুবাদ (Languages)

*Admin*

**Site › Languages › Translations** — website-এর স্থির লেখা (button, label: "Read more", "View all", "Apply Now" …) দুই ভাষায় বদলানো। খুঁজুন → নতুন লেখা → **Save**। Content-এর অনুবাদ নয় (সেটি প্রতিটি form-এ — Part A §10)।

---

## 13. User

*Super Admin (Admin — অনুমতি থাকলে)*

**System › Users › + New user**

📷 *User form*

| ঘর | কী দেবেন |
|---|---|
| Name, Email * | Email দিয়ে login |
| Password | দিন, অথবা **Send invitation** — user নিজে password ঠিক করবেন (প্রস্তাবিত) |
| **Roles** | কী করতে পারবেন — যেমন Department Editor (§14) |
| **Scope** | কোথায় — যেমন CSE Department। Faculty দিলে তার সব Department ও Program-ও |
| Active | বন্ধ করলে login করতে পারবেন না, চলমান session-ও সাথে সাথে বন্ধ |

* **View effective permissions** — এই user আসলে কী কী করতে পারবেন তার সম্পূর্ণ তালিকা; ভুল বোঝাবুঝি এড়াতে নতুন user-এর পর দেখে নিন।
* কেউ চাকরি ছাড়লে: **Active বন্ধ** করুন, মুছবেন না — তাঁর করা কাজের ইতিহাস থাকে।
* একজনকে একাধিক জায়গা দিতে Scope-এ একাধিক যোগ করুন।

---

## 14. Role ও Permission

*Super Admin*

**System › Roles**

| Tab | কী |
|---|---|
| **Resources** | প্রতিটি module-এ View / Create / Update / Delete / Restore; content-এ **Publish to main site**, **Highlight on main site** |
| **Pages**, **Widgets** | বিশেষ screen ও dashboard card |
| **Custom** | Import, Export ইত্যাদি বিশেষ কাজ |
| **Entity permissions** | নির্দিষ্ট Office / Lab-এর অনুমতি — যেমন "Registrar Office: Manage, Content, Menu" |

**প্রস্তাবিত role (শুরু):**

| Role | কার জন্য | মূল অনুমতি |
|---|---|---|
| Super Admin | IT / web lead (১–২ জন) | সব |
| Admin | Central web / PR team | সব content ও settings, main site; System নয় |
| Content Manager | নির্দিষ্ট module (যেমন শুধু News ও Events) | ঐ module-গুলো |
| Faculty / Department / Program Editor | Entity-র মানুষ | Content, My Microsite — **Scope** দিয়ে সীমিত |
| Office / Lab Editor | Office / Lab-এর মানুষ | Entity permission দিয়ে নির্দিষ্ট Office / Lab |
| Admission Officer | Admission office | Admission, Fees & Waiver, Scholarship |
| Viewer | দেখার জন্য | শুধু View |

* Role = **কী** করতে পারবে; Scope = **কোথায়**। দুটো একসাথে কাজ করে।
* বন্ধ module-এর permission ধূসর দেখায় — মুছে যায় না, module চালু হলে আবার কাজ করে।
* নতুন module চালু হলে তার permission নিজে থেকে যোগ হয় — কোন role-এ দেবেন ঠিক করুন।

---

## 15. Role Labels

*Super Admin*

**System › Role Labels** — People-এ ব্যবহৃত ভূমিকার **নাম** (Dean, Head, Officer, Coordinator, Chairperson, Member Secretary …) website-এ কীভাবে লেখা হবে।

* শুধু **নাম (label)** বদলানো যায়, দুই ভাষায় — যেমন "Head" → "Chairman"। সব জায়গায় একসাথে বদলায়।
* মূল key (`head`) বদলায় না — তাই অন্য university-তেও system একই ভাবে চলে।

---

## 16. Module — install, চালু, বন্ধ, update, uninstall

*Super Admin*

**System › Modules**

📷 *Module Manager*

| অবস্থা / কাজ | কী হয় |
|---|---|
| 🔒 Core | সবসময় চালু; বন্ধ করা যায় না |
| **Activate** | Module-এর menu, page, homepage section, statistics, search চালু। অন্য module-এর উপর নির্ভর করলে আগে সেটি চালু করতে বলবে |
| **Deactivate** | সব জায়গা থেকে লুকায় — **data, permission, settings থেকে যায়**; আবার চালু করলে সব আগের মতো। অন্য চালু module এর উপর নির্ভর করলে বন্ধ হবে না (তালিকা দেখায়) |
| **Install from ZIP** | নতুন module — upload → নিরাপত্তা ও সংঘর্ষ যাচাই (✅ / ❌ তালিকা) → সব ✅ হলে install, **বন্ধ অবস্থায়**; তারপর Activate |
| **Update** | নতুন version-এর ZIP → আগে নিজে থেকে backup → update; পুরনো version-এ নামানো যায় না |
| **Uninstall** | শুধু বন্ধ module। **Keep data** (code যায়, data থাকে) বা **Delete data** (module-এর slug টাইপ করে নিশ্চিত — ফেরত আনা যায় না) |
| **Details** | README, permission তালিকা, কোন module-এর উপর নির্ভর করে, version ইতিহাস |

**নিরাপদ নিয়ম:**
1. নতুন বা update ZIP **আগে staging website-এ** install করে দেখুন।
2. Production-এ কম traffic-এর সময় (রাতে)।
3. Validation-এ ❌ থাকলে কিছুই install হয় না — তালিকা developer-কে পাঠান।
4. শুধু বিশ্বস্ত উৎসের ZIP।
5. Publications ও Research module install করা আছে কিন্তু বন্ধ — প্রয়োজন হলে **Activate**।

---

## 17. Theme install

*Super Admin*

**System › Themes**

* **Install from ZIP** → যাচাই → install।
* **Preview** — শুধু আপনার browser-এ নতুন theme দেখুন, visitor পুরনোটাই দেখেন।
* **Activate** — সবার জন্য; content অপরিবর্তিত।
* Active theme ও default theme uninstall করা যায় না।
* রং / font / logo — Admin-ও পারেন (§4)।

---

## 18. Activity Log

*Super Admin (Admin — দেখার অনুমতি থাকলে)*

**System › Activity Log** — system-এর সব কাজ: কে, কখন, কোথায়, কী বদলেছেন (পুরনো ও নতুন মান), login / logout / ব্যর্থ login, module ও setting পরিবর্তন।

* Filter: user, module, কাজের ধরন, তারিখ।
* **Export** (CSV / Excel)।
* ন্যূনতম ৬০ দিন থাকে (Settings-এ বাড়ানো যায়, কমানো নয়); কেউ মুছতে বা বদলাতে পারে না।

---

## 19. Backup

*Super Admin*

**System › Backups**

* **Health:** শেষ সফল backup কখন — ২৪ ঘণ্টার বেশি পুরনো হলে লাল ও notification।
* স্বয়ংক্রিয়: প্রতিদিন database, প্রতি সপ্তাহে file; module / theme install-এর আগেও।
* **Backup now** — বড় পরিবর্তনের আগে (database only / full)।
* তালিকা থেকে **Download**।
* Restore সাধারণত server team করে (Deployment Plan §7); ব্যর্থ backup-এর notification এলে সাথে সাথে IT-কে জানান।

---

## 20. নিয়মিত কাজের checklist

### নতুন semester / session শুরুতে

- [ ] **Academic › Sessions**-এ নতুন session
- [ ] নতুন session-এর **Tuition fee** (পুরনোটি archive-এ থাকে)
- [ ] Waiver rule বদলালে **Rule test**
- [ ] **Academic Calendar** নতুন session
- [ ] Program-এর **Next intake** তারিখ
- [ ] পুরনো popup / ticker / slider-এর মেয়াদ দেখা

### Admission মৌসুম

- [ ] Admission **Circular** ও গুরুত্বপূর্ণ তারিখ
- [ ] Homepage-এ "Admission going on" section ও countdown
- [ ] Announcement bar / popup (মেয়াদ সহ)
- [ ] **Apply Now** link ঠিক আছে (Settings › Admission)
- [ ] Waiver Calculator — কয়েকটি নমুনা দিয়ে **Rule test**
- [ ] Module install / বড় পরিবর্তন **এই সময়ে নয়**

### ফলাফল প্রকাশের দিন

- [ ] Result import → preview-এ ভুল শূন্য
- [ ] বার্তাগুলো ঠিক
- [ ] কয়েকটি serial দিয়ে নিজে যাচাই → **Publish**

### কেউ চাকরি ছাড়লে / দায়িত্ব বদলালে

- [ ] **System › Users** → Active বন্ধ (বা Scope বদল)
- [ ] People-এ তাঁর assignment-এর **Until** তারিখ; নতুন ব্যক্তি যোগ
- [ ] Head / Dean বদলালে বার্তা (Message from Head) page update

### সাপ্তাহিক

- [ ] **404 log** → redirect
- [ ] **Main Site Queue** ও request
- [ ] Inquiry — কোনোটি অনেকদিন "New" অবস্থায় আছে কিনা

### মাসিক

- [ ] **Content Freshness** report — পিছিয়ে থাকা Department-কে জানানো
- [ ] Media Library-র **Unused** file পরিষ্কার
- [ ] Backup health
- [ ] User তালিকা — অপ্রয়োজনীয় account বন্ধ
