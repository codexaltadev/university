# Program

Installable module M-03 (SRS Part 2). Academic › Programs and the program pages / microsites.

| | |
|---|---|
| Slug | `program` |
| Tables | `program_programs` (B) — DB-03 §4.1; media `image`, `brochure`. Sub-items (§4.2, B, cascade on force delete): `program_majors`, `program_list_items` (career_path / industry), `program_facilitators` (media `photo`), `program_materials` (media `file`), `program_memberships` (media `logo`), `program_variants` (media `image`) |
| Vocabularies | `program.level` (one per program, column `level_term_id`; seeded: Undergraduate, Graduate, Diploma, Post Graduate Diploma), `program.tag` (many, core_termables), `program.major-category` |
| Permissions | resource `…:Program`; scoped users edit the profile but not Department, name, short name, address, active |
| Depends on | `department` (hard: `department_id` FK, restrict) and `faculty` (listing filter; the Faculty comes from the Department) |

* **Admin:** texts per language (name, subtitle, degree, duration label, summary, objective, eligibility general /
  English medium, AI summaries); Department, level, tags, short name, web address (unique site-wide — a taken
  one gets a suffix, BRL-PROG-01); credits, years, semesters, medium, shift, class mode; admission (next intake,
  application link, structured eligibility rules); active, publish date, "show in the program list"; image,
  brochure, video; SEO; people (Coordinator, Officer, Contact, Industry Expert); drag order; filters by Faculty,
  Department, level, shift.
* **Sub-items** (BRL-PROG-04) are edited on the Program's edit page, one list each: Majors & Specializations
  (category), Career Paths, Industries, Facilitators & Industry Experts, Learning Materials (link or file),
  Professional Memberships, Program Options (variants: related program, button). They have no policy of their
  own — whoever may update the Program edits them, whoever may view it sees them (AC-PROG-03). Each part of the
  page can have a short intro ("Section intros" in the form).
* **Scope:** a Program user sees only their Program (AC-PROG-03); Department / Faculty users their programs.
  Moving to another Department is an Admin's (BRL-PROG-02) and fires `EntityHierarchyChanged`.
* **Website:** `/programs` with filters `faculty`, `department` (short names), `level` (slug), `shift`, `mode`,
  `q` (FR-PROG-12, AC-PROG-01) in layouts card / list / table / detailed. Programs of an inactive Department or
  Faculty, scheduled ones and hidden ones are not listed. Page `/program/{slug}`: facts, Apply / brochure /
  video, next intake, overview, counters, objective, majors, options, eligibility, career paths / industries,
  facilitators, materials, memberships (active ones; empty parts left out), then the tabs other modules register with
  `$r->entityTab(Program::class, MyTab::class)` (`Platform\Website\EntityTab`; FR-PROG-18) — an inactive module
  has no tab and a broken tab is left out (AC-PROG-02).
* **Counters:** own stat set — Majors and Industry Experts (metrics `program.majors`, `program.industry-experts`:
  active items of visible programs; a Department / Faculty counts its programs'), Faculty Members and Approved
  Alumni (manual) (FR-PROG-11). Metric `program.count`
  fills the Faculty and Department "Programs" counters.
* **Homepage sections:** "Featured Programs" (`program.featured`, picked or first listed) and "Programs by Level"
  (`program.by-level`); on a Faculty / Department microsite only its own. Search, sitemap, JSON-LD
  `EducationalOccupationalProgram`.

### Views (theme-overridable: `themes/{theme}/views/modules/program/…`)

| View | Variables |
|---|---|
| `listing.card` / `listing.list` / `listing.table` / `listing.detailed` | `$programs`, `$choices` (faculties, departments, levels), `$selected`, `$options` |
| `partials.card` | `$program`, `$style` |
| `partials.filters` | `$choices`, `$selected` |
| `sections.featured` | `$items`, `$section`, `$settings` |
| `sections.by-level` | `$items` (list of `level`, `programs`), `$section`, `$settings` |
| `microsite.home` | `$entity` (Program), `$context` |
| `microsite.items` | `$entity`, `$part` = majors / variants / careers / people |
