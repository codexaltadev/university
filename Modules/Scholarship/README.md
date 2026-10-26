# Scholarship

Installable module, the scholarship part of M-10 (SRS Part 2 v0.2, FR-FEE-10): scholarships and the calls /
opportunities under them. No dependency — it runs with or without the Tuition and Waiver modules.

| | |
|---|---|
| Slug | `scholarship` |
| Admin | **Academic › Scholarships** — list with "Under" filter, "Add a call" on a scholarship's row, drag order, trash |
| Tables | `scholarship_scholarships` (B) — DB-03 §11.3 v1.4: `parent_id` (one level), title, summary, details, eligibility, how to apply (per language), apply link, deadline, order, active |
| Media | `image`, `attachments` (Media usage) |
| Category | vocabulary `scholarship.category` (hierarchical, one per scholarship, Site › Categories & Tags) — on top-level scholarships; calls are in their scholarship's |
| Permissions | resource `…:Scholarship`; the Waiver Office gets them on install (seeder, once); any role may be given them |
| Website | `/scholarships`, `/scholarships/{slug}` |

* **One level:** a scholarship (DIU Scholarship, Erasmus …) or a call under one (Erasmus → "Spring 2025 exchange
  at …"). A call cannot have calls, a scholarship with calls cannot go under another, and it is not deleted
  while it has calls (FR-GEN-08).
* **Deadline:** after it the website shows "Closed", the apply button goes, and the call moves below the open ones.
* **Website:** `/scholarships` lists the active top-level scholarships grouped by category (vocabulary order, the ones
  without a category last as "Other scholarships"; one list when no category is used), `?category={slug}` keeps a category
  and its sub-categories, with their number of calls;
  `/scholarships/{slug}` shows the details, eligibility, how to apply, apply link, files and the calls (open first),
  with the trail Home › Scholarships › (parent) › title. An old address answers 301 (FR-GEN-32); an inactive
  scholarship hides its calls too. Search, sitemap, menu item type, JSON-LD `MonetaryGrant`.
* **DIU data:** `php artisan legacy:migrate --step=S07S` — the 7 legacy categories become scholarships, the 27
  legacy scholarships their calls (all under Erasmus; 16 unpublished come over inactive); old addresses kept.

### Views (theme-overridable: `themes/{theme}/views/modules/scholarship/…`)

| View | Variables |
|---|---|
| `listing` | `$groups` (list of `category`, `scholarships`), `$categories`, `$selected`, `$scholarships` (with `parts_count`), `$page` (Support\ScholarshipPage: title, text, image, sideLinks, quickLinks, intro, outro, groups) |
| `show` | `$scholarship`, `$parent` (or null), `$parts` (open first), `$page` (scholarship(): sections, apply, files, parts) |
