# International

Partner institutions, conferences and visitors (M-35, DB-06 v1.4 §6, SRS Services FR-INTL-01..09).

| | |
|---|---|
| Slug | `international` |
| Tables | `international_countries` (ISO code, flag), `international_partners` (model `Institution`; attachable: country, agreement mou / moa / exchange / dual_degree / research / other, signed / expires), `international_conferences` (attachable, page), `international_visitors` (attachable; free-text name, designation, organisation; country; Gallery album id) |
| Taxonomy | `international.conference-category` |
| Settings | `expired` hide / label, `notify_days` |
| Schedule | `international:expiring` daily 06:10 → `international.agreement-expiring` |
| Metrics | `international.partners`, `international.countries` |
| Admin | International › Partners (Import Excel / CSV: Name, Country, Website, Agreement, Signed, Expires), Conferences, Visitors, Countries |
| Legacy | `--step=S18I` — countries (ISO from the name), linkages, featured → highlight, conferences, visitors |

Website: `/international`, `/international/partners` (`?country=`, `?type=`, `?q=`, `?view=grid|table|countries`),
`/international/conferences` (+ `/{slug}`), `/international/visitors`. Homepage section
`international.partners` "Global Partners" carousel.
