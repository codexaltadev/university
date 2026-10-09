# Homepage

Core module C-10 (SRS Part 1). Appearance › Homepage.

| | |
|---|---|
| Slug | `homepage` |
| Tables | `homepage_layouts`, `homepage_sections` (DB-02 §7) |
| Permissions | custom: `Manage:Homepage` (own microsites), `ManageMain:Homepage` (main site) |
| Settings | none |

* Sections in order (drag), on / off, schedule (show from / until), background, heading per language.
* Core sections: hero / slider, statistics (a counter set of the owner), text, picture + text, call to
  action, custom HTML (Super Admins only), space. Modules add theirs with
  `$r->homepageSection(LatestNews::class)` (a `Platform\Homepage\Section`); content sections get source
  mode (latest, highlighted only, highlighted first, hand-picked), count, layout, "include what is below",
  "view all" link, and use the highlights of this homepage's context.
* A microsite without its own homepage shows the theme's default; "start from the main homepage" copies it.
* A section whose module is off is hidden on the website (⚠ in admin).
* Website: `app(Homepages::class)->forOwner('department.department:12')` → sections with their data, or null.
* Seeder: the main homepage's default sections (FR-HOME-12).

Not yet: preview before saving (with the website theme).

## Views

`homepage::sections.{hero, statistics, rich-text, image-text, cta, html, spacer}` — rendered by the theme.
