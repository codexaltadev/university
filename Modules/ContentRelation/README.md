# Content Relation

Core module C-13 (SRS Part 1). Admin screens of the kernel's Relation Engine (`Platform\Relations`).

| | |
|---|---|
| Slug | `content-relation` |
| Tables | kernel: `core_relations`, `core_main_site_requests` (DB-01 §8) |
| Permissions | none of its own: the queue follows `PublishMain:{Model}`, highlights `HighlightMain:{Model}` |
| Settings | none |

* **Content › Main Site Queue** (FR-CNT-H08/H09): content shown in entities but not on the main site, of
  every type the user may publish. Requested items first (badge = open requests). Publish or dismiss,
  one by one or in bulk; filters: type, place, period (default 30 days; requests always show).
* **Content › Highlights** (FR-CNT-H06): choose a place (main site or an entity), see what is highlighted
  now in order, drag to reorder, remove a highlight. For users who may highlight on the main site.

The form side (Show in, main site switch / request, highlight per place) is in the kernel:
`RelatedToField`, `HighlightPanel`, `PublishingPanel` (`Platform\Filament\Components`).

Not yet: e-mail / in-app notice to Admins on a new request (with Notification, C-18).
