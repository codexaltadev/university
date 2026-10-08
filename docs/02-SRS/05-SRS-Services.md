# Software Requirements Specification (SRS)
## Part 5 — Campus & Services, Reputation & Partnership, Communication (M-31 থেকে M-39)

| বিষয় | তথ্য |
|---|---|
| Document | SRS Part 5: Services, Reputation ও Communication Modules |
| Version | 0.1 (Draft) |
| Date | 2026-10-08 |
| Status | Review-এর জন্য অপেক্ষমাণ |
| Based on | [BRD v1.3](../01-BRD.md), [SRS Part 0](00-SRS-Overview.md), [Part 1](01-SRS-Core.md), `project_diu` analysis |

> Part 0-এর Common Requirement (FR-GEN) এবং Part 4-এর Attachable Common Requirement (FR-CNT-C, Context Highlight) যেখানে প্রযোজ্য সেখানে প্রযোজ্য।

## সূচি

| # | Module | Code | Relation Target | Microsite | Attachable |
|---|---|---|---|---|---|
| M-31 | [Transport](#m-31-transport) | TRN | — | — | — |
| M-32 | [Lab / Research Center](#m-32-lab--research-center) | LAB | ✔ | ✔ | — |
| M-33 | [Facilities](#m-33-facilities) | FCL | — | — | ✔ |
| M-34 | [Accreditation & Ranking](#m-34-accreditation--ranking) | ACR | — | — | ✔ |
| M-35 | [International Linkage & Conference](#m-35-international-linkage--conference) | INTL | — | — | ✔ |
| M-36 | [CSR](#m-36-csr) | CSR | — | — | ✔ |
| M-37 | [Partners](#m-37-partners) | PRT | — | — | ✔ |
| M-38 | [Contact / Inquiry](#m-38-contact--inquiry) | INQ | — | — | — |
| M-39 | [Newsletter / Subscription](#m-39-newsletter--subscription) | NLT | — | — | — |

---

## M-31 Transport

**Purpose:** বিশ্ববিদ্যালয়ের bus route, stop ও সময়সূচি প্রকাশ, এবং ছাত্রদের জন্য "আমার stop থেকে কোন bus" খোঁজা।

**Actors:** Admin, Transport Office role, Visitor

**Data:**

| অংশ | তথ্য |
|---|---|
| Service Type | যেমন Regular, Friday / Weekend, Exam-time, Special (একটি default) — বর্তমান `transport_services` |
| Stop | নাম (multi-language), latitude / longitude, landmark, is_active |
| Route | নাম, নম্বর / code, tag, শুরু ও শেষ stop, ক্রমযুক্ত stop তালিকা, map URL / embed, বার্তা, Campus |
| Schedule | Route + Service Type, দিন (সপ্তাহের কোন দিন), direction (Campus-এ যাওয়া / Campus থেকে ফেরা), ছাড়ার সময়, প্রতিটি stop-এ আনুমানিক সময় (ঐচ্ছিক) |
| Transport Notice ও Policy | session অনুযায়ী নিয়ম ও policy file (বর্তমান `transport_settings`) |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-TRN-01 | Stop CRUD, map-এ pin দিয়ে latitude / longitude নির্বাচন | Q-16 | Must |
| FR-TRN-02 | Route CRUD; stop drag & drop করে ক্রম; শুরু ও শেষ stop automatically ক্রম থেকে | Q-16 | Must |
| FR-TRN-03 | Service Type CRUD, একটি default | Q-16 | Must |
| FR-TRN-04 | Schedule CRUD: Route, Service Type, দিন, direction, সময়; একসাথে একাধিক সময় যোগ (bulk) | Q-16 | Must |
| FR-TRN-05 | Transport page (`/transport`): Service Type ও দিন নির্বাচন (default: আজকের দিন ও default service) → route তালিকা, প্রতিটির stop ও সময় | Q-16 | Must |
| FR-TRN-06 | **Route finder:** visitor একটি stop নির্বাচন বা search করলে সেই stop দিয়ে যাওয়া route ও সময় দেখাবে | Q-16 | Must |
| FR-TRN-07 | Route-এর map: stop গুলো map-এ চিহ্নিত (OpenStreetMap / Google Maps) | — | Should |
| FR-TRN-08 | Transport policy / rules file ও notice (Notice module-এর Transport category বা নিজস্ব) | — | Should |
| FR-TRN-09 | বিশেষ দিনে service পরিবর্তনের বার্তা (route ও service-ভিত্তিক) | — | Should |
| FR-TRN-10 | Driver / helper assign (Administration, Person Picker; ঐচ্ছিক, public-এ দেখানো ঐচ্ছিক) | FR-ADMN-10 | Could |
| FR-TRN-11 | Schedule Excel import / export ও print-friendly schedule | — | Should |

### Business Rules

* **BRL-TRN-01:** একটি Route-এ ন্যূনতম ২টি stop।
* **BRL-TRN-02:** Route, Stop ও Schedule-এর মধ্যকার সংযোগ table pivot নিয়ম মানবে (Q-06); Route, Stop, Schedule নিজেরা business table।

### Permissions

`transport.view`, `transport.manage`

### Acceptance Criteria

* **AC-TRN-01:** Visitor "Mirpur-10" stop search করলে সেই stop-এর সব route, আজকের default service-এর Campus-গামী ও Campus-ফেরত সময় সহ দেখাবে।
* **AC-TRN-02:** Friday service select করলে শুধু Friday-র route ও সময় দেখাবে।

---

## M-32 Lab / Research Center

**Purpose:** Lab ও Research Center-এর তথ্য এবং প্রতিটির নিজস্ব microsite (বর্তমানে ১৫টি `lab_*` table)। Access Office-এর মতো permission-ভিত্তিক (BRD 5.4)।

**Actors:** Admin, Lab role (যে Lab-এর permission আছে), Department Scoped User (নিজের Department-এর Lab — read / content), Visitor

**Data:**

| অংশ | তথ্য |
|---|---|
| Lab | নাম, short name, slug, ধরন (Teaching Lab / Research Lab / Research Center / Innovation Hub — Taxonomy), parent Department (ঐচ্ছিক), অবস্থান (Campus, building, room), সংক্ষিপ্ত বিবরণ, বিস্তারিত, প্রধান যন্ত্রপাতির সারাংশ, দায়িত্বপ্রাপ্ত ব্যক্তি (Person Picker বা manual), promotional video, external website link, লোগো, cover |
| Equipment / Tool | title, category, ছবি, বিবরণ, পরিমাণ (ঐচ্ছিক), ক্রম |
| Scope / Service | title, category, ছবি, বিবরণ — Lab যে সেবা বা গবেষণা-ক্ষেত্র দেয় |
| Student Project | title, slug, বিবরণ, ছবি, student নাম ও ID, Department, category, ক্রম |
| Product / Innovation | title, slug, বিবরণ, category, ছবি |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-LAB-01 | Lab CRUD; ধরন Taxonomy; parent Department (Academic module active থাকলে) | Q-16 | Must |
| FR-LAB-02 | Relation Target ও Microsite entity; fallback: নিজের → Main site (BR-MS-04) | BR-MS-01 | Must |
| FR-LAB-03 | Lab তৈরি হলে dynamic permission: `lab.{id}.manage`, `lab.{id}.content`, `lab.{id}.menu` (FR-ROLE-07) | BRD 5.4 | Must |
| FR-LAB-04 | Equipment / Tool, Scope / Service, Student Project, Product — Lab-এর ভেতরে sub-item হিসেবে CRUD, প্রতিটির category (Taxonomy, সব Lab-এ shared) | Q-16 | Must |
| FR-LAB-05 | Lab listing (`/labs`): ধরন, Department, Campus দিয়ে filter; layout: Grid, List, Card | BR-THM-02 | Must |
| FR-LAB-06 | Lab microsite (`/lab/{slug}`) default section: Hero (Slider), About, Statistics, Scope, Equipment, Projects, Products, News, Events, Gallery, Committee, Partners, Contact (Homepage Builder দিয়ে পরিবর্তনযোগ্য) | BR-MS-07 | Must |
| FR-LAB-07 | Lab-এর news, event, gallery, slider, committee, research, partner — সংশ্লিষ্ট module-এ Relation দিয়ে (বর্তমান `lab_news`, `lab_galleries`, `lab_committees`, `lab_research` আলাদা table নয়) | BR-CNT-02 | Must |
| FR-LAB-08 | Lab-এর নিজস্ব menu ও icon-সহ quick link (বর্তমান `lab_menus`, `lab_app_menus`) — Core Menu-র মাধ্যমে, "Quick Links (icon)" menu location দিয়ে | BR-MS-10 | Must |
| FR-LAB-09 | Feature block (বর্তমান `lab_mid_sections`: icon, title, বিবরণ, রং) ও section intro text (বর্তমান `lab_section_summaries`) — Homepage Builder section হিসেবে | FR-HOME-02 | Must |
| FR-LAB-10 | Student Project ও Product-এর details page | — | Should |
| FR-LAB-11 | Statistics: Lab-এর নিজস্ব Stat Set (C-20) — equipment, project, product সংখ্যা automatic; অন্যান্য manual | BR-STAT-01 | Must |
| FR-LAB-12 | Equipment booking / reservation | — | Won't (এই release-এ নয়) |

### Business Rules

* **BRL-LAB-01:** Lab role-এর user শুধু নিজের Lab-এর তথ্য, sub-item, page, menu ও related content পরিচালনা করবে (BR-SEC-05)।
* **BRL-LAB-02:** Department Scoped User তার Department-এর অধীন Lab-এর content দেখতে পারবে; Lab-এর profile edit করতে Lab permission লাগবে।

### Permissions

Static: `lab.view`, `lab.create`, `lab.delete`, `lab.manage-all`
Dynamic: `lab.{id}.manage`, `lab.{id}.content`, `lab.{id}.menu`

### Acceptance Criteria

* **AC-LAB-01:** "Robotics Lab" তৈরি করলে `/lab/robotics-lab` microsite ও তিনটি dynamic permission তৈরি হবে।
* **AC-LAB-02:** News তৈরি করে Related To-তে Robotics Lab দিলে Lab microsite-এর News section-এ দেখাবে।

---

## M-33 Facilities

**Purpose:** Campus ও entity-র সুবিধা (Library, Hostel, Medical, Sports, Cafeteria, Wi-Fi, Lab ইত্যাদি) প্রদর্শন।

**Actors:** Admin, Scoped / Office / Lab user (নিজের entity-র facility)

**Data:** title, slug, icon, ছবি / gallery, সংক্ষিপ্ত ও বিস্তারিত বিবরণ, category (Taxonomy), Campus, link (internal / external), related entity, ক্রম।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-FCL-01 | Facility CRUD; Related To (বর্তমান polymorphic `facilityable` ও `department_id`-এর বদলে) | BR-CNT-02 | Must |
| FR-FCL-02 | Facilities page (`/facilities`): category ও Campus অনুযায়ী; layout: Icon grid, Card, List | BR-THM-02 | Must |
| FR-FCL-03 | Details page (বিস্তারিত থাকলে), নইলে link-এ যাবে | — | Should |
| FR-FCL-04 | Homepage section: "Campus Facilities" (Context Highlight অনুযায়ী) | FR-GEN-102 | Must |
| FR-FCL-05 | Campus page ও entity microsite-এ সংশ্লিষ্ট facility | FR-CAMP-03 | Should |

### Permissions

`facilities.manage` (scope অনুযায়ী)

---

## M-34 Accreditation & Ranking

**Purpose:** বিশ্ববিদ্যালয় ও Program-এর accreditation, professional recognition এবং national / international ranking।

**Actors:** Admin, IQAC / Office role

**Data:**

| অংশ | তথ্য |
|---|---|
| Accreditation | title, slug, প্রদানকারী সংস্থা, logo, বিবরণ, link, category (Taxonomy), প্রদানের তারিখ ও মেয়াদ, প্রযোজ্য: বিশ্ববিদ্যালয় / Faculty / Department / Program (Related To) |
| Ranking | ranking সংস্থা (যেমন QS, THE, Webometrics), বছর, অবস্থান / band, ক্ষেত্র (Overall / Subject / Region), logo, সংক্ষিপ্ত ও বিস্তারিত বিবরণ, URL, certificate / badge image |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-ACR-01 | Accreditation CRUD; Related To দিয়ে Program / Department / Faculty-তে (বর্তমান `department_program_type_id`, `is_faculty`, `is_department`-এর বদলে) | Q-16 | Must |
| FR-ACR-02 | মেয়াদ শেষের তারিখ থাকলে মেয়াদোত্তীর্ণ accreditation frontend থেকে লুকাবে বা "Expired" দেখাবে (setting); মেয়াদ শেষের ৬০ দিন আগে admin-কে notification | — | Should |
| FR-ACR-03 | Ranking CRUD; একই সংস্থার বছর অনুযায়ী ইতিহাস | Q-16 | Must |
| FR-ACR-04 | Ranking page (`/rankings`): সর্বশেষ বছরের অবস্থান আগে, সংস্থা অনুযায়ী বছরভিত্তিক trend (table / chart) | — | Should |
| FR-ACR-05 | Accreditation page (`/accreditations`): category অনুযায়ী; Program page-এ সংশ্লিষ্ট accreditation badge | BR-MS-07 | Must |
| FR-ACR-06 | Homepage section: "Rankings & Recognition" (logo strip / card) | FR-GEN-102 | Must |

### Permissions

`accreditation.manage`, `ranking.manage`

---

## M-35 International Linkage & Conference

**Purpose:** আন্তর্জাতিক partner university (MoU), আন্তর্জাতিক conference এবং বিদেশি অতিথি / delegation।

**Actors:** Admin, International Affairs Office role

**Data:**

| অংশ | তথ্য |
|---|---|
| Partner Institution | নাম, দেশ, website, logo, চুক্তির ধরন (MoU / MoA / Exchange / Dual Degree / Research), স্বাক্ষরের তারিখ ও মেয়াদ, বিবরণ, সংশ্লিষ্ট Program / Department (ঐচ্ছিক) |
| Country | নাম, ISO code, flag (master data) |
| Conference | title, slug, ছবি, বিস্তারিত, category, তারিখ, স্থান, website, organizer entity |
| International Visitor / Delegation | নাম, পদবি, প্রতিষ্ঠান, দেশ, তারিখ, বিবরণ, ছবি — বর্তমান `visitors` |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-INTL-01 | Partner Institution CRUD (বর্তমানে ৭০০+), Country master data | Q-16 | Must |
| FR-INTL-02 | Linkage page (`/international/partners`): দেশ, চুক্তির ধরন দিয়ে filter; search; layout: Logo grid, Table, World map (দেশভিত্তিক সংখ্যা) | BR-THM-02 | Must |
| FR-INTL-03 | মেয়াদোত্তীর্ণ চুক্তি চিহ্নিত / লুকানো (setting) ও মেয়াদ শেষের আগে notification | — | Should |
| FR-INTL-04 | Featured partner: Context Highlight দিয়ে (বর্তমান `featured_partners` আলাদা table নয়) | BR-CNT-07 | Must |
| FR-INTL-05 | Conference CRUD ও page (`/international/conferences`); Events module active থাকলে conference-কে event হিসেবেও দেখানোর option | Q-16 | Must |
| FR-INTL-06 | International Visitor / Delegation CRUD ও page; Gallery album link | — | Should |
| FR-INTL-07 | Excel import (partner list) | — | Must |
| FR-INTL-08 | Statistics metric: partner institution সংখ্যা, দেশ সংখ্যা (C-20) | BR-STAT-02 | Should |
| FR-INTL-09 | Homepage section: "Global Partners" (logo carousel), "Countries" counter | FR-GEN-102 | Must |

### Permissions

`international.manage`

### Acceptance Criteria

* **AC-INTL-01:** "Malaysia" ও "Dual Degree" filter দিলে শুধু মিলে যাওয়া partner দেখাবে।

---

## M-36 CSR

**Purpose:** বিশ্ববিদ্যালয়ের Corporate Social Responsibility কার্যক্রম।

**Actors:** Admin, CSR / Office role

**Data:** title, slug, বিবরণ, ছবি (একাধিক, Media Library), video link, external URL, তারিখ, category (hierarchical, category-র ছবি ও সংক্ষিপ্ত বিবরণ সহ), related entity।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-CSR-01 | CSR activity CRUD; hierarchical category CRUD (ছবি ও বিবরণ সহ) | Q-16 | Must |
| FR-CSR-02 | CSR page (`/csr`): category card → category page → activity; layout: Card, Grid, Timeline | BR-THM-02 | Must |
| FR-CSR-03 | Highlight (বর্তমান `is_home`, `is_featured`) — Context Highlight দিয়ে | BR-CNT-07 | Must |
| FR-CSR-04 | Homepage section: "Social Responsibility" | FR-GEN-102 | Should |

### Permissions

`csr.manage`

---

## M-37 Partners

**Purpose:** Industry partner, sponsor, professional membership এবং ছাত্রদের জন্য discount partner।

**Actors:** Admin, Office role (permission অনুযায়ী)

**Data:** নাম, partner ধরন (Industry / Sponsor / Membership / Discount / Other — Taxonomy), logo, website, বিবরণ, category (Discount partner-এর জন্য category ও icon), discount-এর মান ও শর্ত (Discount ধরনের জন্য), চুক্তির মেয়াদ (ঐচ্ছিক), related entity (যেমন Lab-এর partner — বর্তমান `mps`), international কিনা।

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-PRT-01 | Partner CRUD; ধরন অনুযায়ী অতিরিক্ত field (Discount: মান, শর্ত, category) | Q-16 | Must |
| FR-PRT-02 | Partner page (`/partners`): ধরন অনুযায়ী tab; Discount partner page (`/student-discounts`): category ও icon অনুযায়ী | Q-16 | Must |
| FR-PRT-03 | Related To দিয়ে Lab / Department / Program-এর partner (বর্তমান `mps`) | BR-CNT-02 | Must |
| FR-PRT-04 | মেয়াদোত্তীর্ণ discount automatically লুকাবে | — | Should |
| FR-PRT-05 | Homepage section: "Industry Partners" (logo carousel) | FR-GEN-102 | Must |

### Business Rules

* **BRL-PRT-01:** International partner university M-35-এ থাকবে; এই module-এ শুধু অন্যান্য প্রতিষ্ঠান। দুটিই একই "Partners" homepage section-এ একসাথে দেখানো যাবে (section-এর source হিসেবে দুটি module নির্বাচন)।

### Permissions

`partners.manage` (scope অনুযায়ী)

---

## M-38 Contact / Inquiry

**Purpose:** Visitor-দের প্রশ্ন ও যোগাযোগ form গ্রহণ, সঠিক বিভাগে পাঠানো এবং উত্তর প্রক্রিয়া track করা; এবং Contact page।

**Actors:** Visitor, Admin, Scoped / Office user (নিজের বিভাগে আসা inquiry)

**Data:**

| অংশ | তথ্য |
|---|---|
| Inquiry Type | নাম (যেমন Admission, Academic, Transport, General), কোন entity / Office-এ যাবে, recipient email, auto-reply template |
| Inquiry | type, নাম, email, phone, বিষয়, বার্তা, ঐচ্ছিক attachment, source page, related entity (যে microsite থেকে এসেছে), অবস্থা (New / In Progress / Replied / Closed / Spam), assignee, internal note, উত্তর ইতিহাস |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-INQ-01 | Contact page (`/contact`): Website Settings-এর ঠিকানা, map, phone, email; Office / Department contact (Assignment থেকে); inquiry form | BR-THM-05 | Must |
| FR-INQ-02 | Inquiry Type CRUD ও routing নিয়ম | — | Must |
| FR-INQ-03 | Inquiry form যেকোনো page বা microsite-এ block হিসেবে যোগ করা যাবে; microsite থেকে এলে সেই entity-তে route হবে | BR-MS-07 | Must |
| FR-INQ-04 | Spam সুরক্ষা: honeypot, rate limit, ঐচ্ছিক captcha (NFR-SEC-06) | NFR-SEC-06 | Must |
| FR-INQ-05 | জমা হলে visitor-কে auto-reply email এবং সংশ্লিষ্ট user-দের notification (FR-NOTI-07) | FR-NOTI-03 | Must |
| FR-INQ-06 | Admin inbox: অবস্থা, type, entity, তারিখ দিয়ে filter; assign; internal note | — | Must |
| FR-INQ-07 | Admin Panel থেকে উত্তর পাঠানো (email), উত্তর ইতিহাস সংরক্ষণ | — | Should |
| FR-INQ-08 | Scoped / Office user শুধু নিজের entity-তে আসা inquiry দেখবে | BR-SEC-04 | Must |
| FR-INQ-09 | Personal data retention: Closed inquiry X মাস (default ২৪) পর automatically মুছে ফেলা | — | Should |
| FR-INQ-10 | Export (CSV) | — | Should |

### Business Rules

* **BRL-INQ-01:** Inquiry-র ব্যক্তিগত তথ্য activity log-এ পুরো লেখা হবে না (শুধু id ও অবস্থা পরিবর্তন)।

### Permissions

`inquiries.view`, `inquiries.reply`, `inquiries.manage-types`, `inquiries.export`

### Acceptance Criteria

* **AC-INQ-01:** CSE microsite থেকে পাঠানো inquiry শুধু CSE scoped user ও Admin-এর inbox-এ দেখাবে, এবং তাঁরা notification পাবেন।

---

## M-39 Newsletter / Subscription

**Purpose:** Email subscriber সংগ্রহ (বর্তমানে ৬,৫০০+) এবং subscriber-দের কাছে newsletter পাঠানো।

**Actors:** Visitor, Admin, Communication / PR role

**Data:**

| অংশ | তথ্য |
|---|---|
| List | নাম (যেমন General, Admission Updates, Notices), বিবরণ, public কিনা (visitor নিজে বেছে নিতে পারবে) |
| Subscriber | email, নাম (ঐচ্ছিক), list, verified তারিখ, অবস্থা (Pending / Subscribed / Unsubscribed / Bounced), source (footer, admission page ইত্যাদি), unsubscribe কারণ |
| Campaign | বিষয়, বিষয়বস্তু (rich text / template; News বা Notice থেকে তৈরি করা যাবে), list, পাঠানোর সময়, অবস্থা, পাঠানো / ব্যর্থ সংখ্যা |

### Functional Requirements

| ID | Requirement | BRD Ref | Priority |
|---|---|---|---|
| FR-NLT-01 | Subscribe form (footer, block, popup): email + list নির্বাচন | BR-THM-05 | Must |
| FR-NLT-02 | Double opt-in: confirmation email-এর link-এ click করলে Subscribed | NFR-SEC | Must |
| FR-NLT-03 | প্রতিটি email-এ one-click unsubscribe link; unsubscribe page-এ কারণ (ঐচ্ছিক) | — | Must |
| FR-NLT-04 | List ও Subscriber CRUD, import (CSV) ও export | — | Must |
| FR-NLT-05 | Campaign: compose, test email, এখনই বা নির্ধারিত সময়ে পাঠানো (queue, throttled); News / Notice থেকে "Create campaign" | — | Should |
| FR-NLT-06 | Campaign report: পাঠানো, ব্যর্থ, bounce | — | Should |
| FR-NLT-07 | External email service integration (Mailchimp / Brevo ইত্যাদি) — subscriber sync | — | Could |
| FR-NLT-08 | Admission Info module-এর subscriber (FR-ADM-09) এখানে "Admission Updates" list হিসেবে | FR-ADM-09 | Should |

### Business Rules

* **BRL-NLT-01:** একই email একই list-এ একবারই।
* **BRL-NLT-02:** Unsubscribed email-এ কখনো campaign যাবে না, এমনকি পুনরায় import করলেও (suppression)।

### Permissions

`newsletter.view`, `newsletter.manage`, `newsletter.send`

### Acceptance Criteria

* **AC-NLT-01:** নতুন subscriber confirmation link-এ click না করা পর্যন্ত "Pending" থাকবে এবং campaign পাবে না।
* **AC-NLT-02:** Unsubscribe করা email CSV import-এ আবার এলে Subscribed হবে না।

---

## বর্তমান System থেকে পরিবর্তন (Migration Note)

| বর্তমান (`project_diu`) | নতুন system |
|---|---|
| `roads`, `stops`, `schedules`, `road_stop`, `road_schedule`, `road_services`, `road_service_schedule`, `road_service_stop`, `transport_services`, `transport_settings` | Transport (M-31) |
| `lab_facilities` | Lab (M-32) |
| `lab_tools`, `lab_tool_categories`, `lab_scopes`, `lab_scope_categories`, `lab_projects`, `lab_project_categories`, `lab_products`, `lab_product_categories`, `lab_management_categories` | Lab sub-item ও shared category |
| `lab_menus`, `lab_app_menus` | Core Menu (C-08) |
| `lab_mid_sections`, `lab_section_summaries` | Homepage Builder (C-10) section |
| `lab_news`, `lab_research`, `lab_galleries`, `lab_committees` | News, Research, Gallery, Committee — Lab relation সহ |
| `mps`, `mps_categories` | Partners (M-37), Lab relation সহ |
| `facilities` | Facilities (M-33) |
| `accreditations`, `accreditation_categories`, `rankings` | Accreditation & Ranking (M-34) |
| `international_linkages`, `featured_partners`, `countries` | International Linkage (M-35), featured → Highlight |
| `international_conferences`, `international_conference_categories` | Conference (M-35) |
| `visitors` | International Visitor (M-35) |
| `csrs`, `csr_categories` | CSR (M-36) |
| `discount_partners`, `discount_partner_categories` | Partners (M-37), ধরন Discount |
| `newsletters`, `admission_subscribers` | Newsletter (M-39), list অনুযায়ী |

---

## Traceability Summary (BRD → SRS Part 5)

| BRD Requirement | SRS Requirement |
|---|---|
| Q-16 (নতুন module) | FR-TRN, FR-LAB, FR-FCL, FR-ACR, FR-INTL, FR-CSR, FR-PRT |
| BR-MS-01/04/07/10 (Lab microsite) | FR-LAB-02, FR-LAB-06, FR-LAB-08, FR-LAB-09 |
| BRD 5.4, BR-SEC-05 (Lab permission) | FR-LAB-03, BRL-LAB-01 |
| BR-CNT-02 (Relation) | FR-LAB-07, FR-FCL-01, FR-ACR-01, FR-PRT-03 |
| BR-CNT-07 (Highlight) | FR-INTL-04, FR-CSR-03 |
| BR-THM-05 (Contact, Newsletter) | FR-INQ-01, FR-NLT-01 |
| BR-SEC-04 (Scope) | FR-INQ-08 |
