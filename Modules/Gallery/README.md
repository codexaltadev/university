# Gallery

Photo and video albums (M-22, DB-05 §7 v1.6, SRS Content FR-GAL-01..07).

| | |
|---|---|
| Slug | `gallery` |
| Tables | `gallery_albums` (model `Album`, attachable), `gallery_videos` (model `Video`, assignable — role `speaker`) |
| Pictures | **Media Library items, never copies (BRL-GAL-01):** the album's media uses of field `items`, in the dragged order, `meta.caption` = its own caption here (else the item's caption). One picture can be in many albums. No item table. |
| Form | `Filament\Components\AlbumItems` — "Pick from the library" (many at once, uploading there too), "Add a folder" (every picture / video of a Media Library folder), drag, caption per row; non-picture files are left out |
| Videos | uploaded video files = album items (kind `video`); YouTube / Vimeo / Facebook links = `gallery_videos` (provider and id from the link, embed, YouTube thumbnail, episode, length, speaker) |
| Album | title, description, date (`occurred_on`), series (`documentary` / `lecture_series`), event (optional, `event_id`), cover (media `cover`, else the first picture); `is_active`, `active_at` |
| Where | "Show in" entities; the main site by `PublishMain:Album` (Admin) |
| Vocabulary | `gallery.category` (hierarchical) |
| Layouts | Appearance › Layouts `gallery.album` = masonry / grid / carousel, `gallery.listing` = cards / grid |
| Legacy | `php artisan legacy:migrate --step=S17G` (after S04, S10); again after S03 to link the pictures |

Website:

| Address | What |
|---|---|
| `/gallery`, `/{entity}/gallery` | albums, newest first; `?category=`, `?kind=photos|videos`, `?view=` |
| `/gallery/{slug}` (also under a microsite) | pictures with a lightbox (Alpine: ← → Esc, swipe), uploaded and link videos, episode and speaker; 404 where it is not shown |

Homepage sections (FR-GAL-07): `gallery.photos` "Photo Gallery" (the builder's source mode; album
covers or their pictures) and `gallery.videos` "Video Gallery" (videos of the place's albums,
one series optionally). An event's page shows its albums (entity tab on `Event`, FR-GAL-06).

## Views

| View | Variables |
|---|---|
| `gallery::index` | `$albums` (paginator), `$categories`, `$category`, `$kind`, `$layout`, `$base` |
| `gallery::show` | `$album`, `$photos`, `$uploads` (`['item', 'caption']` lists), `$videos`, `$layout`, `$base` |
| `gallery::partials.photos` | `$photos`, `$layout` |
| `gallery::partials.card` | `$album`, `$base`, `$layout` |
| `gallery::partials.video` | `$video` |
| `gallery::sections.photos` / `videos` | `$items`, `$section`, `$settings`; `$context` from the page |
| `gallery::tabs.event` | `$entity`, `$albums` |
