# Program

Installable module M-03 (SRS Part 2). Academic › Programs and the program pages / microsites.

| | |
|---|---|
| Slug | `program` |
| Tables | `program_programs` (B) — DB-03 §4.1; media `image`, `brochure`. Sub-items (§4.2, B, cascade on force delete): `program_majors`, `program_list_items` (career_path / industry), `program_facilitators` (media `photo`), `program_materials` (media `file`), `program_memberships` (media `logo`), `program_variants` (media `image`). |
| Vocabularies | `program.level` (one per program, column `level_term_id`; seeded: Undergraduate, Graduate, Diploma, Post Graduate Diploma), `program.tag` (many, core_termables), `program.major-category` |
| Permissions | resource `…:Program`; scoped users edit the profile but not Department, name, short name, address, active. |
| Depends on | `department` (hard: `department_id` FK, restrict) and `faculty` (listing filter; the Faculty comes from the Department) |

* **Admin:** texts per language (name, subtitle, degree, duration label, summary, objective, eligibility general /
  English medium, AI summaries); Department, level, tags, short name, web address (unique site-wide — a taken
  one gets a suffix, BRL-PROG-01); credits, years, semesters, medium, shift, class mode; admission (next intake,
  application link, structured eligibility rules); active, publish date, "show in the program list"; image,
  brochure, video; SEO; people (Coordinator, Officer, Contact, Industry Expert); drag order; filters by Faculty,
  Department, level, shift.
* **Program Editor** (UI/UX Part A §5.2.1): a new program is a wizard (Basic info, Details, Eligibility, Majors,
  Options, Career & industry, Facilitators & people, Materials & memberships, Summary & SEO; skippable); an existing
  one shows the same parts as tabs on the left with ✓ / ! / item counts and "x of 7 parts filled in"
  (`Support\ProgramChecklist`). Who may only view gets it read only (`ViewProgram`).
* **Sub-items** (BRL-PROG-04) are lists inside their part: Majors & Specializations (category), Career Paths,
  Industries, Facilitators & Industry Experts, Learning Materials (link or file), Professional Memberships,
  Program Options (variants: related program, button). They have no policy of their own — whoever may update the
  Program edits them, whoever may view it sees them (AC-PROG-03). Each part has an optional intro on top.

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
