# Changelog

## Unreleased

- Each block goes through the theme's wrapper `theme::partials.page.block` when the theme has one ($type, $core,
  $html) — page text and a module's block can look different.
- `quick_links` (DB-02 v0.3): "Quick Links" under the sidebar menu of a with-sidebar page.
- Layouts as in WordPress (FR-PAGE-04 v0.3): Standard (no sidebar), Full width, Left sidebar (`with-sidebar`), Right
  sidebar (`right-sidebar`, new), Landing. The sidebar menu and Quick Links are chosen for either sidebar.
- Blocks (FR-PAGE-02 v0.3): Link cards (tiles as DIU's /students, boxes as /career's values), Location (a campus of
  Settings › Contact with its map), Job listings (read from the company's skill.jobs address, remembered 30 minutes).
  They are shown in the theme's own look (`PageBlocks::DESIGNED`), not as page text. The default template shows the
  page's short description under the title.

## 1.0.0

- First version.
- Pages with blocks, owner, parent, layout, web address, files, duplicate.
