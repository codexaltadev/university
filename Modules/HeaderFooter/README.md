# Header & Footer

Core module C-09 (SRS Part 1). Appearance › Header & Footer.

| | |
|---|---|
| Slug | `header-footer` |
| Tables | `header_footer_regions` (DB-02 §6) |
| Permissions | custom: `Manage:HeaderFooter` (own entities), `ManageMain:HeaderFooter` (main site) |
| Settings | none; phone, email and social links come from Settings › Contact / Social |

* Regions: top bar (phone, email, quick links, language switch, social icons, login link), header (search,
  sticky, announcement / notice ticker slot), footer (up to 6 columns of blocks: about text, menu, contact,
  social, newsletter form, custom HTML — Super Admin only) and the copyright line with `{year}`.
* Variants come from `config('headerfooter.variants')`; the theme module will provide its own.
* Fallback per region (FR-HF-05): own → parent … → main site; "create own" and "copy … and edit".
* Website: `app(Regions::class)->forContext('footer', $context)` → variant, config, copyright, from.
