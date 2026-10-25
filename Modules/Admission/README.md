# Admission

Installable module M-09 (SRS Part 2 v0.3, FR-ADM-01..16; DB-03 v1.5 §10): admission circulars and their dates,
information cards, how-to-apply steps, contacts, admission test results (import and lookup) and subscribers, as
page blocks for the `/admission` page and any other page. Optional: Program (results matched to programs).

| | |
|---|---|
| Slug | `admission` |
| Admin | **Academic › Admission** — Circulars, Admission information, How to apply, Admission contacts, Test results (Import / Export), Subscribers (Export) |
| Tables | `admission_circulars`, `admission_highlights`, `admission_steps`, `admission_contact_groups`, `admission_result_batches`, `admission_results`, `admission_subscribers` — all with `is_active`, no publish date |
| Category | vocabulary `admission.highlight-category` (Notice, Facility, Campus Life …) |
| People | contact groups are assignable (role `contact`): Administration people, or manual ones |
| Permissions | resource `…:Circular`, `…:AdmissionHighlight`, `…:AdmissionStep`, `…:AdmissionContactGroup`, `…:ResultBatch`, `…:AdmissionSubscriber`; the "Admission Office" role gets them on install (seeder, once) |
| Settings | Website Settings › Admission: Apply Now link, Request for Information link, WhatsApp group link; the Admission Office (name, address, helpline, email, Google Maps embed); the result page's text, links and picture |
| Website | `/admission` is a **Page** (made on install with the module's blocks and an empty "Admission" free menu as its sidebar); its section pages are its child pages. Module pages: `/admission/result`, `/admission/circulars`, `/admission/circulars/{slug}` |

* **Circulars:** session as text ("Fall 2026") until the Session module; dates apply from / last day / test /
  admission / classes. Closed after the last day to apply; lists show open ones first with "N days left".
* **Results:** one batch per admission test with a message per result (`{name}` = the student) and a Published
  switch (= active). Import: Excel or CSV with a header row (Program, Application serial, Student name, Result);
  "Check only" lists the problems by line without saving; importing replaces the batch's results, in bulk. The
  file's own words stay ("Selected 1st Merit List") next to the result used for the message. Programs are matched
  by name, short name, or name without its "(BBA)" ending.
* **Lookup:** application serial → name, result and message, nothing else (BRL-ADM-02); published batches only,
  the newest first; 10 tries a minute per visitor and a hidden trap field (FR-ADM-07 — a captcha joins when the
  kernel offers one).
* **Subscribers:** email or WhatsApp per topic; the same contact and topic is one subscriber; an email gets a
  confirmation link and an unsubscribe link (signed).
* **Blocks:** Admission circulars, Admission information, How to apply, Admission contacts, Admission updates
  sign-up, Admission result lookup, Apply / Request information. **Homepage section:** Admission going on.
* **DIU data:** `php artisan legacy:migrate --step=S08` — 2 circulars, 26 cards in 3 categories, 2 contact groups
  with 13 people (from `admins`), the "Summer 2026" batch with its messages and 1,717 results (3 repeated serials
  merged), 4,828 subscribers. Pictures wait for S03.

### Views (theme-overridable: `themes/{theme}/views/modules/admission/…`)

| View | Variables |
|---|---|
| `contact` | `$page` (Support\AdmissionPage: office, social, contacts) |
| `circulars` | `$circulars` (open first), `$page` |
| `circular` | `$circular`, `$page` (circular(): dates with status, sanitised body, PDF, apply link) |
| `result` | `$intro`, `$page` (intro, resultLinks, resultImage, applyUrl, office) |
| `subscription` | `$message` |
| `partials.circular-card`, `partials.dates` | `$circular` |
| `blocks.*` | `$data`, `$locale`, `$admission` (Support\AdmissionBlocks: circulars, links, highlights, steps, contacts) |
| `sections.going-on` | `$items`, `$section`, `$settings` |
| `livewire.result-lookup` | `wire:model` `serial`, `website` (trap); `wire:submit` `lookup`; `$answer` (status, name, label, message, batch) |
| `livewire.subscribe-form` | `wire:model` `channel`, `contact`, `website` (trap); `wire:submit` `subscribe`; `$done`, `$channels`, `$whatsappGroup` |
