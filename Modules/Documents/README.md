# Documents

Forms, policies, prospectus, guidebooks, charters and reports to read or download — with our own
flipbook (M-27, DB-05 §12 v1.9, SRS Content FR-DOC-01..08).

| | |
|---|---|
| Slug | `documents` |
| Table | `documents_documents` (model `Document`, attachable) |
| Type | `document_type` from a fixed list — form, policy (version, approval date, in force from), prospectus (session, program), guidebook, charter, report, other (AC-DOC-01); grouping by the vocabulary `documents.category` |
| File | Media Library use `file` (never a copy), or `external_url`; cover `thumbnail` |
| Versions | "New version" on the edit page (`Support\Versions`): a copy with the new file in the same places; the old one gets `superseded_by_id`, leaves the lists, keeps its address and points to the new one (FR-DOC-05) |
| Switches | `show_as_flipbook` (PDF), `allow_download` (off = read only), `is_private` (signed-in users only, the file through a signed link — FR-DOC-07) |
| Downloads | `/downloads/{slug}/file` counts (`download_count`, no `updated_at` change) and sends the file (FR-DOC-06) |
| Time | `is_active`, `active_at`; no publish date |
| Where | "Show in" entities; the main site by `PublishMain:Document` (Admin); highlight |
| Layouts | Appearance › Layouts `documents.listing` = table / cards / list |
| Legacy | `php artisan legacy:migrate --step=S17D` (after S04, S06, S10); again after S03 to link the files; flipbooks need their original PDF (migration plan 7.7) |

## Flipbook (FR-DOC-03)

No ZIP, no paid software, no server tools: upload the PDF and switch "Flipbook" on.
`public/flipbook.js` (Alpine component `flipbook`) with PDF.js 4.10.38 (legacy build, Apache-2.0,
`public/pdfjs/`, see `VERSION.txt`) draws the pages in the browser:

* two pages side by side on wide screens (the first alone, as the cover), one page on phones;
* 3D page turn (none with reduced motion); arrows, keyboard (← → Home End PageUp PageDown), swipe, clicking a page;
* page number, "go to page", `?page=` links; thumbnails (drawn as they come into view); zoom ×1–×3; full screen;
* only the pages around the open one are drawn and kept (a few in memory), so big books open fast;
* the PDF object stays outside Alpine's reactive data (PDF.js private fields break inside proxies);
* download button only when allowed; a link to the PDF when the browser cannot show it.

The module's `public/` folder is published by the kernel to `public/modules/documents/` on
activation and by `uwms:modules:sync`; views use `module_asset('documents', 'flipbook.js')`.

Website:

| Address | What |
|---|---|
| `/downloads`, `/{entity}/downloads` | current documents, newest first; `?type=`, `?category=`, `?year=`, `?q=`, `?view=` |
| `/downloads/{slug}` (also under a microsite) | details, newer / older versions, flipbook or PDF preview, download |
| `/downloads/{slug}/file` | the counted download |

Homepage sections `documents.downloads` "Downloads" (types filter) and `documents.prospectus`
"Prospectus" (covers); page block "Documents" (a list, or the first PDF as a flipbook).

## Views

| View | Variables |
|---|---|
| `documents::index` | `$documents` (paginator), `$types`, `$type`, `$categories`, `$category`, `$year`, `$years`, `$q`, `$layout`, `$base` |
| `documents::show` | `$document`, `$reader`, `$download`, `$older`, `$base` |
| `documents::partials.flipbook` | `$src`, `$title`, `$download`, `$page` |
| `documents::partials.item` | `$document`, `$base`, `$layout` |
| `documents::sections.downloads` / `prospectus` | `$items`, `$section`, `$settings`; `$context` from the page |
| `documents::blocks.documents` | `$data`, `$locale` |
