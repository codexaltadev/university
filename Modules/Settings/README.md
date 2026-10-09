# Settings

Core module C-05 (SRS Part 1). Site › Settings: one tab per settings group.

| | |
|---|---|
| Slug | `settings` |
| Tables | kernel: `core_settings` (DB-01 §7) |
| Permissions | custom: `View:Settings`, `Update:Settings`, `Advanced:Settings` (mail, maintenance); custom code tab: Super Admin only |
| Settings | general, contact, social, media, modules, mail, maintenance, code, admin_branding |

Read anywhere: `settings('contact.phone')` — translatable values come back in the current language.
A module adds its own tab with `$r->settings(FaqSettings::class)` (a `Platform\Settings\ModuleSettings`);
the tab disappears while the module is inactive. Saving clears the group's cache (FR-SET-12) and fires
`Platform\Events\SettingsUpdated`; every change is in the activity log (secrets masked).

Not yet: default images, admin logos / favicon / login background (with the Media Library, Phase 2.5) and
"copy website branding" (with Theme Management).

## Views

`settings::maintenance` — `$siteName`, `$message` (shown to visitors while maintenance is on).
