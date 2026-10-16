# Slider

Hero slides, background video and page banners for any site (M-23, DB-05 §8 v1.7, SRS Content
FR-SLD-01..07).

| | |
|---|---|
| Slug | `slider` |
| Table | `slider_slides` (model `Slide`, attachable) |
| Idea | a place's hero = the slides shown there: **one = a still hero, several = a slider**; a slide with a video = a full-screen background video (autoplay, muted, loop, playsinline; the picture is the poster) |
| Reuse | one slide "Show in" many places at once (main site, departments, programs, any microsite) — not made again per site |
| Media | Media Library items, never copies: `image_desktop` (needed; 1920 × 1080), `image_mobile` (else the desktop one cropped; 1080 × 1350), `video` (MP4 / WebM — Settings › Media allows `mp4`, `webm`) |
| Text | title, subtitle, up to 4 bullets per language, up to 2 buttons; text left / centre / right; darken 0–80 % (brand-colour gradient) |
| Phones | `video_on_mobile` off = only the picture; `prefers-reduced-motion` = no video, no autoplay |
| Time | `is_active`, `active_at` (from), `expires_at` (until — a campaign); no publish date |
| Placement | `hero` or `page_banner` (cover photo) |
| Legacy | `php artisan legacy:migrate --step=S17S` (after S04, S06, S10); again after S03 to link the pictures |

`Support\Hero::for($contextKey)` (FR-SLD-06): the place's hero slides → its banner → the same for
each parent entity (nearest first); the main site never borrows. Slides without a picture or video
are left out.

Homepage section `slider.hero` "Hero" (any homepage): autoplay, interval, slide / fade, arrows,
dots, height (full screen / large / medium); Alpine. The Homepage module's `core.hero` stays for
sites without this module.

## Views

| View | Variables |
|---|---|
| `slider::sections.hero` | `$items` (Slide list), `$section`, `$settings` |
