# User Manual — Part A: শুরু ও সাধারণ কাজ

| বিষয় | তথ্য |
|---|---|
| Document | User Manual (Admin Panel) — Part A (SDLC ধাপ ১০) |
| Version | 0.1 (Draft) |
| Date | 2026-10-08 |
| Status | Review-এর জন্য অপেক্ষমাণ |
| পাঠক | Admin Panel ব্যবহারকারী **সবাই** — Department / Faculty / Program / Office / Lab-এর content editor, Admin, Super Admin। কোনো technical জ্ঞান লাগবে না |

### User Manual-এর গঠন

| Part | কার জন্য | বিষয় |
|---|---|---|
| **A** | সবাই | Login, dashboard, সব screen-এর সাধারণ নিয়ম, প্রকাশ, ভাষা, Media Library, Trash — এই document |
| B | Content editor ও microsite editor | [Notice, News, Event, Page, Gallery …; নিজের microsite-এর menu, homepage, statistics, people; Program, Admission, Waiver](02-Manual-PartB-Content-Microsite.md) |
| C | Admin ও Super Admin | [Main site, theme, settings, user ও role, module ও theme install, backup](03-Manual-PartC-Admin.md) |

> **Screenshot সম্পর্কে:** এই manual এখন UI/UX design ও prototype অনুযায়ী লেখা। Development শেষে প্রতিটি `📷` চিহ্নের জায়গায় আসল screenshot বসবে, এবং button-এর নাম চূড়ান্ত screen-এর সাথে মিলিয়ে নেওয়া হবে।

## সূচি

1. [System কী](#1-system-কী)
2. [Login ও নিরাপত্তা](#2-login-ও-নিরাপত্তা)
3. [Dashboard ও menu চেনা](#3-dashboard-ও-menu-চেনা)
4. [কোন Department / Office-এ কাজ করছি](#4-কোন-department--office-এ-কাজ-করছি)
5. [তালিকা (list) screen](#5-তালিকা-list-screen)
6. [Form — নতুন তৈরি ও সম্পাদনা](#6-form--নতুন-তৈরি-ও-সম্পাদনা)
7. [প্রকাশ: Active, Publish date, Expires](#7-প্রকাশ-active-publish-date-expires)
8. ["Show in" — কোথায় দেখাবে](#8-show-in--কোথায়-দেখাবে)
9. [Main site-এ দেখানো ও Highlight](#9-main-site-এ-দেখানো-ও-highlight)
10. [দুই ভাষায় লেখা](#10-দুই-ভাষায়-লেখা)
11. [Media Library — ছবি ও file](#11-media-library--ছবি-ও-file)
12. [Delete, Trash ও Restore](#12-delete-trash-ও-restore)
13. [পরিবর্তনের ইতিহাস (Activity)](#13-পরিবর্তনের-ইতিহাস-activity)
14. [Notification ও Content Update Reminder](#14-notification-ও-content-update-reminder)
15. [সাধারণ প্রশ্ন ও সমস্যা](#15-সাধারণ-প্রশ্ন-ও-সমস্যা)
16. [শব্দকোষ](#16-শব্দকোষ)

---

## 1. System কী

এটি university-র website চালানোর system। Website-এর সব লেখা, ছবি, notice, menu ইত্যাদি **Admin Panel** থেকে বদলানো যায় — কোনো developer লাগে না।

* **Website (public):** সবাই যা দেখে — যেমন `daffodilvarsity.edu.bd`।
* **Admin Panel:** আপনি যেখানে কাজ করবেন — যেমন `daffodilvarsity.edu.bd/admin`।
* **Microsite:** প্রতিটি Faculty, Department, Program, Office ও Lab-এর নিজস্ব ছোট website, মূল website-এর ভেতরে — যেমন `/faculties/fsit`, `/fsit/cse`, `/fsit/cse/bsc-in-cse`।

আপনি কী দেখবেন ও কী করতে পারবেন, তা নির্ভর করে আপনার **role** (ভূমিকা) ও **scope**-এর (কোন Department / Office) উপর। যে কাজের অনুমতি নেই, তার button আপনার screen-এ দেখাবেই না।

---

## 2. Login ও নিরাপত্তা

### 2.1 Login

1. Browser-এ Admin Panel-এর ঠিকানা খুলুন।
2. **Email** ও **Password** দিন → **Sign in**।
3. 2FA চালু থাকলে phone-এর Authenticator app (Google Authenticator / Microsoft Authenticator)-এর ৬ অঙ্কের code দিন → **Verify**।

📷 *Login screen*

| সমস্যা | কারণ ও সমাধান |
|---|---|
| "১৫ মিনিট পর চেষ্টা করুন" | ৫ বার ভুল password — ১৫ মিনিট অপেক্ষা করুন, বা **Forgot password?** |
| "Account inactive" | আপনার account বন্ধ করা হয়েছে — Admin-এর সাথে যোগাযোগ করুন |
| Code কাজ করছে না | Phone-এর সময় ঠিক আছে কিনা দেখুন; অথবা **Use recovery code** |

### 2.2 Password ভুলে গেলে

**Forgot password?** → email দিন → email-এর link-এ click → নতুন password (ন্যূনতম ৮ অক্ষর, বড় ও ছোট হাতের অক্ষর এবং সংখ্যা)।

### 2.3 2FA চালু করা (প্রস্তাবিত)

User menu (উপরে ডানে আপনার নাম) → **Profile** → **Two-factor authentication** → **Enable** → phone-এর app দিয়ে QR code scan → app-এর code দিন → **Recovery codes** কোথাও নিরাপদে লিখে রাখুন (phone হারালে লাগবে)।

> Super Admin-দের জন্য 2FA বাধ্যতামূলক হতে পারে (setting অনুযায়ী)।

### 2.4 নিরাপত্তার অভ্যাস

* Password কাউকে দেবেন না — প্রত্যেকের নিজস্ব account থাকবে; কে কী বদলেছে তা system লিখে রাখে।
* কাজ শেষে **Logout**; ১২০ মিনিট কিছু না করলে system নিজেই logout করে দেয়।
* Admin Panel-এর ভাষা বদলাতে: **Profile** → **Language** (English / বাংলা)।

---

## 3. Dashboard ও menu চেনা

Login-এর পর **Dashboard** — আপনার role অনুযায়ী card (widget) দেখায়: সাম্প্রতিক প্রকাশ, নির্ধারিত (scheduled) content, নতুন inquiry, কোন content অনেকদিন update হয়নি ইত্যাদি।

📷 *Dashboard*

**বাম পাশের menu (sidebar)** — group অনুযায়ী সাজানো:

| Group | কী আছে |
|---|---|
| **My Microsite** | আপনার Department / Office-এর page (editor) খোলে; সেখানে উপরের **Microsite ▾** থেকে menu, homepage, header & footer, statistics, pages |
| **Content** | News, Events, Notices, Gallery, Slider, Documents, FAQ, Achievements, Testimonials, Announcements, Media |
| **Academic** | Faculties, Departments, Programs (প্রতিটি খুললে তার Hub — সব কাজ এক জায়গায়), Courses, Admission, Tuition Fees, Waiver Calculator, Scholarships, Academic Calendar … |
| **People** | Offices, Administration, Leadership, Committees, Alumni |
| **Services** | Transport, Labs, Facilities, Accreditation & Ranking, International, CSR, Partners |
| **Communication** | Inquiries, Newsletter |
| **Appearance, Site, System** | শুধু Admin / Super Admin (Part C) |

আপনার অনুমতি অনুযায়ী এর কিছু group বা item না-ও দেখাতে পারে — এটি স্বাভাবিক।

**উপরের bar (ডানে):**

| জিনিস | কাজ |
|---|---|
| 🔍 Search (`Ctrl + K`) | System-এর যেকোনো কিছু খোঁজা (শুধু আপনার অনুমতির মধ্যে) |
| **Working in** | কোন Department / Office-এ কাজ করছেন (Section 4) |
| 🔔 | Notification (Section 14) |
| **View site** | Website নতুন tab-এ খোলে |
| আপনার নাম | Profile, 2FA, ভাষা, Logout |

---

## 4. কোন Department / Office-এ কাজ করছি

অনেকে একাধিক জায়গায় কাজ করেন — যেমন CSE Department ও Robotics Lab। উপরের bar-এর **Working in** থেকে বেছে নিন।

📷 *Working in dropdown*

* যেটি বেছে নেবেন, নতুন content সেখানেই default হিসেবে যুক্ত হবে।
* **My Microsite** menu সেই জায়গার microsite দেখায়।
* Department user তার Department-এর অধীন Program-ও দেখেন; Faculty user তার সব Department ও Program দেখেন।

---

## 5. তালিকা (list) screen

যেকোনো item-এ (যেমন **Content › Notices**) click করলে তালিকা খোলে। সব module-এ একই রকম।

📷 *Notices list*

| অংশ | কী করে |
|---|---|
| **+ New …** (উপরে ডানে) | নতুন তৈরি |
| Tab: **All / Active / Scheduled / Inactive / Trash** | ধরন অনুযায়ী দেখা; **Scheduled** = ভবিষ্যতে প্রকাশ হবে; **Trash** = মুছে ফেলা (Section 12) |
| 🔍 Search ও filter (Category, Show in, Date …) | খোঁজা ও ছাঁকা |
| **Active** switch (প্রতিটি সারিতে) | এক click-এ website-এ দেখানো / লুকানো |
| **Show in** column | কোথায় দেখায়; **🌐 Main** = মূল website-এও |
| ● / ○ (ভাষা) | ● = সেই ভাষায় লেখা আছে, ○ = নেই |
| 📌 / ⏰ | 📌 Pinned (উপরে আটকানো), ⏰ Scheduled |
| **⋯** (সারির শেষে) | View on site, Edit, Duplicate, Activity, Delete |
| ☐ (বাম পাশে) | একাধিক বেছে একসাথে **Activate / Deactivate / Delete** |
| ⠿ (কিছু তালিকায়) | টেনে (drag) ক্রম বদলানো |

---

## 6. Form — নতুন তৈরি ও সম্পাদনা

সব content-এর form একই নকশায়: **বামে লেখা**, **ডানে প্রকাশের নিয়ন্ত্রণ**।

📷 *Notice form*

| জায়গা | কী আছে |
|---|---|
| বামে tab | **Content** (title, body …), module-অনুযায়ী অতিরিক্ত tab (যেমন Attachments), **SEO**, **Activity** |
| বামে উপরে **Language** | English / বাংলা tab (Section 10) |
| ডানে **Publishing** | Active, Publish date, Expires (Section 7) |
| ডানে **Show in** | কোথায় দেখাবে (Section 8) |
| ডানে **Highlight** | Homepage-এ বিশেষ জায়গায় (Section 9) |
| ডানে **Category / Tags** | শ্রেণি ও tag |
| উপরে **Save ▾** | **Save**, **Save & create another**, **Save & view** |

**কিছু নিয়ম:**

* `*` চিহ্নিত ঘর বাধ্যতামূলক। ভুল থাকলে ঘরের নিচে লাল লেখায় কারণ দেখায়।
* **Web address** (URL) title থেকে নিজে তৈরি হয়। বদলাতে চাইলে **Edit web address** — পুরনো address স্বয়ংক্রিয়ভাবে নতুনটিতে নিয়ে যায়, তাই পুরনো link ভাঙে না।
* Save না করে page ছাড়তে চাইলে system সতর্ক করবে।
* **Duplicate** (তালিকার ⋯ থেকে): একই রকম আরেকটি তৈরি করতে — যেমন প্রতি semester-এর routine notice।

---

## 7. প্রকাশ: Active, Publish date, Expires

Website-এ কোনো কিছু দেখাবে **শুধু তখন**, যখন:

1. **Active** চালু (●), **এবং**
2. **Publish date** এখন বা অতীতে (খালি রাখলে সাথে সাথে), **এবং**
3. **Expires** (যেখানে আছে) এখনো আসেনি।

| আপনি চান | কী করবেন |
|---|---|
| এখনই প্রকাশ | Active চালু, Publish date খালি বা আজ |
| পরে প্রকাশ (যেমন কাল সকাল ৯টায়) | Active চালু, Publish date = কাল ৯:০০ → তালিকায় ⏰ **Scheduled**; সময় হলে নিজে থেকেই দেখাবে |
| লিখে রাখি, এখন প্রকাশ নয় | Active বন্ধ (○) → **Inactive** tab-এ থাকে |
| নির্দিষ্ট দিন পর সরিয়ে দেওয়া (Notice, Slider, Announcement) | **Expires** তারিখ দিন — Notice board থেকে সরবে, archive-এ থাকবে |
| সাময়িকভাবে লুকানো | তালিকায় Active switch বন্ধ — মুছবেন না |

> Publish date website-এ "প্রকাশের তারিখ" হিসেবেও দেখায় এবং তালিকার ক্রম ঠিক করে।

---

## 8. "Show in" — কোথায় দেখাবে

**Show in** বলে দেয় content কোন Faculty / Department / Program / Office / Lab-এর microsite-এ দেখাবে। একাধিক বেছে নেওয়া যায় — যেমন একটি seminar-এর news CSE ও EEE দুটোতেই।

* আপনার **Working in** জায়গাটি নিজে থেকেই বসানো থাকে।
* আপনি শুধু নিজের অনুমতির মধ্যের জায়গাগুলো দেখবেন। অন্তত একটি থাকতেই হবে।
* Faculty-তে যুক্ত content সেই Faculty-র microsite-এ দেখায়; Faculty চাইলে তার Department-গুলোর content-ও দেখাতে পারে (microsite-এর setting)।

---

## 9. Main site-এ দেখানো ও Highlight

### 9.1 Main site (মূল website)

Department-এর content **নিজে থেকে মূল website-এ যায় না**। মূল website-এর তালিকায় (যেমন `/notices`) দেখাবে কিনা, তা **শুধু Admin** ঠিক করেন।

* **Department / Office user:** form-এ **Request main site** চাপুন — Admin-এর কাছে অনুরোধ যায়। Admin অনুমোদন করলে notification পাবেন, আর তালিকায় **🌐 Main** দেখাবে।
* **Admin:** form-এ **🌐 Main site** switch, অথবা **Main Site Queue** থেকে (Part C)।

### 9.2 Highlight

Highlight মানে কোনো জায়গার **homepage-এ বিশেষভাবে দেখানো** (যেমন "Latest News"-এর প্রথম বড় card)। প্রতিটি জায়গার highlight আলাদা:

* CSE-এর homepage-এ highlight → CSE user নিজে করতে পারেন (form-এর **Highlight** অংশে CSE switch)।
* মূল website-এর homepage-এ highlight → শুধু Admin।
* **Until** তারিখ দিলে সেই দিনের পর highlight নিজে থেকে উঠে যায়; content website-এ থেকেই যায়।

---

## 10. দুই ভাষায় লেখা

Form-এর উপরে **Language** tab: **English ●  বাংলা ○**।

* **Default ভাষা** (সাধারণত English) বাধ্যতামূলক; অন্য ভাষা ঐচ্ছিক।
* ● = লেখা আছে, ○ = নেই। তালিকায় **Missing বাংলা** filter দিয়ে অনুবাদ বাকি থাকা content খুঁজুন।
* বাংলা না থাকলে বাংলা website-এ default ভাষার লেখা দেখায় (setting অনুযায়ী)।
* বাংলা লেখার জন্য Unicode (Avro, Google Input ইত্যাদি) ব্যবহার করুন — Bijoy-র পুরনো ANSI font নয়।

---

## 11. Media Library — ছবি ও file

System-এ upload করা **সব** ছবি ও file এক জায়গায়, WordPress-এর মতো। একবার upload করলে যেকোনো form থেকে আবার ব্যবহার করা যায়।

📷 *Media Library*

### 11.1 Upload

**Content › Media** (বা **Site › Media Library**) → **⬆ Upload** বা file টেনে এনে ছেড়ে দিন।

| নিয়ম | কেন |
|---|---|
| **Alt text** দিন (ছবিতে কী আছে, এক বাক্যে) | দৃষ্টি-প্রতিবন্ধী ব্যবহারকারী ও Google-এর জন্য; না দিলে system সতর্ক করবে |
| ছবি সাধারণত ২ MB-এর কম, চওড়া ১৬০০–২০০০ px যথেষ্ট | System নিজেই ছোট সংস্করণ ও WebP তৈরি করে |
| File-এর নাম অর্থবহ (যেমন `mid-term-routine-fall-2026.pdf`) | খুঁজে পেতে সুবিধা |
| Folder ব্যবহার করুন (যেমন CSE › Notices) | গোছানো থাকে |

### 11.2 Form থেকে ব্যবহার

ছবি / file-এর ঘরে click → Media Library খোলে → আগের file বেছে **Select**, অথবা নতুন upload।

### 11.3 ছবি বদলানো ও মোছা

* File-এ click → ডানে **Details**: title, alt text, caption বদলানো; **Used in** — কোথায় কোথায় ব্যবহৃত।
* **Replace** — একই file-এর নতুন সংস্করণ (সব জায়গায় একসাথে বদলায়)।
* **Delete** — কোথাও ব্যবহৃত হলে system তালিকা দেখিয়ে সতর্ক করবে।
* **Unused only** filter — কোথাও ব্যবহৃত হয় না এমন file।

---

## 12. Delete, Trash ও Restore

* **Delete** করলে content **Trash**-এ যায়, একেবারে মুছে যায় না। Website থেকে সাথে সাথে সরে যায়।
* ভুল করে মুছলে: তালিকার **🗑 Trash** tab → সারির **Restore** (বা একাধিক বেছে bulk restore)।
* **Delete permanently** (চিরতরে মোছা) শুধু Super Admin পারেন, নাম টাইপ করে নিশ্চিত করার পর।
* কোনো Faculty-র অধীনে active Department থাকলে Faculty মুছতে গেলে system সতর্ক করে ও তালিকা দেখায়।

> **মোছার বদলে লুকানো ভালো:** সাময়িক বন্ধ করতে **Active** switch বন্ধ করুন।

---

## 13. পরিবর্তনের ইতিহাস (Activity)

প্রতিটি record-এর form-এ **Activity** tab: কে, কখন, কী বদলেছেন — পুরনো ও নতুন মান পাশাপাশি। কোনো ভুল পরিবর্তন খুঁজতে বা আগের লেখা ফিরিয়ে আনতে কাজে লাগে।

* ইতিহাস ন্যূনতম ৬০ দিন থাকে।
* কেউ ইতিহাস মুছতে বা বদলাতে পারে না।

---

## 14. Notification ও Content Update Reminder

উপরের 🔔-তে notification আসে, যেমন:

* Main site request অনুমোদিত / বাতিল
* আপনার Department-এ নতুন inquiry
* **Content Update Reminder:** কোনো section নির্দিষ্ট দিন ধরে update হয়নি (যেমন "CSE Notices — ৯৫ দিন কোনো নতুন notice নেই")। Email-ও আসতে পারে।

**My Microsite › Overview**-এ আপনার microsite-এর অবস্থা: ✅ ঠিক আছে, ⚠️ পুরনো হয়ে যাচ্ছে, ❌ কোনো content নেই — পাশে **Add …** button দিয়ে সরাসরি নতুন যোগ।

Reminder বন্ধ করার উপায়: নতুন content প্রকাশ করা — তখনই হিসাব আবার শূন্য থেকে শুরু হয়।

---

## 15. সাধারণ প্রশ্ন ও সমস্যা

| প্রশ্ন | উত্তর |
|---|---|
| Save করলাম, website-এ দেখছি না | (১) **Active** চালু? (২) **Publish date** ভবিষ্যতে নয় তো? (৩) **Expires** পেরিয়ে যায়নি? (৪) **Show in** ঠিক জায়গা? (৫) মূল website-এ খুঁজছেন? — Department-এর content মূল website-এ যায় শুধু Admin অনুমোদন দিলে (Section 9)। (৬) Browser refresh করুন |
| কোনো menu / button দেখছি না | আপনার অনুমতি নেই, বা সেই module বন্ধ — Admin-কে জানান |
| অন্য Department-এর content দেখছি না | স্বাভাবিক — আপনি শুধু নিজের scope দেখেন |
| বাংলা লেখা ভাঙা দেখাচ্ছে | Unicode keyboard ব্যবহার করুন (Section 10) |
| PDF / ছবি upload হচ্ছে না | File-এর ধরন (PDF, JPG, PNG, WebP, DOCX, XLSX …) ও আকার (সর্বোচ্চ ৬৪ MB) দেখুন |
| ভুল করে মুছে ফেলেছি | **Trash** tab → **Restore** (Section 12) |
| কে বদলেছে জানতে চাই | Form-এর **Activity** tab (Section 13) |
| Website-এর ঠিকানা (URL) বদলাতে চাই | Form-এ **Edit web address** — পুরনো link স্বয়ংক্রিয়ভাবে নতুনটিতে যায় |
| কোনো error message এসেছে | Message-টি ও কী করছিলেন তা screenshot সহ Web team-কে পাঠান |

**সাহায্য:** Web team — *(DIU-র যোগাযোগ: email / phone / extension এখানে বসবে)*

---

## 16. শব্দকোষ

| শব্দ | অর্থ |
|---|---|
| Admin Panel | যেখান থেকে website চালানো হয় |
| Microsite | Faculty / Department / Program / Office / Lab-এর নিজস্ব অংশ, মূল website-এর ভেতরে |
| Main site | মূল university website (homepage ও মূল তালিকা) |
| Active | চালু — website-এ দেখানোর অনুমতি |
| Publish date | কখন থেকে দেখাবে; ভবিষ্যতের তারিখ = Scheduled |
| Expires | কখন থেকে আর দেখাবে না |
| Show in | কোন microsite-এ দেখাবে |
| Highlight | Homepage-এ বিশেষভাবে সামনে আনা |
| Trash | মুছে ফেলা জিনিসের জায়গা, যেখান থেকে ফেরত আনা যায় |
| Role | আপনি কী কী করতে পারবেন (যেমন Department Editor) |
| Scope | কোন Department / Office-এর data-তে কাজ করবেন |
| Module | System-এর একটি অংশ (যেমন Notice, Transport) — Super Admin চালু / বন্ধ করেন |
| Alt text | ছবির লিখিত বর্ণনা |
| Slug / Web address | Page-এর ঠিকানার শেষ অংশ (যেমন `/news/convocation-2026`) |
