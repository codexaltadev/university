# Accreditation & Ranking

Accreditations and rankings (M-34, DB-06 v1.4 §5, SRS Services FR-ACR-01..06).

| | |
|---|---|
| Slug | `accreditation` |
| Tables | `accreditation_accreditations` (attachable: title, agency, body, website, awarded / expires), `accreditation_rankings` (attachable: `agency_key` — the same for every year of one ranking, year, scope overall / subject / region, `subject_key`, `rank_label`, `rank_value`, summary, body, link) |
| Media | accreditation `logo`, `certificate`; ranking `logo`, `badge` |
| Taxonomy | `accreditation.category` |
| Settings | `expired` hide / label, `notify_days` (60) |
| Schedule | `accreditation:expiring` daily 06:00 → notification `accreditation.expiring` |
| Admin | Services › Accreditations, Rankings (**Next year** copies a result) |
| Legacy | `--step=S18R` — accreditations → program (+ department / faculty), rankings → rank read from the text (review) |

Website: `/accreditations` (by category), `/accreditations/{slug}`, `/rankings` (each result's latest
year, then each agency's years). Program tab "Accreditation". Homepage section
`accreditation.recognition` "Rankings & Recognition" (rankings / accreditations / both; strip or cards).
