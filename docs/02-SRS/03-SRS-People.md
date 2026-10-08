# Software Requirements Specification (SRS)
## Part 3 — Administration & People Modules (M-12 থেকে M-17)

| বিষয় | তথ্য |
|---|---|
| Document | SRS Part 3: Administration & People Modules |
| Version | 0.1 (Draft) |
| Date | 2026-10-08 |
| Status | Review-এর জন্য অপেক্ষমাণ |
| Based on | [BRD v1.1](../01-BRD.md), [SRS Part 0](00-SRS-Overview.md), [Part 1](01-SRS-Core.md), [Part 2](02-SRS-Academic.md), `project_diu` analysis |

> Part 0-এর Common Requirement (FR-GEN) সব module-এ প্রযোজ্য।

## সূচি

| # | Module | Code | Relation Target | Microsite | Person Source | Dependency |
|---|---|---|---|---|---|---|
| M-12 | [Office / Administrative Unit](#m-12-office--administrative-unit) | OFF | ✔ | ✔ | — | — |
| M-13 | [Administration](#m-13-administration) (Administration + Support Staff একত্রিত) | ADMN | — | — | ✔ | — (optional: Office) |
| M-14 | *একত্রিত — M-13-এ* | — | — | — | — | — |
| M-15 | [Leadership / Authorities](#m-15-leadership--authorities) | LEAD | — | — | — (Assignable; DB-04 সংশোধন) | — |
| M-16 | [Committee](#m-16-committee) | COM | — | — | — | — |
| M-17 | [Alumni](#m-17-alumni) | ALUM | — | — | ✔ | — (optional: Department, Program) |

---

## 1. মূল Design ধারণা: Person Source ও Assignment

### 1.1 সমস্যা (বর্তমান system)

বর্তমান `project_diu`-তে একই ব্যক্তির নাম, পদবি, phone, email ও ছবি অনেক table-এ আলাদাভাবে লেখা আছে:

| Table | কী রাখে |
|---|---|
| `admins` | কর্মকর্তার মূল তথ্য |
| `office_admins` | Office-এর সদস্য (নাম, পদবি, phone আবার লেখা) |
| `section_employees` | Section-এর সদস্য |
| `coordination_officers` | Department / Program-এর coordinator (নাম, phone আবার লেখা) |
| `admission_contacts` | Admission contact (নাম, phone আবার লেখা) |
| `support_contacts` | উদ্দেশ্যভিত্তিক support contact |
| `contacts` | Faculty / Department contact |

ফলে একজনের phone নম্বর বদলালে ৪–৫ জায়গায় আলাদাভাবে বদলাতে হয়, এবং প্রায়ই কোথাও পুরনো তথ্য থেকে যায়।

### 1.2 সমাধান

**ব্যক্তির তথ্য এক জায়গায় থাকবে (Person Source), আর অন্য সব জায়গা তাকে শুধু reference করবে (Assignment)।**

```
Person Source (ব্যক্তির মূল তথ্য)            Assignment (কোথায়, কী ভূমিকায়)
─────────────────────────────────           ───────────────────────────────────────────
Faculty Member (M-07)  ─┐                   Registrar Office    → "Deputy Registrar"  #2
Administration (M-13) ─┼── একজন ব্যক্তি ──> Exam Office / Section → "Officer"        #5
                        │                   CSE Department      → "Program Coordinator"
Leadership (M-15)      ─┤                   Admission Contact   → "International"
Alumni (M-17)          ─┘                   Committee: Syndicate → "Member Secretary"
```

### 1.3 Core-এ সংযোজন (SRS Part 1 সংশোধন)

এই design-এর জন্য **C-13 Content Relation Engine**-এ দুটি নতুন requirement যোগ হবে:

| ID | Requirement | Priority |
|---|---|---|
| FR-REL-09 | **Person Source registry:** module নিজেকে person source হিসেবে register করবে (label, model, নাম / ছবি / পদবি / contact দেওয়ার contract)। একটি reusable "Person Picker" component সব active source থেকে search করে ব্যক্তি select করতে দেবে, অথবা "Manual entry" (নাম, পদবি, ছবি, contact সরাসরি লেখা) | Must |
| FR-REL-10 | **Assignment:** ব্যক্তি ↔ যেকোনো **Assignable** (Faculty, Department, Program, Office, Office Section, Lab, Committee, Event ইত্যাদি; পূর্ণ তালিকা [Part 0, Appendix 6.8](00-SRS-Overview.md#68-assignable-p-02-এর-অন্য-দিক--১৪টি)) — module নিজেকে Assignable হিসেবে register করবে; সাথে ভূমিকা (role label, multi-language), ক্রম, শুরু ও শেষ তারিখ, public-এ দেখাবে কিনা, এবং ঐচ্ছিক override (assignment-ভিত্তিক phone / extension)। Target-এর page-এ "People" তালিকা এখান থেকে আসবে | Must |

**নিয়ম:**

* **BRL-REL-03:** Assignment ব্যক্তির মূল তথ্য copy করবে না, শুধু reference। মূল তথ্য বদলালে সব জায়গায় সঙ্গে সঙ্গে বদলাবে।
* **BRL-REL-04:** Person source module inactive হলে তার ব্যক্তিদের assignment frontend-এ লুকাবে, data থাকবে।
* **BRL-REL-05:** ব্যক্তি inactive বা delete হলে তার সব assignment frontend থেকে লুকাবে, এবং admin-এ "inactive person" সতর্কতা দেখাবে।
* **BRL-REL-06:** শেষ তারিখ পার হওয়া assignment automatically "Former" হিসেবে গণ্য হবে (যেমন সাবেক Head)।

---

## M-12 Office / Administrative Unit

**Purpose:** বিশ্ববিদ্যালয়ের প্রশাসনিক ও সেবা unit (VC Office, Registrar Office, Exam Office, Library, IT Section, International Affairs ইত্যাদি) পরিচালনা, এবং প্রতিটি Office-এর microsite।

**Actors:** Admin, Office role (যে Office-এর permission আছে), Visitor

**Data:**

| অংশ | তথ্য |
|---|---|
| Office | নাম, short name, slug, Office type (Administrative / Academic Support / Student Service ইত্যাদি), logo / ছবি, cover, overview, Office Head (Person Picker), contact (email, phone, extension, অবস্থান, Campus), অফিস সময়, parent Office (ঐচ্ছিক), নিজস্ব page দেখাবে কিনা (বর্তমান `show_page`), ক্রম |
| Section / Sub-unit | Office-এর অধীন section (যেমন Registrar Office → Student Service, Employee Service), নাম, বিবরণ, contact, ক্রম |
| Service | Office যে সেবা দেয় (যেমন "Transcript issue", "Certificate verification"): title, বিবরণ, প্রয়োজনীয় document, fee, সময়, আবেদন link / form file, দায়িত্বপ্রাপ্ত ব্যক্তি (Assignment) |
| Support Contact | উদ্দেশ্যভিত্তিক contact ("ভর্তির পর ID card সমস্যা → কাকে যোগাযোগ করবেন"): উদ্দেশ্য, ব্যক্তি (Person Picker), অবস্থান |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-OFF-01 | Office CRUD; Office type একটি Taxonomy vocabulary | BR-MS-01 | Must |
| FR-OFF-02 | Relation Target ও Microsite entity (parent নেই, fallback: নিজের → Main site; parent Office থাকলে তার menu-তে fallback ঐচ্ছিক) | BR-MS-01, BR-MS-04 | Must |
| FR-OFF-03 | Office তৈরি হলে dynamic permission তৈরি: `office.{slug}.manage`, `office.{slug}.content`, `office.{slug}.menu` (FR-ROLE-07) | Q-13, Q-14 | Must |
| FR-OFF-04 | Section / Sub-unit CRUD, Office-এর ভেতরে (Relation Manager); Section একটি Assignable, যাতে Section-এ ব্যক্তি assign করা যায় (Section content-এর Relation Target নয়) | Q-08 | Must |
| FR-OFF-05 | Office ও Section-এ ব্যক্তি assign (FR-REL-10): ভূমিকা, ক্রম, public কিনা; Office page-এ "Our Team" তালিকা | Q-08 | Must |
| FR-OFF-06 | Service catalog CRUD; Office page-এ "Services" section; সব Office-এর service একসাথে খোঁজার page (`/services`) | — | Should |
| FR-OFF-07 | Support Contact CRUD; "Who to contact" page (`/support-contacts`): উদ্দেশ্য দিয়ে search | — | Should |
| FR-OFF-08 | Office-এর নিজস্ব page, menu, header, footer, homepage — Core-এর Pages, Menu, Header & Footer, Homepage Builder দিয়ে (বর্তমান `registrar_offices`, `proctor_offices`-এর মতো আলাদা table নয়) | BR-MS-10 | Must |
| FR-OFF-09 | Office listing (`/offices`): type দিয়ে grouped; layout: Grid, List, Directory | BR-THM-02 | Must |
| FR-OFF-10 | Office microsite (`/office/{slug}`): default section — Overview, Statistics (Stat Set থাকলে), Head-এর বার্তা, Services, Team, Notices, Documents, Contact | BR-MS-07 | Must |
| FR-OFF-11 | "Own page নেই" Office (show_page = false): listing-এ শুধু contact card দেখাবে, microsite থাকবে না | — | Should |
| FR-OFF-12 | Office-এর related content (Notice, News, Documents, FAQ) Relation Engine দিয়ে; Office role-এর user content তৈরি করলে automatically নিজের Office-এ যুক্ত হবে | BR-SEC-05 | Must |
| FR-OFF-13 | Homepage section: "Offices Directory", "Quick Services" | FR-GEN-102 | Could |
| FR-OFF-14 | Statistics: Office-এর নিজস্ব Stat Set (C-20) — যেমন Library: "50,000+ Books" (manual); Service ও Team সদস্য সংখ্যা automatic | BR-STAT-01 | Must |

### Business Rules

* **BRL-OFF-01:** Office slug পরিবর্তন করলে dynamic permission-এর নাম পরিবর্তন হবে না (permission Office ID ভিত্তিক থাকবে, slug শুধু label)।
* **BRL-OFF-02:** Office permanent delete হলে তার dynamic permission মুছবে এবং role থেকে সরবে; soft delete-এ থাকবে।
* **BRL-OFF-03:** Office role-এর user শুধু নিজের Office-এর profile, Section, Service, Support Contact, page, menu ও related content পরিচালনা করবে।

### Permissions

Static: `office.view`, `office.create`, `office.delete`, `office.manage-all`
Dynamic (প্রতিটি Office): `office.{id}.manage`, `office.{id}.content`, `office.{id}.menu`

### Acceptance Criteria

* **AC-OFF-01:** "Proctorial Office" তৈরি করলে `/office/proctorial-office` microsite তৈরি হবে এবং role editor-এ "Proctorial Office: Manage / Content / Menu" permission দেখাবে।
* **AC-OFF-02:** "Registrar Office Staff" role-এর user Exam Office-এর কোনো page edit করতে পারবে না।
* **AC-OFF-03:** Registrar Office-এর নিজস্ব menu না থাকলে main site menu দেখাবে।

---

## M-13 Administration

> **BRD v1.5 (Q-25):** আগের M-13 Administration ও M-14 Support Staff একত্র করে একটি module। কারণ: বর্তমান `admins` table-এই Management, Officer ও Staff সবাই আছেন, এবং আলাদা `staff` table-এও staff — দুটি আলাদা directory অর্থহীন। শিক্ষক (Faculty Member, M-07) ছাড়া সব employee এখানে; কে কোথায় কোন ভূমিকায় দেখাবেন তা Assignment দিয়ে।

**Purpose:** শিক্ষক ছাড়া বিশ্ববিদ্যালয়ের সব employee-র একটি directory — উচ্চ ব্যবস্থাপনা, কর্মকর্তা ও সহায়ক কর্মী (বর্তমানে `admins` ৬০৩ + `staff` ৩৭৯)। এটি একটি **Person Source**।

**Actors:** Admin, HR / Registrar role, Office role (নিজের Office-এর staff, permission থাকলে), Visitor

**Data:** নাম, slug, employee ID (বর্তমান `unique_id`), পদবি (free text, যেমন "Director (F & A)"), **Administration Category** (একটি ক্রমযুক্ত তালিকা: Management, Director, Additional Registrar, … Officer, … Office Assistant, Attendant, Driver, Security Guard, Cleaner — প্রতিটির public visibility সহ), ছবি, email, phone, extension, Campus, primary Office, biography (ঐচ্ছিক), ক্রম।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-ADMN-01 | Member CRUD; Person Source হিসেবে register | Q-08, Q-25 | Must |
| FR-ADMN-02 | **Administration Category** CRUD: একটি ক্রমযুক্ত তালিকা (উচ্চ থেকে নিম্ন); প্রতিটি category-র `group` (Management / Officer / Support) এবং public-এ দেখাবে কিনা (Support-এর default: শুধু admin-এ) | Q-25 | Must |
| FR-ADMN-03 | Primary Office (ঐচ্ছিক, Office module active থাকলে) এবং একাধিক Office / Section / Department / Committee / Leadership পদে assignment (FR-REL-10) — একই ব্যক্তি বিভিন্ন জায়গায় বিভিন্ন ভূমিকায় | Q-08, Q-25 | Must |
| FR-ADMN-04 | Directory page (`/administration`, URL segment configurable): Category / group, Office, Campus দিয়ে filter; নাম, পদবি দিয়ে search; শুধু public category-র ব্যক্তি; layout: Grid, List, Table | BR-THM-02 | Must |
| FR-ADMN-05 | Profile page (ঐচ্ছিক, setting): ছবি, পদবি, Office, contact, biography, সব current assignment | — | Should |
| FR-ADMN-06 | Excel / CSV bulk import ও export | — | Must |
| FR-ADMN-07 | Contact field-এর visibility: email / phone / extension প্রতিটি public কিনা আলাদাভাবে | NFR-SEC | Should |
| FR-ADMN-08 | Staff কোনো Admin Panel user-এর সাথে link করা যাবে (ঐচ্ছিক), যাতে নিজের profile আপডেট করতে পারেন | — | Could |
| FR-ADMN-09 | অবসর বা চাকরি ছাড়লে "Deactivate" — সব assignment frontend থেকে লুকাবে (BRL-REL-05) | BR-DATA-03 | Must |
| FR-ADMN-10 | Transport module (M-31) active থাকলে Driver-কে route-এ assign করা যাবে (Person Picker দিয়ে) | — | Could |

### Business Rules

* **BRL-ADMN-01:** Employee ID অনন্য (দেওয়া হলে)।
* **BRL-ADMN-02:** Office role-এর user শুধু তার Office-এ assign হওয়া staff-এর তথ্য দেখতে পারবে; edit করতে Administration update permission লাগবে।
* **BRL-ADMN-03:** Non-public category-র ব্যক্তি directory-তে দেখাবেন না, কিন্তু কোনো Office বা Committee-তে public assignment থাকলে সেখানে দেখাবেন।

### Permissions

`administration.view`, `administration.create`, `administration.update`, `administration.delete`, `administration.import`

### Acceptance Criteria

* **AC-ADMN-01:** একজন কর্মকর্তার phone নম্বর পরিবর্তন করলে তার Office page, Section তালিকা, Admission Contact ও Committee-তে নতুন নম্বর দেখাবে।
* **AC-ADMN-02:** Directory-তে "Director" category filter দিলে শুধু ওই category-র staff ক্রম অনুযায়ী দেখাবে।
* **AC-ADMN-03:** একই ব্যক্তি Registrar Office-এ "Deputy Registrar", Syndicate-এ "Member Secretary" এবং Admission Contact-এ "Undergraduate Admission" — তিন জায়গায় তিন ভূমিকায় দেখাবেন, তথ্য একবারই রাখা।
* **AC-ADMN-04:** "Driver" category non-public → `/administration`-এ কোনো driver দেখাবে না।

---

## M-14 *(একত্রিত — M-13 Administration-তে)*

আগের Support Staff module M-13-এর সাথে একত্র হয়েছে (BRD v1.5, Q-25)। Module নম্বর M-14 খালি রাখা হলো, যাতে অন্য module-এর নম্বর ও reference পরিবর্তন না হয়।

---

## M-15 Leadership / Authorities

**Purpose:** বিশ্ববিদ্যালয়ের শীর্ষ নেতৃত্ব (Chairman, Board of Trustees, Vice Chancellor, Pro-VC, Treasurer, Registrar ইত্যাদি) এবং তাঁদের বার্তা।

**Actors:** Admin

**Data:** ব্যক্তি (Person Picker: Faculty Member / Administration, অথবা manual — নাম, ছবি), পদ (যেমন Vice Chancellor), group (Board of Trustees / Executive / Academic Leadership), বার্তা (multi-language, rich text), biography, শুরু ও শেষ তারিখ (tenure), ক্রম, homepage-এ বার্তা দেখাবে কিনা।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-LEAD-01 | Leadership position CRUD; ব্যক্তি Person Picker দিয়ে, অথবা manual (যাঁরা অন্য কোনো directory-তে নেই, যেমন Trustee) | — | Must |
| FR-LEAD-02 | Leadership group (Taxonomy) ও ক্রম | — | Must |
| FR-LEAD-03 | Leadership page (`/leadership`): group অনুযায়ী; layout: Grid, Profile Card, List | BR-THM-02 | Must |
| FR-LEAD-04 | Profile / বার্তা page (`/leadership/{slug}`): ছবি, পদ, বার্তা, biography | — | Must |
| FR-LEAD-05 | Tenure: শেষ তারিখ পার হলে "Former" তালিকায় (`/leadership/former`) চলে যাবে (যেমন সাবেক VC-দের তালিকা) | — | Should |
| FR-LEAD-06 | Homepage section: "Message from the Vice Chancellor / Chairman" (কোন পদের বার্তা তা select) | FR-GEN-102 | Must |
| FR-LEAD-07 | একই পদে একসাথে একজনই "current" (যেমন VC); নতুন জন নিয়োগ দিলে আগের জনের শেষ তারিখ set করার প্রস্তাব | — | Should |

### Permissions

`leadership.view`, `leadership.manage`

### Acceptance Criteria

* **AC-LEAD-01:** নতুন VC নিয়োগ দিলে আগের VC "Former Vice Chancellors" তালিকায় যাবে এবং homepage-এ নতুন VC-র বার্তা দেখাবে।

---

## M-16 Committee

**Purpose:** বিভিন্ন committee ও তাদের সদস্য (Board of Trustees, Syndicate, Academic Council, Finance Committee, Disciplinary Committee ইত্যাদি), এবং entity-ভিত্তিক committee (যেমন Lab committee, Department committee)।

**Actors:** Admin, Scoped / Office / Lab user (নিজের entity-র committee)

**Data:**

| অংশ | তথ্য |
|---|---|
| Committee | নাম, slug, category / ধরন, বিবরণ, গঠনের তারিখ, মেয়াদ (term শুরু ও শেষ), owner entity (ঐচ্ছিক: Department, Office, Lab ইত্যাদি), notification / গঠন আদেশ PDF, ক্রম |
| Member | ব্যক্তি (Person Picker বা manual), committee-তে পদ (Chairman, Member, Member Secretary, Convener ইত্যাদি), বাইরের প্রতিষ্ঠান / authority (যেমন "Nominated by UGC"), contact (ঐচ্ছিক override), ক্রম |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-COM-01 | Committee CRUD; category একটি Taxonomy vocabulary | — | Must |
| FR-COM-02 | Member যোগ: Person Picker (Faculty Member, Administration, Alumni) অথবা manual (বাইরের সদস্য) | FR-REL-09 | Must |
| FR-COM-03 | Committee-পদ একটি ক্রমযুক্ত Taxonomy (Chairman প্রথমে, Member Secretary শেষে ইত্যাদি) | — | Must |
| FR-COM-04 | Owner entity (Relation): Department / Office / Lab-এর committee তার microsite-এ দেখাবে (বর্তমান `lab_committees` আলাদা table নয়) | BR-CNT-02 | Must |
| FR-COM-05 | Term: মেয়াদ শেষ হলে পূর্ববর্তী committee archive হিসেবে দেখা যাবে; "Copy to new term" দিয়ে সদস্যসহ নতুন term শুরু | — | Should |
| FR-COM-06 | Committee page (`/committees`, `/committee/{slug}`): category অনুযায়ী; layout: Table, Card, List | BR-THM-02 | Must |
| FR-COM-07 | গঠন আদেশ / notification PDF (Media Library) | — | Should |

### Business Rules

* **BRL-COM-01:** Member-এর ব্যক্তি-তথ্য Person Source থেকে আসবে (BRL-REL-03); manual member-এর তথ্য committee-তেই থাকবে।
* **BRL-COM-02:** Committee member table একটি assignment / pivot-ধরনের table, তবে নিজস্ব business data (পদ, ক্রম) থাকায় soft delete ও `is_active` থাকবে।

### Permissions

`committee.view`, `committee.manage` (scope / Office / Lab অনুযায়ী), `committee.manage-all`

### Acceptance Criteria

* **AC-COM-01:** Syndicate-এ একজন Faculty Member-কে সদস্য করলে তাঁর পদবি পরিবর্তন হলে Syndicate তালিকায় নতুন পদবি দেখাবে।
* **AC-COM-02:** Robotics Lab-এর committee শুধু Robotics Lab microsite-এ দেখাবে।

---

## M-17 Alumni

**Purpose:** প্রাক্তন ছাত্রদের তথ্য, সফলতার গল্প এবং "Pride Alumni" প্রদর্শন। Alumni login portal এই phase-এ নয় (BRD 4.2)। এটি একটি **Person Source**।

**Actors:** Admin, Alumni Affairs Office role, Scoped User (নিজের Department / Program-এর alumni), Visitor (ঐচ্ছিক submission form)

**Data:** নাম, slug, student ID, Department, Program, batch, পাসের বছর, বর্তমান পদবি, প্রতিষ্ঠান, চাকরির ধরন (Job / Business / Higher study / Other), office ঠিকানা, email, phone, social link (Facebook, X, LinkedIn), ছবি, video (YouTube URL বা upload), বিস্তারিত / গল্প, "Pride Alumni" (featured), এখন এই বিশ্ববিদ্যালয়ের শিক্ষক কিনা (বর্তমান `is_faculty`), homepage-এ দেখাবে কিনা, ক্রম।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-ALUM-01 | Alumni CRUD; Department ও Program (Academic module active থাকলে) relation | Q-16 | Must |
| FR-ALUM-02 | Alumni directory (`/alumni`): Department, Program, batch, পাসের বছর, চাকরির ধরন, প্রতিষ্ঠান দিয়ে filter; layout: Grid, List, Card | BR-THM-02 | Must |
| FR-ALUM-03 | Alumni profile / story page (`/alumni/{slug}`) video সহ | — | Must |
| FR-ALUM-04 | "Pride Alumni" featured flag; homepage section "Our Pride Alumni" (slider / grid) | FR-GEN-102 | Must |
| FR-ALUM-05 | Department / Program microsite-এ সেই Department / Program-এর alumni section | BR-MS-07 | Should |
| FR-ALUM-06 | Privacy: email, phone, office ঠিকানা default-এ public নয়; প্রতিটি field আলাদাভাবে public করা যাবে | NFR-SEC | Must |
| FR-ALUM-07 | Public "Alumni information submission" form: visitor-এর জমা দেওয়া তথ্য `is_active = false` অবস্থায় save হবে (`submission_source = public_form`); Alumni Office যাচাই করে active করলে প্রকাশ। Admin list-এ "নতুন জমা" filter | — | Should |
| FR-ALUM-08 | "এখন এই বিশ্ববিদ্যালয়ের শিক্ষক" হলে Faculty Member profile-এর সাথে link (Person Picker) | — | Could |
| FR-ALUM-09 | Excel import / export | — | Must |
| FR-ALUM-10 | Statistics: Department / batch অনুযায়ী alumni সংখ্যা (Program statistics-এর "approved alumni"-তে ব্যবহারযোগ্য) | — | Could |

### Business Rules

* **BRL-ALUM-01:** Student ID অনন্য (দেওয়া হলে)।
* **BRL-ALUM-02:** Submission form-এর spam সুরক্ষা NFR-SEC-06 অনুযায়ী; জমা দেওয়া তথ্য approve-এর আগে public হবে না।

### Permissions

`alumni.view`, `alumni.create`, `alumni.update`, `alumni.delete`, `alumni.approve`, `alumni.import`

### Acceptance Criteria

* **AC-ALUM-01:** "CSE" ও "2015" filter দিলে শুধু CSE-এর ২০১৫ পাস করা alumni দেখাবে, phone নম্বর ছাড়া।
* **AC-ALUM-02:** Visitor-এর জমা দেওয়া তথ্য Alumni Office active না করা পর্যন্ত directory-তে দেখাবে না।

---

## বর্তমান System থেকে পরিবর্তন (Migration Note)

| বর্তমান (`project_diu`) | নতুন system |
|---|---|
| `offices` | Office (M-12) |
| `sections` | Office-এর Section (FR-OFF-04) |
| `registrar_offices`, `registrar_office_categories`, `proctor_offices`, `proctor_office_categories` | Office-এর Pages (C-11) ও Menu (C-08) |
| `admins`, `admin_categories` | Administration (M-13); category → Administration Category |
| `office_admins`, `section_employees` | Assignment (FR-REL-10) → Office / Section |
| `coordination_officers` | Assignment → Department / Program, ভূমিকা "Coordinator" |
| `admission_contacts`, `admission_contact_types` | Assignment → Admission contact group (M-09) |
| `support_contacts` | Office Support Contact (FR-OFF-07) |
| `contacts` (Faculty / Department contact) | Assignment → Faculty / Department |
| `staff`, `staff_categories` | Administration (M-13), একই `administration_members` table-এ; category → Administration Category (group Support) |
| `committees`, `committee_categories`, `lab_committees` | Committee (M-16), owner entity relation সহ |
| `alumnis` | Alumni (M-17) |

> Migration-এর সময় একই ব্যক্তির duplicate record (নাম + email / phone মিলিয়ে) চিহ্নিত করে একটি Person-এ একত্র করার প্রক্রিয়া Data Migration Plan-এ থাকবে।

---

## Traceability Summary (BRD → SRS Part 3)

| BRD Requirement | SRS Requirement |
|---|---|
| Q-08 (Administration ও Office আলাদা), Q-25 (Administration একত্রিত) | FR-OFF, FR-ADMN |
| Q-13, Q-14 (Office permission) | FR-OFF-03, BRL-OFF-01/02/03 |
| BR-MS-01/04/07/10 (Office microsite) | FR-OFF-02, FR-OFF-08, FR-OFF-10 |
| BR-SEC-05 | FR-OFF-12, BRL-OFF-03, BRL-ADMN-02 |
| BR-CNT-02 (Relation) | FR-REL-09/10, FR-COM-04, FR-ALUM-01 |
| BR-THM-02 (Layout) | FR-OFF-09, FR-ADMN-04, FR-LEAD-03, FR-COM-06, FR-ALUM-02 |
