# Media

Core module C-07 (SRS Part 1). Site › Media Library.

| | |
|---|---|
| Slug | `media` |
| Tables | kernel: `core_media_folders`, `core_media_items`, `media` (spatie), `core_media_usages` (DB-02 §3) |
| Permissions | `ViewAny / View / Create / Update / Delete / Restore / ForceDelete:MediaItem`; custom: `ManageAll:MediaItem` (see every scope) |
| Settings | Settings › Media (allowed types, size, default images) |

* Grid / list view, search, filters (type, folder, uploader, belongs to, upload date, unused, shared, trash).
* Upload up to 50 files at once. Every file is checked: allowed extension, real content type matches,
  no scripts or PHP inside, SVG cleaned, JPEG location data removed; duplicates are reported.
* Thumbnail 150, medium 768, large 1600 (WebP) are made on the queue.
* Details: preview, facts, copyable stable link `/media/{id}/{file}`, texts per language, where it is used.
  Replace file keeps the item and the link. Deleting a used file lists the places first.
* Scoped users see their entities' media, their own uploads and shared media (FR-MEDIA-14).
* Private files: only through an expiring signed link.

In a module: `use Platform\Concerns\HasMediaUsage;` on the model and
`MediaPicker::make('featured_image')->image()` in the form; read with `$model->firstMedia('featured_image')?->url('large')`.

Not yet: crop / rotate (FR-MEDIA-12, Could), PDF first-page preview (Could), drag files between folders.
