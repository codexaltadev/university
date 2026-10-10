# Software Requirements Specification (SRS)
## Part 2 — Academic Modules (M-01 থেকে M-11)

| বিষয় | তথ্য |
|---|---|
| Document | SRS Part 2: Academic Modules |
| Version | 0.2 — 2026-10-10: M-10 তিনটি module — `tuition`, `waiver`, `scholarship` (DB-03 v1.4) |
| Date | 2026-10-08 |
| Status | Review-এর জন্য অপেক্ষমাণ |
| Based on | [BRD v1.1](../01-BRD.md), [SRS Part 0](00-SRS-Overview.md), [SRS Part 1](01-SRS-Core.md), `project_diu` analysis |

> Part 0-এর Common Requirement (FR-GEN: soft delete, `is_active`, `published_at`, slug, multi-language, scope, Media Picker, SEO, log, layout) সব module-এ প্রযোজ্য, তাই এখানে আবার লেখা হয়নি।

## সূচি

| # | Module | Code | Relation Target | Microsite | Dependency |
|---|---|---|---|---|---|
| M-01 | [Faculty / School](#m-01-faculty--school) | FAC | ✔ | ✔ | — |
| M-02 | [Department](#m-02-department) | DEPT | ✔ | ✔ | Faculty |
| M-03 | [Program](#m-03-program) | PROG | ✔ | ✔ | Department |
| M-04 | [Course](#m-04-course) | CRS | ✔ | — | Program |
| M-05 | [Academic Session / Semester](#m-05-academic-session--semester) | SESS | — | — | — |
| M-06 | [Curriculum](#m-06-curriculum) | CURR | — | — | Program, Course |
| M-07 | [Faculty Member / Teacher](#m-07-faculty-member--teacher) (Person Source) | FM | — | — | Department |
| M-08 | [Campus / Location](#m-08-campus--location) | CAMP | ✔ | — | — |
| M-09 | [Admission Info](#m-09-admission-info) | ADM | — | — | — (optional: Program) |
| M-10 | [Tuition, Waiver & Scholarship](#m-10-tuition-waiver--scholarship) | FEE | — | — | Program |
| M-11 | [Academic Calendar](#m-11-academic-calendar) | CAL | — | — | — (optional: Program) |

### Academic Hierarchy

```
Faculty ──< Department ──< Program ──< Curriculum ──< Course
                │              │
                │              ├── Major / Specialization
                │              ├── Tuition Fee, Eligibility
                │              └── Career Path, Industry, Facilitator ...
                └──< Faculty Member
```

---

## M-01 Faculty / School

**Purpose:** বিশ্ববিদ্যালয়ের Faculty বা School (যেমন Faculty of Science and Information Technology) পরিচালনা, এবং প্রতিটি Faculty-র microsite।

**Actors:** Admin, Faculty Scoped User (নিজের Faculty)

**Data:** নাম, short name (যেমন FSIT), slug, logo / photo, cover image, overview (rich text), Dean-এর বার্তা (Faculty Member বা Leadership থেকে link), contact (email, phone, অবস্থান), statistics, ক্রম, display settings।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-FAC-01 | Faculty CRUD; নাম, short name, overview multi-language | BR-CNT-01 | Must |
| FR-FAC-02 | Relation Target ও Microsite entity হিসেবে register (hierarchy-র শীর্ষ) | BR-CNT-02, BR-MS-01 | Must |
| FR-FAC-03 | Dean নির্বাচন Person Assignment (FR-REL-10, ভূমিকা "Dean") দিয়ে — Faculty Member থেকে, অথবা manual (নাম, পদবি, ছবি); এবং Dean-এর বার্তা | BR-CNT-01 | Must |
| FR-FAC-04 | Statistics: Faculty-র নিজস্ব Stat Set (C-20) — Department, Program, Faculty Member সংখ্যা automatic; ছাত্র সংখ্যা ও অন্যান্য manual | BR-STAT-01 | Must |
| FR-FAC-05 | Faculty listing page (`/faculties`) ও details / microsite home (`/faculties/{slug}`, FR-GEN-34) | BR-MS-01 | Must |
| FR-FAC-06 | Microsite home-এ default section: Overview, **Statistics**, Dean-এর বার্তা, Departments, Programs, Latest News, Upcoming Events, Notices (Homepage Builder দিয়ে পরিবর্তনযোগ্য) | BR-MS-07, BR-STAT-01 | Must |
| FR-FAC-07 | Listing layout: Grid, List, Card | BR-THM-02 | Must |
| FR-FAC-08 | Homepage section: "Our Faculties" | FR-GEN-102 | Must |
| FR-FAC-09 | "Show in listing" flag (বর্তমান `show_list`): কিছু Faculty listing-এ না দেখিয়ে শুধু সরাসরি URL-এ রাখা | — | Should |

### Business Rules

* **BRL-FAC-01:** Faculty-র অধীনে active Department থাকলে Faculty delete করা যাবে না (FR-GEN-08)।
* **BRL-FAC-02:** Faculty Scoped User নিজের Faculty-র নাম ও slug পরিবর্তন করতে পারবে না; overview, ছবি, contact ও microsite settings পারবে।

### Permissions

`faculty.view`, `faculty.create`, `faculty.update`, `faculty.delete`, `faculty.update-profile` (scoped user-এর জন্য সীমিত edit)

### Acceptance Criteria

* **AC-FAC-01:** নতুন Faculty তৈরি করলে `/faculties/{slug}` URL-এ default microsite home দেখাবে, main menu সহ (নিজস্ব menu না থাকায়)।
* **AC-FAC-02:** Faculty-তে ৩টি Department যোগ করলে statistics-এ "3 Departments" automatically দেখাবে।

---

## M-02 Department

**Purpose:** Faculty-র অধীন Department পরিচালনা, এবং প্রতিটি Department-এর microsite।

**Actors:** Admin, Faculty Scoped User (অধীন Department), Department Scoped User (নিজের)

**Data:** Faculty, নাম, short name (যেমন CSE), slug, photo, cover image, about title ও content, Head নির্বাচন ও বার্তা, contact, external website link (ঐচ্ছিক), statistics, ক্রম।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-DEPT-01 | Department CRUD, Faculty বাধ্যতামূলক | BR-CNT-01 | Must |
| FR-DEPT-02 | Relation Target ও Microsite entity (parent: Faculty) | BR-CNT-02, BR-MS-01 | Must |
| FR-DEPT-03 | Head নির্বাচন ও বার্তা (FR-FAC-03-এর মতো) | BR-CNT-01 | Must |
| FR-DEPT-04 | External website link: থাকলে listing থেকে click করলে সেখানে যাবে (বর্তমান `department_link`) | — | Should |
| FR-DEPT-05 | Statistics: Department-এর নিজস্ব Stat Set (C-20) — Program, Faculty Member সংখ্যা automatic; অন্যান্য manual | BR-STAT-01 | Must |
| FR-DEPT-06 | Department listing (`/departments`, Faculty অনুযায়ী grouped / filter) ও microsite (`/{faculty}/{slug}`, যেমন `/fsit/cse` — FR-GEN-34) | BR-MS-01 | Must |
| FR-DEPT-07 | Microsite default section: About, **Statistics**, Head-এর বার্তা, Programs, Faculty Members, News, Events, Notices, Achievements | BR-MS-07, BR-STAT-01 | Must |
| FR-DEPT-08 | Department অন্য Faculty-তে স্থানান্তর (শুধু Admin); স্থানান্তরের পর scope ও menu fallback নতুন Faculty অনুযায়ী হবে | — | Should |
| FR-DEPT-09 | Listing layout: Grid, List, Card; Homepage section: "Departments" | BR-THM-02 | Must |

### Business Rules

* **BRL-DEPT-01:** একই Faculty-তে Department-এর short name অনন্য।
* **BRL-DEPT-02:** অধীনে active Program বা Faculty Member থাকলে delete করা যাবে না।
* **BRL-DEPT-03:** Faculty Scoped User নিজের Faculty-র অধীনে নতুন Department তৈরি করতে পারবে কিনা তা permission দিয়ে নির্ধারিত (default: না, শুধু Admin)।

### Permissions

`department.view`, `department.create`, `department.update`, `department.delete`, `department.update-profile`

### Acceptance Criteria

* **AC-DEPT-01:** CSE Department user শুধু CSE-এর profile edit করতে পারবে, অন্য Department-এর edit page খোলা যাবে না (404, FR-GEN-52)।
* **AC-DEPT-02:** CSE-কে অন্য Faculty-তে স্থানান্তর করলে CSE-এর নিজস্ব menu না থাকলে নতুন Faculty-র menu দেখাবে।

---

## M-03 Program

**Purpose:** Department-এর অধীন academic program (যেমন BSc in CSE, MBA) পরিচালনা — বর্তমান DIU system-এর সবচেয়ে বিস্তারিত অংশ।

**Actors:** Admin, Faculty / Department Scoped User (অধীন Program), Program Scoped User (নিজের)

**Data (business level):**

| গ্রুপ | তথ্য |
|---|---|
| মূল তথ্য | পূর্ণ নাম, short name, subtitle, slug, Department, Program Level (Undergraduate / Graduate / Diploma / Post Graduate Diploma), Program tag (যেমন Major in Cyber Security, Dual Degree, Executive), degree নাম, image, video URL |
| Academic | মোট credit, সময়কাল (বছর ও semester সংখ্যা), শিক্ষার মাধ্যম, shift (Day / Evening / Weekend), class mode (On-campus / Online / Hybrid) |
| বিবরণ | summary, program objective, section intro, short ও AI summary (ঐচ্ছিক) |
| Statistics | approved alumni, major সংখ্যা, শিক্ষক সংখ্যা, industry expert সংখ্যা |
| Admission | পরবর্তী semester শুরুর তারিখ, eligibility (সাধারণ ও English-medium ছাত্রদের জন্য আলাদা), application link |
| Sub-items | Major / Specialization (category সহ), Career Path, Industry, Facilitator / Industry Expert, Learning Material, Professional Membership, Program Variant (Dual degree ইত্যাদি) |
| Display | homepage-এ দেখাবে কিনা, listing-এ দেখাবে কিনা, ক্রম |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-PROG-01 | Program CRUD; Department বাধ্যতামূলক; Faculty automatically Department থেকে | BR-CNT-01 | Must |
| FR-PROG-02 | Relation Target ও Microsite entity (parent: Department) | BR-CNT-02, BR-MS-01 | Must |
| FR-PROG-03 | **Program Level** একটি Taxonomy vocabulary; প্রাথমিক চারটি level: **Undergraduate, Graduate, Diploma, Post Graduate Diploma** (DIU-র confirmed তালিকা)। প্রতিটি Program-এর ঠিক একটি level। অন্য university-র জন্য admin নতুন level যোগ করতে পারবে | — | Must |
| FR-PROG-03a | **Program Tag** (vocabulary `program.tag`, বহু-নির্বাচন): level নয় এমন বর্ণনামূলক label — যেমন "Major in Cyber Security", "Dual Degree (DIU-BCU)", "Executive", "Honors Program", "OBE Curriculum"। Listing-এ filter হিসেবে ব্যবহারযোগ্য | — | Should |
| FR-PROG-04 | **Major / Specialization:** title, short description, category (যেমন Concentration, Track); repeatable list | BR-CNT-01 | Must |
| FR-PROG-05 | **Career Path ও Industry:** সাধারণ repeatable list (multi-language) | BR-CNT-01 | Must |
| FR-PROG-06 | **Facilitator / Industry Expert:** নাম, পদবি, প্রতিষ্ঠান, ধরন, ছবি, profile / LinkedIn link, email, phone, ক্রম | BR-CNT-01 | Should |
| FR-PROG-07 | **Learning Material:** title, link বা file (Media Library), ক্রম | BR-CNT-01 | Should |
| FR-PROG-08 | **Professional Membership / Accreditation:** title, description, logo | BR-CNT-01 | Should |
| FR-PROG-09 | **Program Variant:** একই program-এর ভিন্ন রূপ (যেমন Dual Degree, Evening) — title, বর্ণনা, related program link, action button | BR-CNT-01 | Should |
| FR-PROG-10 | **Eligibility:** সাধারণ ও English-medium (O/A Level) ছাত্রদের জন্য আলাদা rich text; ঐচ্ছিক structured field (ন্যূনতম GPA ইত্যাদি) | BR-CNT-01 | Must |
| FR-PROG-11 | Statistics: Program-এর নিজস্ব Stat Set (C-20) — Major ও Industry Expert সংখ্যা automatic; শিক্ষক সংখ্যা manual (শিক্ষক Department-এর সাথে যুক্ত, তাই Program-ভিত্তিক automatic গণনা সম্ভব নয়); approved alumni manual, অথবা Alumni module-এর Program relation থেকে automatic | BR-STAT-01 | Must |
| FR-PROG-12 | Program listing (`/programs`): Faculty, Department, Level, Shift, Mode দিয়ে filter; search; layout: Card, List, Table, Detailed | BR-THM-02 | Must |
| FR-PROG-13 | Program details / microsite (`/{faculty}/{department}/{slug}`, যেমন `/fsit/cse/bsc-in-cse` — FR-GEN-34): Overview, **Statistics**, Majors, Curriculum (CURR active থাকলে), Tuition (FEE active থাকলে), Eligibility, Career, Facilitators, FAQ, related News / Events / Notices, Apply button | BR-MS-07 | Must |
| FR-PROG-14 | Program-ভিত্তিক hero slider: Slider / Banner module (M-23)-এর slide এই Program-এর সাথে relation দিয়ে যুক্ত হবে (বর্তমান `program_sliders` আলাদা table নয়) | BR-CNT-02 | Should |
| FR-PROG-15 | Program comparison: ২–৩টি Program পাশাপাশি তুলনা (credit, সময়কাল, fee, shift) | — | Could |
| FR-PROG-16 | Homepage section: "Featured Programs", "Programs by Level" | FR-GEN-102 | Must |
| FR-PROG-17 | Program brochure / prospectus PDF (Media Library থেকে) download | — | Should |
| FR-PROG-18 | Optional module-এর data (Tuition, Curriculum, Calendar) Program page-এ tab বা section হিসেবে যোগ হবে extension point দিয়ে, Program module সরাসরি সেই module-কে চিনবে না | BR-MOD-05 | Must |

### Business Rules

* **BRL-PROG-01:** একই Department-এ Program-এর slug অনন্য; সম্পূর্ণ site-এও URL-এর জন্য slug অনন্য।
* **BRL-PROG-02:** Program-এর Department পরিবর্তন শুধু Admin করতে পারবে।
* **BRL-PROG-03:** Program inactive করলে তার Tuition, Curriculum ও Admission তথ্যও frontend-এ দেখাবে না।
* **BRL-PROG-04:** Sub-item (Major, Career Path ইত্যাদি) আলাদা table হলেও Program-এর ভেতরেই edit হবে (Filament Repeater / Relation Manager)।

### Permissions

`program.view`, `program.create`, `program.update`, `program.delete`

### Acceptance Criteria

* **AC-PROG-01:** Program listing-এ "Graduate" ও "Evening" filter দিলে শুধু মিলে যাওয়া program দেখাবে।
* **AC-PROG-02:** Tuition module deactivate করলে Program page থেকে Tuition tab লুকাবে, page ভাঙবে না।
* **AC-PROG-03:** BSc in CSE user শুধু ওই Program-এর তথ্য ও sub-item edit করতে পারবে।

---

## M-04 Course

**Purpose:** Program-এ পড়ানো course-এর তথ্য।

**Actors:** Admin, Scoped User (নিজের Program / Department-এর course)

**Data:** Course code, title, credit (theory / lab), course type (Core, Elective, GED, Lab, Thesis), description, objective, learning outcome (CLO), prerequisite course, owning Department, Program-এর সাথে সম্পর্ক।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-CRS-01 | Course CRUD; owning Department বাধ্যতামূলক | BR-CNT-01 | Must |
| FR-CRS-02 | একটি course একাধিক Program-এ ব্যবহৃত হতে পারবে | BR-CNT-01 | Must |
| FR-CRS-03 | Prerequisite: এক বা একাধিক course; circular prerequisite প্রতিরোধ | — | Should |
| FR-CRS-04 | Course Learning Outcome (CLO) repeatable list | — | Should |
| FR-CRS-05 | Course outline / syllabus file (Media Library) | — | Should |
| FR-CRS-06 | Relation Target (যেমন একটি Event কোনো course-এর সাথে যুক্ত) | BR-CNT-02 | Must |
| FR-CRS-07 | Course details page (`/course/{code}`) ও Program page থেকে link | — | Should |
| FR-CRS-08 | CSV / Excel থেকে bulk import (code, title, credit, type, Department) | — | Must |

### Business Rules

* **BRL-CRS-01:** Course code একটি Department-এ অনন্য।
* **BRL-CRS-02:** কোনো Curriculum-এ ব্যবহৃত course delete করার আগে সতর্কবার্তা।

### Permissions

`course.view`, `course.create`, `course.update`, `course.delete`, `course.import`

### Acceptance Criteria

* **AC-CRS-01:** ২০০টি course-এর একটি CSV import করলে valid row import হবে এবং invalid row-এর কারণসহ report দেখাবে।

---

## M-05 Academic Session / Semester

**Purpose:** Semester ও session (যেমন Spring 2027, Summer 2027, Fall 2027) পরিচালনা, যা অন্য module reference হিসেবে ব্যবহার করে।

**Actors:** Admin

**Data:** নাম, slug, ধরন (Spring / Summer / Fall বা Trimester / Semester / Year), বছর, শুরু ও শেষ তারিখ, admission শুরুর তারিখ, বিবরণ, "current" flag।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-SESS-01 | Session CRUD | BR-CNT-01 | Must |
| FR-SESS-02 | একটি "Current Session" ও একটি "Admission Session" নির্ধারণ | — | Must |
| FR-SESS-03 | Session ধরন configurable (Trimester / Bi-semester), কারণ ভিন্ন university ভিন্ন ব্যবস্থা ব্যবহার করে | BO-06 | Must |
| FR-SESS-04 | অন্য module (Calendar, Admission, Tuition) session select field হিসেবে ব্যবহার করতে পারবে | BR-MOD-05 | Must |
| FR-SESS-05 | Countdown: "Next semester starts on" — Program ও homepage-এ দেখানোর জন্য data | — | Should |

### Business Rules

* **BRL-SESS-01:** একসাথে একটিই Current ও একটিই Admission session।

### Permissions

`session.manage`

---

## M-06 Curriculum

**Purpose:** একটি Program-এর semester-ভিত্তিক course তালিকা (study plan)।

**Actors:** Admin, Scoped User (নিজের Program)

**Data:** Program, curriculum নাম / version (যেমন "OBE Curriculum 2024"), কার্যকর হওয়ার session, মোট credit, semester / year অনুযায়ী course তালিকা (Course reference, ধরন, credit), elective group, curriculum PDF।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-CURR-01 | একটি Program-এর একাধিক curriculum version; একটি "current" | BR-CNT-01 | Must |
| FR-CURR-02 | Semester / year অনুযায়ী course সাজানো (drag & drop), Course module থেকে select | BR-CNT-01 | Must |
| FR-CURR-03 | Elective group: "এই group থেকে যেকোনো ২টি" ধরনের নিয়ম | — | Should |
| FR-CURR-04 | মোট credit automatic হিসাব এবং Program-এর ঘোষিত credit-এর সাথে না মিললে সতর্কবার্তা | — | Should |
| FR-CURR-05 | Program page-এ "Curriculum" tab: semester অনুযায়ী table, course-এ click করলে details | BR-MS-07 | Must |
| FR-CURR-06 | Curriculum PDF download (upload করা অথবা automatically তৈরি) | — | Should |
| FR-CURR-07 | একটি curriculum copy করে নতুন version শুরু | — | Should |

### Permissions

`curriculum.view`, `curriculum.manage`

### Acceptance Criteria

* **AC-CURR-01:** BSc in CSE-এর current curriculum Program page-এ ৮টি semester-এ ভাগ হয়ে দেখাবে, মোট credit সহ।

---

## M-07 Faculty Member / Teacher

**Purpose:** শিক্ষকদের profile directory।

**Actors:** Admin, Scoped User (নিজের Department-এর শিক্ষক), শিক্ষক নিজে (ভবিষ্যতে; এই phase-এ নয়, Out of Scope 4.2)

**Data:** নাম, পদবি, Department (primary ও অতিরিক্ত), employee ID, ছবি, email, phone, extension, অফিস রুম, শিক্ষাগত যোগ্যতা, research interest, biography, publication link, Google Scholar / ORCID / ResearchGate / LinkedIn, প্রশাসনিক দায়িত্ব (যেমন Head, Coordinator), শিক্ষকতার ধরন (Full-time / Part-time / Adjunct / On leave), ক্রম।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-FM-01 | Faculty Member CRUD; primary Department বাধ্যতামূলক; একাধিক Department-এ যুক্ত করা যাবে | BR-CNT-01 | Must |
| FR-FM-02 | Profile section: education, experience, research interest, award, membership (repeatable) | BR-CNT-01 | Must |
| FR-FM-03 | Directory page (`/faculty-members`): Faculty, Department, পদবি দিয়ে filter, নাম দিয়ে search; layout: Grid, List, Profile Card | BR-THM-02 | Must |
| FR-FM-04 | Profile page (`/faculty-member/{slug}`) এবং সংশ্লিষ্ট Publication ও Research (M-25, M-26 active থাকলে) | BR-CNT-01 | Must |
| FR-FM-05 | Department microsite-এ শিক্ষকদের তালিকা, পদবির ক্রম অনুযায়ী (পদবির ক্রম configurable: Professor → Associate Professor → ...) | BR-MS-07 | Must |
| FR-FM-06 | পদবি (Designation) একটি Taxonomy vocabulary, ক্রম সহ | — | Must |
| FR-FM-07 | CSV / Excel bulk import | — | Must |
| FR-FM-08 | External faculty profile system থেকে API-র মাধ্যমে sync (যেমন DIU-র আলাদা faculty portal থাকলে); sync করা field admin-এ read-only | — | Could |
| FR-FM-09 | Email address frontend-এ spam bot থেকে সুরক্ষিত করে দেখানো | NFR-SEC | Should |
| FR-FM-10 | **Person Source** হিসেবে register (FR-REL-09), যাতে Dean, Head, Coordinator, Committee member, Event speaker, Publication author ইত্যাদিতে শিক্ষককে assign করা যায় | Q-08 | Must |

### Business Rules

* **BRL-FM-01:** Employee ID অনন্য (দেওয়া হলে)।
* **BRL-FM-02:** "On leave" শিক্ষক directory-তে চিহ্নসহ দেখাবে; inactive শিক্ষক দেখাবে না।

### Permissions

`faculty-member.view`, `faculty-member.create`, `faculty-member.update`, `faculty-member.delete`, `faculty-member.import`

### Acceptance Criteria

* **AC-FM-01:** CSE Department microsite-এ শুধু CSE-এর active শিক্ষক পদবির ক্রম অনুযায়ী দেখাবে।

---

## M-08 Campus / Location

**Purpose:** বিশ্ববিদ্যালয়ের campus ও স্থাপনার তথ্য (যেমন Daffodil Smart City, Dhanmondi campus)।

**Actors:** Admin

**Data:** নাম, slug, ঠিকানা, map coordinate / embed, ছবি, বিবরণ, contact, building তালিকা (নাম, code, floor)।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-CAMP-01 | Campus CRUD, building / block repeatable list | BR-CNT-01 | Must |
| FR-CAMP-02 | Relation Target (যেমন Event কোনো campus-এ, Office কোনো campus-এ) | BR-CNT-02 | Must |
| FR-CAMP-03 | Campus page: map, ছবি, বিবরণ, সংশ্লিষ্ট Facilities (M-33 active থাকলে) ও Transport route (M-31 active থাকলে) | — | Should |
| FR-CAMP-04 | অন্য module-এর "Campus" field এই module থেকে আসবে (বর্তমান `admins.campus` text field-এর বদলে) | — | Must |

### Permissions

`campus.manage`

---

## M-09 Admission Info

**Purpose:** Admission সংক্রান্ত তথ্য প্রদর্শন ও admission test result lookup। Online application ও payment এই phase-এ নয় (BRD 4.2)।

**Actors:** Admin, Admission Office role, Visitor

**Data:**

| অংশ | তথ্য |
|---|---|
| Admission Circular / Notice | title, Admission Session, application শুরু ও শেষ তারিখ, admission test তারিখ, ভর্তির তারিখ, বিস্তারিত, সংশ্লিষ্ট Program, PDF |
| Admission Facility / Highlight | title, ছবি, link, ক্রম, category (বর্তমান `admissions`, `admission_facility_categories`) |
| Admission Test Result | Program, application serial, ছাত্রের নাম, ফলাফল (Selected / Not selected / Waiting / Absent) |
| Result Message | প্রতিটি ফলাফলের জন্য প্রদর্শিত বার্তা (বর্তমান `admission_tests`) |
| Admission Contact | contact type অনুযায়ী (যেমন Undergraduate, International), নাম, পদবি, phone, email, ছবি — Administration থেকে link বা manual |
| Admission Subscriber | email / WhatsApp, verified, source |
| Admission Step | কীভাবে আবেদন করবেন — ধাপ (title, বিবরণ, icon) |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-ADM-01 | Admission landing page (`/admission`): চলমান circular, গুরুত্বপূর্ণ তারিখ, facility highlight, ধাপ, contact, Program finder, Apply link | Q-16 | Must |
| FR-ADM-02 | Admission circular CRUD; গুরুত্বপূর্ণ তারিখ structured field হিসেবে, যাতে countdown ও calendar-এ দেখানো যায় | Q-16 | Must |
| FR-ADM-03 | Application শেষ তারিখ পার হলে circular-এ "Closed" চিহ্ন automatically | Q-16 | Should |
| FR-ADM-04 | External application portal URL (settings) — "Apply Now" button সেখানে নিয়ে যাবে | Q-16 | Must |
| FR-ADM-05 | **Result upload:** Excel / CSV import (Program, serial, নাম, ফলাফল), session অনুযায়ী; import preview ও error report | Q-16 | Must |
| FR-ADM-06 | **Result lookup:** visitor application serial (ও Program) দিয়ে নিজের ফলাফল দেখবে; ফলাফল অনুযায়ী configured বার্তা; "Not found" বার্তা | Q-16 | Must |
| FR-ADM-07 | Result lookup-এ rate limit ও captcha (bulk scraping প্রতিরোধ) | NFR-SEC-06 | Must |
| FR-ADM-08 | Result publish / unpublish switch (session অনুযায়ী) | Q-16 | Must |
| FR-ADM-09 | Admission subscriber form (email / WhatsApp), email verification, unsubscribe; Newsletter module active থাকলে সেখানে একটি list হিসেবে sync | Q-16 | Should |
| FR-ADM-10 | Admission contact তালিকা, type অনুযায়ী grouped | Q-16 | Must |
| FR-ADM-11 | Homepage section: "Admission Going On" banner / countdown, "Important Dates" | FR-GEN-102 | Must |
| FR-ADM-12 | Subscriber ও result export (Admin) | — | Should |

### Business Rules

* **BRL-ADM-01:** Admission Result একটি bulk data table; প্রতি session-এ হাজার হাজার row। Result-এর জন্য soft delete থাকবে (BR-DATA-01), কিন্তু পুরনো session-এর result একসাথে archive / purge করার ব্যবস্থা থাকবে।
* **BRL-ADM-02:** Result lookup কোনো ব্যক্তিগত তথ্য (phone, email) দেখাবে না; শুধু নাম ও ফলাফল।

### Permissions

`admission.view`, `admission.manage`, `admission.result-import`, `admission.subscribers`

### Acceptance Criteria

* **AC-ADM-01:** ১৭০০ row-এর result file import করে publish করলে visitor serial দিয়ে ২ সেকেন্ডের মধ্যে ফলাফল পাবে।
* **AC-ADM-02:** Result unpublish থাকলে lookup "Result not yet published" দেখাবে।

---

## M-10 Tuition, Waiver & Scholarship

**Purpose:** Program-ভিত্তিক tuition fee, নিয়মভিত্তিক waiver, scholarship তথ্য, এবং visitor-দের জন্য **Waiver Calculator**। *(v0.2: তিনটি আলাদা module — Tuition `tuition`, Waiver Calculator `waiver`, Scholarship `scholarship`; প্রতিটি আলাদা চালু / বন্ধ করা যায়)*

**Actors:** Admin, **Admission Office** (tuition fee), **Waiver Office** (waiver rule, scholarship), Faculty / Department / Program user (নিজের scope-এর program-এর tuition শুধু দেখা), Visitor *(v1.1, 2026-10-10)*

**Data:**

| অংশ | তথ্য |
|---|---|
| Fee Category | যেমন Local, International (বর্তমান `tuition_categories`) |
| Tuition Fee | Program, Fee Category, প্রযোজ্য Session, credit hour (সর্বনিম্ন ও সর্বোচ্চ), semester সংখ্যা ও সময়কাল, admission fee, বছর অনুযায়ী fee (১ম–৪র্থ বছর), per semester fee, মোট tuition (সর্বনিম্ন ও সর্বোচ্চ), অন্যান্য fee, মোট খরচ, prospectus link |
| Waiver Rule | নাম, Program Level, Fee Category, শর্ত (SSC GPA পরিসর, HSC GPA পরিসর, golden A+, Undergraduate CGPA পরিসর (Graduate-এর জন্য), International), প্রযোজ্য Board, Gender, Faculty, Tuition, Waiver category, waiver হার ও সর্বোচ্চ হার, বার্তা |
| Waiver Category | যেমন Merit, Freedom Fighter, Sibling, Female (Program Level অনুযায়ী) |
| Education Board | যেমন Dhaka, Rajshahi, Madrasah, Technical (Program Level অনুযায়ী) |
| Scholarship | title, সংক্ষেপ, বিবরণ, যোগ্যতা, আবেদন প্রক্রিয়া, আবেদনের link, শেষ সময়, ছবি ও file; একটি scholarship-এর অধীনে তার অংশ (যেমন Erasmus → প্রতিটি exchange call) *(v0.2)* |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-FEE-01 | Fee Category CRUD | Q-16 | Must |
| FR-FEE-02 | Tuition Fee CRUD, Program ও Fee Category অনুযায়ী; মোট অঙ্ক automatically হিসাব (manual override সম্ভব) | Q-16 | Must |
| FR-FEE-03 | Tuition fee table page (`/tuition-fees`): Faculty, Department, Level, Fee Category দিয়ে filter; print-friendly | Q-16 | Must |
| FR-FEE-04 | Program page-এ Tuition tab (extension point, FR-PROG-18) | Q-16 | Must |
| FR-FEE-05 | Waiver Rule CRUD (উপরের সব শর্ত), Board, Gender, Waiver Category master data CRUD | Q-16 | Must |
| FR-FEE-06 | **Waiver Calculator** (`/waiver-calculator`): visitor Program Level, Program, Fee Category, Board, Gender, SSC ও HSC GPA (অথবা Undergraduate CGPA), golden A+, প্রযোজ্য waiver category দেয় → system মিলে যাওয়া rule থেকে waiver হার, বার্তা, এবং waiver-এর পর আনুমানিক মোট খরচ দেখায় | Q-16 | Must |
| FR-FEE-07 | একাধিক rule মিললে নিয়ম: সর্বোচ্চ হার প্রযোজ্য, তবে rule-এর "সর্বোচ্চ হার" সীমা অতিক্রম করবে না (নিয়ম Admin settings-এ configurable: highest / additive with cap) | Q-16 | Must |
| FR-FEE-08 | Calculator-এর ফলাফলে disclaimer (settings থেকে) এবং ফলাফল print / PDF | — | Should |
| FR-FEE-09 | Rule-এর মধ্যে conflict বা ফাঁক থাকলে admin-কে সতর্ক করার "Rule test" tool: নমুনা input দিয়ে কোন rule মিলছে দেখা | — | Should |
| FR-FEE-10 | Scholarship CRUD (module `scholarship`); একটি scholarship-এর অধীনে অংশ (এক স্তর — "hierarchical category"); শেষ সময় পেরোলে "Closed"; ঐচ্ছিক category (Site › Categories & Tags, sub-category সহ) দিয়ে ভাগ; page `/scholarships` (মূল scholarship-গুলো, category অনুযায়ী ভাগ, `?category=` filter) ও `/scholarships/{slug}` (বিস্তারিত, অধীন অংশের তালিকা) *(v0.2)* | Q-16 | Must |
| FR-FEE-11 | Tuition ও Waiver Rule Excel import / export | — | Should |
| FR-FEE-12 | Fee তথ্য Session-ভিত্তিক version রাখা: নতুন session-এর fee আলাদা, পুরনো session archive | — | Should |

### Business Rules

* **BRL-FEE-01:** Waiver Rule-এর GPA পরিসরে min ≤ max; HSC / SSC GPA ০–৫, CGPA ০–৪ এর মধ্যে।
* **BRL-FEE-02:** Calculator শুধু active rule ও active Program ব্যবহার করবে।
* **BRL-FEE-03:** Calculator-এ visitor-এর কোনো input database-এ ব্যক্তিগতভাবে সংরক্ষিত হবে না (শুধু aggregated usage count, ঐচ্ছিক)।
* **BRL-FEE-04:** Waiver-এর pivot table (rule ↔ board / gender / faculty / tuition / category) pivot নিয়ম মানবে (Q-06)।

### Permissions

`tuition.view`, `tuition.manage`, `waiver.manage`, `scholarship.manage`

*(v1.1)* বাস্তবায়নে resource অনুযায়ী আলাদা permission (Shield: `ViewAny / View / Create / Update / Delete …:{Model}`) — Tuition, FeeCategory, WaiverRule, WaiverCategory, Board, Scholarship; এবং calculator settings-এর জন্য `Manage:WaiverSettings`। Default role (FR-ROLE-04): **Admission Office** = Tuition + FeeCategory; **Waiver Office** = WaiverRule + WaiverCategory + Board + WaiverSettings + Scholarship (Scholarship module install-এর সময় এই role পায়; অন্য role-কেও দেওয়া যায়)। এই দুই office-এর user-এর scope থাকে না, তাই সব program দেখেন (BRL-ROLE-03)। Faculty / Department / Program user-কে tuition-এর শুধু View দিলে নিজের scope-এর program-এর tuition দেখেন — Program Hub-এর "Tuition Fees" অংশে, read-only (FR-GEN-86)।

### Acceptance Criteria

* **AC-FEE-01:** SSC 5.00 ও HSC 5.00 (golden নয়), Dhaka Board, Undergraduate, Local দিলে Calculator সঠিক rule-এর waiver হার ও waiver-পরবর্তী মোট খরচ দেখাবে।
* **AC-FEE-02:** কোনো rule না মিললে "No waiver applicable" বার্তা দেখাবে।
* **AC-FEE-03:** একটি tuition-এর credit বা per-credit fee পরিবর্তন করলে মোট automatically আবার হিসাব হবে।

---

## M-11 Academic Calendar

**Purpose:** Session অনুযায়ী academic calendar প্রকাশ।

**Actors:** Admin, Registrar / Exam Office role, Visitor

**Data:**

| অংশ | তথ্য |
|---|---|
| Calendar | title, slug, Session / বছর, সময়কাল, প্রযোজ্য audience group (যেমন Undergraduate, Graduate, Diploma — বর্তমান `calender_programs`), printable PDF (hard copy), homepage-এ দেখাবে কিনা, view count |
| Calendar Event | calendar, তারিখ বা তারিখের পরিসর (structured), তারিখের লিখিত ব্যাখ্যা (যেমন "Week 1"), title, ধরন (Class, Exam, Holiday, Registration, Result ইত্যাদি), audience group, ক্রম |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-CAL-01 | Calendar CRUD; একাধিক audience group | Q-16 | Must |
| FR-CAL-02 | Calendar Event CRUD, structured শুরু ও শেষ তারিখ এবং ঐচ্ছিক লিখিত ব্যাখ্যা | Q-16 | Must |
| FR-CAL-03 | Event ধরন একটি Taxonomy vocabulary (রং সহ) | Q-16 | Should |
| FR-CAL-04 | Calendar page (`/academic-calendar`): Session ও audience group দিয়ে filter; layout: Table, Month view, Timeline | BR-THM-02 | Must |
| FR-CAL-05 | PDF download (upload করা hard copy) | Q-16 | Must |
| FR-CAL-06 | iCal (`.ics`) export, যাতে Google Calendar-এ যোগ করা যায় | — | Could |
| FR-CAL-07 | Homepage section: "Upcoming Academic Dates" (পরবর্তী ৫টি event) | FR-GEN-102 | Should |
| FR-CAL-08 | Excel import (তারিখ, title, ধরন, audience) | — | Should |
| FR-CAL-09 | Program page-এ সংশ্লিষ্ট audience group-এর calendar link (extension point) | FR-PROG-18 | Should |

### Permissions

`calendar.view`, `calendar.manage`

### Acceptance Criteria

* **AC-CAL-01:** "Graduate" filter দিলে শুধু Graduate audience-এর event দেখাবে।
* **AC-CAL-02:** `.ics` file Google Calendar-এ import করলে event সঠিক তারিখে দেখাবে।

---

## বর্তমান System থেকে পরিবর্তন (Migration Note)

Database Design phase-এ বিস্তারিত mapping হবে; এখানে উল্লেখযোগ্য structural পরিবর্তন:

| বর্তমান (`project_diu`) | নতুন system |
|---|---|
| `program_types` (Level ও sub-type একসাথে, parent দিয়ে) | Level (Undergraduate / Graduate / Diploma / Post Graduate Diploma) → `program.level`; বাকি sub-type → `program.tag` (DB-03 Section 15.1) |
| `department_program_type` | Program (M-03) |
| `institutional_programs`, `mba_programs` | Program (উপযুক্ত level: Undergraduate / Graduate / Diploma / Post Graduate Diploma) অথবা বিদ্যমান Program-এর সাথে একত্র |
| `program_sliders` | Slider / Banner (M-23), Program-এর সাথে relation |
| `program_videos` | Gallery (M-22) video, Program-এর সাথে relation |
| `facilitators`, `program_facilitators` | Program Facilitator (FR-PROG-06) |
| `eligibilities` | Program Eligibility (FR-PROG-10) |
| `greetings` (Program / Department / Faculty-ভিত্তিক) | Announcement Widgets (M-30), relation সহ |
| `admins.campus` (text) | Campus (M-08) reference |
| `faculty_menus`, `department_menus`, `program_menus` | একক Menu Management (C-08) |
| `department_user` | Core Scope (C-03) |

---

## Traceability Summary (BRD → SRS Part 2)

| BRD Requirement | SRS Requirement |
|---|---|
| BR-CNT-01 (Academic entity CRUD) | FR-FAC, FR-DEPT, FR-PROG, FR-CRS, FR-SESS, FR-CURR, FR-FM, FR-CAMP |
| BR-CNT-02/03 (Relation) | FR-FAC-02, FR-DEPT-02, FR-PROG-02, FR-CRS-06, FR-CAMP-02 |
| BR-MS-01/07 (Microsite) | FR-FAC-05/06, FR-DEPT-06/07, FR-PROG-13 |
| BR-THM-02 (Layout) | FR-FAC-07, FR-DEPT-09, FR-PROG-12, FR-FM-03, FR-CAL-04 |
| Q-12 (Scope নিচের দিকে) | Actors / Permission প্রতিটি module-এ |
| Q-16 (Admission, Tuition / Waiver / Scholarship, Calendar) | FR-ADM, FR-FEE, FR-CAL |
