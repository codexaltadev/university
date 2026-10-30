# Department

Installable module M-02 (SRS Part 2). Academic › Departments and the department microsites.

| | |
|---|---|
| Slug | `department` |
| Tables | `department_departments` (B) — DB-03 §3; media `photo`, `cover`; Head = assignment `head` |
| Permissions | resource `…:Department`; scoped users edit the profile but not Faculty, name, short name, address, active |
| Depends on | `faculty` (hard: `faculty_id` FK, restrict) |

* **Admin:** Faculty (required), name, about title, about, Head's message, location per language; short name
  (unique inside its Faculty, BRL-DEPT-01), web address, own website (FR-DEPT-04), email, phone, "show in the
  department list", photo / cover, people (Head, Coordinator, Officer, Contact, Industry Expert), SEO, order by
  drag; list filter by Faculty.
* **Scope:** a Department user sees and edits only their Department; a Faculty user the departments of their
  Faculty (through the hierarchy). Creating needs `Create:Department` (not given by default, BRL-DEPT-03) and the
  Faculty list only offers the user's own Faculty.
* **Moving** to another Faculty (FR-DEPT-08) is an Admin's: the kernel fires `EntityHierarchyChanged`, and scope,
  menu / header / footer fallback and the hierarchy follow the new Faculty (AC-DEPT-02).
* **Delete guard:** a Department with active Programs or Faculty Members cannot be deleted (BRL-DEPT-02 — those
  modules register `relationTarget(…, parent: Department::class)`); its Faculty cannot be deleted while it is active.
* **Website:** `/departments` grouped by Faculty, `?faculty={short name}` shows one; layouts grid / list / card
  (Appearance › Layouts); views also get `$listed`, every listed department whatever the filter. Settings ›
  Departments: the page's title, text, picture and Quick Links (`settings('department.…')`, DIU's by default).
  A department with its own website links there. Departments of an inactive Faculty leave
  the list. Microsite `/department/{slug}` (parent: its Faculty); until it builds its own homepage,
  `department::microsite.home` shows cover, about, counters, Head's message and contact (FR-DEPT-07).
* **Counters:** every new Department gets its own stat set — Programs, Faculty Members (shown once those modules
  are active) and Students (manual) (FR-DEPT-05). Metric `department.count` (main site; a Faculty counts its own).
* **Homepage section** "Departments" (`department.list`): all, or one Faculty's; on a Faculty microsite that
  Faculty's (FR-FAC-06). Search (department group, "only in this Department"), sitemap, JSON-LD.

### Views (theme-overridable: `themes/{theme}/views/modules/department/…`)

| View | Variables |
|---|---|
| `listing.grid` / `listing.list` / `listing.card` | `$groups` (list of `faculty`, `departments`), `$faculties`, `$currentFaculty`, `$options` (per_row, sidebar) |
| `partials.card` | `$department`, `$style` |
| `partials.faculty-filter` | `$faculties`, `$currentFaculty` |
| `sections.departments` | `$items`, `$section`, `$settings` |
| `microsite.home` | `$entity` (Department), `$context` |
