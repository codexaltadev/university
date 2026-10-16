# Notice

Official notices — exam, admission, holiday, result, tender — with their files (M-20, DB-05 §5
v1.4, SRS Content FR-NOT-01..07).

| | |
|---|---|
| Slug | `notice` |
| Table | `notice_notices` (model `Notice`, attachable) |
| Files | media uses `attachments`, each with its own title per language (`meta.title`) — kernel `AttachmentList` in the form, `$notice->mediaUsagesIn('attachments')` on the website (AC-NOT-01) |
| Board / archive | `expires_at` takes a notice off the board, homepage and ticker; it stays in the archive and on its own page (FR-NOT-03) — scopes `onBoard()`, `archived()`; not the kernel `HasExpiry` (that hides it everywhere) |
| Order | pinned first while pinned, then newest (`boardOrder()`, AC-NOT-02); urgent in another colour |
| Scheduling | `active_at` (empty = now) |
| Where | "Show in" entities; the main site by `PublishMain:Notice` (Admin) |
| Vocabulary | `notice.category` (seeded once) |
| Settings | Settings › Notices: "New" badge days (default 7, FR-NOT-07), per page; Appearance › Layouts `notice.listing` = table / list / cards |
| Legacy | `php artisan legacy:migrate --step=S16T` (after S04, S10); files after S03 |

Website:

| Address | What |
|---|---|
| `/notices`, `/{entity}/notices` | the board; `?archive=1`; `?category=`, `?entity=` (main board), `?from=` / `?to=`, `?q=` (title or notice number), `?view=` |
| `/notices/{slug}` (also under a microsite) | text, files with titles, PDF preview (Alpine) and download; 404 where it is not shown |

Homepage sections (FR-NOT-06): `notice.board` (latest on the board, pinned first) and
`notice.ticker` (moving titles, urgent first; "only urgent and pinned" option; stops for reduced
motion). Metric `notice.count`, Content Update Reminder source.

## Views

| View | Variables |
|---|---|
| `notice::index` | `$notices` (paginator), `$categories`, `$category`, `$entities`, `$entity`, `$archive`, `$layout`, `$base` |
| `notice::show` | `$notice`, `$attachments` (MediaUsage list), `$base`, `$locale` |
| `notice::partials.badges` | `$notice` |
| `notice::sections.board` / `ticker` | `$items`, `$section`, `$settings`; `$context` from the page |
