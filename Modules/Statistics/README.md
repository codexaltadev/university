# Statistics

Core module C-20 (SRS Part 1). Appearance › Statistics — the screen of the kernel's Statistics engine
(`Platform\Statistics`).

| | |
|---|---|
| Slug | `statistics` |
| Tables | kernel: `core_stat_sets`, `core_stat_items` (DB-02 §11) |
| Permissions | custom: `Manage:Statistic` (own entities), `ManageMain:Statistic` (main site) — BRL-STAT-01 |
| Settings | none |

* Counter sets per owner (main site or entity), several per owner (`key`).
* Counter: label per language, manual number or a module metric counted for the owner and everything below
  it (Engineering = its departments and programs), optional fixed number, rounding (1,200+), prefix / suffix,
  icon, "as of" date; live preview; hidden with a warning when its module is not active.
* Metrics: `$r->metric('faq.count', FaqCount::class)` (a `Platform\Statistics\MetricProvider`); values are
  cached an hour and counted again whenever content changes.
* Website: `app(Statistics::class)->forOwner('site:0')` → label, value, display (in the language's digits), icon.
* Seeder: the main site's default set (Students, Programs, Faculty Members, Alumni).
