# Faculty Member

Teachers' directory (M-07, DB-03 §8 v1.7, SRS Academic v0.5 FR-FM-02..12). The teachers are kept
on the university's **faculty site**; this module keeps a **read-only copy** and refreshes it
(`faculty-member:sync`). A person source: Dean, Head, committee members, event speakers … point
at a teacher and follow every change the faculty site makes.

| | |
|---|---|
| Slug | `faculty-member` |
| Tables | `faculty_member_profiles` (model `FacultyMember`, morph `faculty-member.member`), `faculty_member_departments` (also in) |
| Depends on | `department`, `faculty`; scope: Department users see their own teachers |
| Vocabulary | `faculty-member.designation` — the ladder (Professor → … → Lecturer, seeded); its order is the directory order; new designations from the source are added at the end |
| Permissions | Shield from the resource (`ViewAny/View/Update/Reorder:FacultyMember`; create / delete never allowed); custom `Sync:FacultyMember` |
| Settings | Website Settings › Faculty Members: source (`json` + `json_path`, or `api` + `api_url` + `api_token`), daily sync, profile pages, intro; Appearance › Layouts `faculty-member.directory` = grid / list / cards |
| Metric | `faculty-member.count` (a department's teachers incl. "also in", a faculty's departments', or all) |

## Source and sync

* **Now:** `resources/data/faculty-members.json` — **dummy data** (72 fictional teachers on the dev
  departments) used while the faculty site's API is not available. Another file: Settings ›
  Faculty Members › JSON file.
* **Later:** Settings › Faculty Members › Source = API, the address and token. If the API's field
  names differ from the format below, teach `Sync\RecordMapper::ALIASES` (or the mapper) the new
  names — nothing else changes. `Sync\ApiSource` follows `next_page_url` / `links.next`.
* `php artisan faculty-member:sync` — daily 05:30 (when on) and **Sync now** on the admin list.
  Matched by the source `id`: new → added, changed → updated (a hash skips unchanged ones),
  missing → soft deleted, back → restored with assignments. An empty / unreadable source stops
  the sync and removes no one. Departments are matched by short name (case ignored); unknown ones
  are reported and the teacher is kept without a department. The last result is shown above the
  admin list (`settings('faculty-member.last_sync')`).
* Only `show_email`, `show_phone`, `show_extension`, `sort_order` (and SEO) are this site's own.

Record format (`{"data": [...]}` or a plain list; texts are a string or `{"en": …, "bn": …}`):

```json
{
  "id": "T-1001", "employee_id": "710100037",
  "name": {"en": "Dr. Arif Alam"}, "designation": "Professor", "designation_note": "and Head",
  "department": "CSE", "other_departments": ["SWE"],
  "email": "…", "phone": "…", "extension": "65123", "office_room": "AB4-512",
  "photo_url": "https://…", "employment_type": "full_time|part_time|adjunct|on_leave", "status": "active|inactive",
  "biography": "<p>…</p>", "research_interests": "…",
  "links": {"google_scholar": "…", "orcid": "…", "researchgate": "…", "linkedin": "…", "website": "…"},
  "profile": {"education": [{"title": "PhD", "institution": "…", "start_year": 2006, "end_year": 2010, "description": "…", "url": "…"}],
              "experience": [], "training": [], "award": [], "membership": [], "publication": []}
}
```

Also understood: `employeeID`, `position`, `picture` / `photo`, `cell`, `bio`, `ext`, `room`.

## Website

| Address | What |
|---|---|
| `/faculty-members` | designation order; `?faculty=` / `?department=` (short name), `?designation=` (term id), `?q=` (name, note, research interests), `?view=`; 30 a page |
| `/{faculty}/faculty-members`, `/{department}/faculty-members` | that place's own teachers (FR-FM-05) |
| `/faculty-members/{slug}` | photo, designation, departments, public contacts (email written in character references, FR-FM-09), links, biography, research interests, profile sections, roles |

Homepage section `faculty-member.list` "Faculty Members" (settings `count`, `designations`).

## Views

| View | Variables |
|---|---|
| `facultymember::directory` | `$members` (paginator), `$faculties`, `$departments`, `$designations`, `$layout`, `$intro` |
| `facultymember::profile` | `$member`, `$person` (PersonPresenter + `protectedEmailLink()`), `$departments`, `$places` |
| `facultymember::partials.card` | `$member`, `$layout` |
| `facultymember::sections.list` | `$items` (`members`, `url`), `$section`, `$settings` |
