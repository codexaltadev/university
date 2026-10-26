# Seo

Core module C-12 (SRS Part 1). Site › Redirects, Site › 404 log, Site › Search terms and the Settings › SEO and Search tabs. The
per-record SEO panel, the head tags, sitemap.xml and robots.txt belong to the kernel (`Platform\Seo`).

| | |
|---|---|
| Slug | `seo` |
| Tables | `seo_redirects` (B), `seo_not_found_logs` (L) — DB-02 §9.2/9.3; kernel `core_seo_meta` (§9.1) |
| Permissions | resource: `…:Redirect`, `ViewAny / Delete:NotFoundLog`; custom `Manage:Seo` (Settings › SEO) |
| Settings | `seo`: title_pattern, default_description, og_image, google / bing verification, organization_type, robots_txt |

* **Redirects:** old address (normalized: no query, no trailing slash, lower case) → an address or a record
  of the website (follows its current address). 301 / 302, active, visits. Saving refuses loops and
  removes chains (A → B with B → C stored as A → C; X → A becomes X → B). CSV import for the old
  website's addresses. Answered by a global middleware before routing.
* **404 log:** every website address that ends in 404 (not the admin panel or asset files), with visits,
  first / last seen and referrer; "Redirect" makes one in a click and marks it resolved. Resolved rows go
  after 90 days.
* **Changed web addresses** (slug history) answer 301 by themselves (FR-GEN-32); no redirect row needed.

### Search terms and Settings › Search (Phase 4.7g)

* **Search terms:** every search on the search page (the kernel's `SearchPerformed`, first page only — not the header box
  while typing), lower case: how often and how many results the last time; "No results" shows what visitors do not
  find. Rows not searched for 180 days go (`seo:search-terms-clean`).
* **Settings › Search:** synonyms, one rule a line (`cse = computer science and engineering`) — the kernel's search
  matches a word by its synonyms; "Popular searches" (entered, else the most searched terms that found something in
  90 days), given to `theme::search` and `theme::livewire.instant-search` as `$popular`.
