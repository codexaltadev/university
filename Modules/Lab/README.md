# Lab

Labs and research centres (M-32, DB-06 v1.4 §3, SRS Services FR-LAB-01..11).

| | |
|---|---|
| Slug | `lab` |
| Tables | `lab_labs` (microsite entity, relation target, assignable `in_charge` / `team`, `Manage:Lab#{id}`; department, location, summary, body, equipment summary, contact, video, website), `lab_items` (equipment / scope / project / product; quantity, students; project & product pages) |
| Media | lab `logo`, `cover`; item `image` |
| Taxonomy | `lab.type`; `lab.equipment-category`, `lab.scope-category`, `lab.project-category`, `lab.product-category` (shared by all labs) |
| Metrics | `lab.count`, `lab.equipment`, `lab.projects`, `lab.products` (a new lab gets a counter set) |
| Admin | Services › Labs — form, People, then Scopes, Equipment, Projects, Products |
| Legacy | `--step=S18L` — labs under their department, in-charge person, items + categories, articles → the lab's pages; the rest listed for review |

Website: microsite `/{faculty}/{department}/lab/{slug}` (no department: `/lab/{slug}`) — home
`lab::microsite.home` until the lab builds its own homepage; `…/equipment`, `…/scopes`,
`…/projects` (+ `/{item}`), `…/products` (+ `/{item}`). `/labs` and a department's `/labs`
(`?type=`, `?department=`, `?q=`; grid / list / cards). News, events, gallery, slider, partners:
"Show in" the lab in those modules.
