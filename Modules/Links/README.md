# Links

Important links of each site — UGC, Student Portal, Library … (M-29, DB-05 §14 v1.10, SRS
Content FR-LINK-01..03).

| | |
|---|---|
| Slug | `links` |
| Table | `links_links` (model `Link`, attachable) |
| Fields | title, description per language; `url` (`https://…`, a page of this site `/…`, `mailto:`, `tel:`); open in a new tab; icon from the Media Library (`icon`); group `links.group` |
| Order | dragged (`sort_order`); on the website by group (the group's order), links without a group last |
| Where | "Show in" entities — each department / office its own; the main site by `PublishMain:Link` (Admin) |
| Check | `links:check` every Monday 04:00 (`LinkChecker`: HEAD, else GET, 10 s, outside links only); status kept without touching `updated_at`; newly broken → notification `links.broken` to the people who may edit the link; admin list status, "Broken" filter, "Check now"; a new address is checked again |
| Layouts | Appearance › Layouts `links.listing` = tiles / list |
| Legacy | none — DIU's old site had no links table (`international_linkages` belong to International) |

Website: `/links`, `/{entity}/links`. Homepage section `links.list` "Important Links" (one group
optionally, tiles or list). **Footer block** `links.footer` — Header & Footer › footer column ›
block "Important Links" (group, how many), through the kernel footer block hook
(`$r->footerBlock()`, `Platform\Website\FooterBlock`, `<x-platform::footer-block>`). Sidebar
widget: with M-30 Widgets.

## Views

| View | Variables |
|---|---|
| `links::index` | `$groups` (list of `group` ?Term, `links`), `$layout` |
| `links::partials.link` | `$link`, `$layout` (tiles / list / footer) |
| `links::sections.list` | `$items`, `$section`, `$settings` |
| `links::footer.block` | `$block`, `$context`, `$links` |
