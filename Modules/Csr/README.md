# CSR

Social responsibility activities (M-36, DB-06 v1.4 §7, SRS Services FR-CSR-01..04).

| | |
|---|---|
| Slug | `csr` |
| Tables | `csr_activities` (attachable: title, summary, body, date, video, link) |
| Media | `image`, `gallery`; each category's picture = a Media Library use of the term (field `csr_image`) |
| Taxonomy | `csr.category` (hierarchical — sub categories) |
| Admin | Services › CSR; Services › CSR category pictures |
| Legacy | `--step=S18C` — categories with parent and description, activities in their category, `is_featured` → highlight |

Website: `/csr` — the top categories as cards (picture, description) or, without categories, the
activities; `/csr/category/{slug}` — its sub categories and its activities (sub categories
included); `/csr/{slug}`. Cards / grid / timeline (`csr.listing`, `?view=`). Also under a microsite.
Homepage section `csr.list` "Social Responsibility".

## Views

| View | Variables |
|---|---|
| `csr::index` | `$category`, `$children` (term, image), `$activities` (paginator or null), `$layout`, `$base` |
| `csr::show` | `$activity`, `$gallery`, `$base` |
| `csr::partials.card` | `$activity`, `$base`, `$layout` |
| `csr::sections.list` | `$items`, `$section`, `$settings` |
