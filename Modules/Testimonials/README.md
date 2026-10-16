# Testimonials

What students, alumni, parents, employers and partners say (M-21, DB-05 §6 v1.5, SRS Content
FR-TEST-01..04).

| | |
|---|---|
| Slug | `testimonials` |
| Table | `testimonials_testimonials` (model `Testimonial`, attachable, assignable) |
| Person | exactly one assignment, role `author` (`Support\Authors::set()`); from any person directory or a manual person; its `role_label` is the person's own title here ("Parent of a CSE student"), else their designation |
| Photo | the person's photo; media use `photo` only as the fallback |
| Fields | quote per language, `testimonial_type` (student / alumni / parent / employer / partner), rating 1–5, video link (YouTube / Drive → embed) |
| Order | dragged (`sort_order`, FR-TEST-04); no publish date — `is_active` |
| Where | "Show in" entities; the main site by `PublishMain:Testimonial` (Admin); a program's page gets the **Testimonials** tab |
| Layouts | Appearance › Layouts `testimonials.listing` = slider / grid / masonry / video |
| Legacy | `php artisan legacy:migrate --step=S17M` (after S06); photos after S03 |

Website:

| Address | What |
|---|---|
| `/testimonials`, `/{entity}/testimonials` | the place's testimonials in order; `?type=`, `?view=` (video = only those with a video) |

Homepage section `testimonials.list` "What People Say" (FR-TEST-03): the builder's source mode
("latest" = the dragged order), only the chosen types when set, slider or grid.

## Views

| View | Variables |
|---|---|
| `testimonials::index` | `$items`, `$type`, `$types`, `$layout`, `$base` |
| `testimonials::partials.list` | `$items`, `$layout` |
| `testimonials::partials.card` | `$testimonial`, `$video` |
| `testimonials::sections.list` | `$items`, `$section`, `$settings` (types, display, count); `$context` from the page |
| `testimonials::tabs.program` | `$entity`, `$items` |
