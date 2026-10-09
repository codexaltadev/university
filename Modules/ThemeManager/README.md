# ThemeManager

Core module C-06 (SRS Part 1). Appearance › Themes, Design and Layouts — the screens of the kernel's
theme engine (`Platform\Theme`). Named ThemeManager because the `theme::` view namespace always
points at the active theme.

| | |
|---|---|
| Slug | `theme` |
| Tables | kernel: `theme_themes` (DB-02 §13); design in `core_settings` groups `theme.{slug}`, `branding`, `layouts` |
| Permissions | custom: `View:Theme`, `Install:Theme`, `Activate:Theme` (also preview), `Customize:Theme` |
| Settings | none (the screens are the settings) |

* **Themes:** installed themes with screenshot, one active; activate (logged), preview only for the
  admin who asks (a bar on the website with "Stop preview"), upload a ZIP to install or update
  (checked: structure, file types, no PHP logic, `requires_core`), uninstall — never the active or a
  built-in theme (BRL-THEME-01).
* **Design:** colours with a WCAG contrast warning, heading / body / Bangla fonts (Google Fonts), base
  size, corners, page width; website logos and favicon (Media Library); the theme's own options;
  "Reset to theme default"; "Use for admin panel too"; preview of the saved homepage.
* **Layouts:** for each module page that offers layouts (`$r->layouts('faq.listing', [...])`) one of
  the module's and the active theme's layouts, with items per row and sidebar. A choice the theme cannot
  draw falls back to the module's default.
