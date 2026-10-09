# Localization

Core module C-15 (SRS Part 1). Website languages and admin overrides of interface texts.

| | |
|---|---|
| Slug | `localization` |
| Tables | kernel: `core_languages`, `core_translations` (DB-01 §11) |
| Permissions | `ViewAny / View / Create / Update / Delete / Reorder:Language`; custom: `Update:InterfaceText` |
| Settings | none |

* **Site › Languages** — add, activate, order, make default. The default language can be neither
  deactivated nor deleted (BRL-LANG-01); deactivating keeps every translation (BRL-LANG-02).
* **Site › Interface texts** — every `frontend` group string (`lang/{locale}/frontend.php`, and each
  module's `lang/{locale}/frontend.php`) can be changed per language; empty = the built-in text (FR-LANG-03).
* Admin panel language comes from the user's profile (`users.locale`, `SetAdminLocale`).

Kernel side (`platform/Localization`): `Languages` (cached list, default, fallback), `DatabaseTranslationLoader`,
`Numerals::localize()` (FR-LANG-08), form component `Platform\Filament\Components\TranslatableTabs` (FR-LANG-02).
Language switcher, `/bn/` URLs and browser-language redirect (FR-LANG-04/06) come with the website theme.
