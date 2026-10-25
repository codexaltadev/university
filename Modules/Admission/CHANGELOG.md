# Changelog

## Unreleased

- `AdmissionBlocks` presenter for themes (`$admission` in the block views): circulars with each date's status, the
  Apply links, the cards by category, steps, contacts; the sign-up form gets `$channels` and `$whatsappGroup`.
- `/admission/contact` (the office, its map, the contact groups); Settings › Admission gains the office (name,
  address, helpline, email, Google Maps embed) and the result page's links and picture; `AdmissionPage` presenter
  (`$page` on the module's pages). Result messages take `{program}` and `{serial}` and are shown as sanitised HTML;
  the lookup's answer adds the serial, program and a tone.

## 1.0.0

- First version.
