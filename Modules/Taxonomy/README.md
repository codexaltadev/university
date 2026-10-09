# Taxonomy

Core module C-16 (SRS Part 1). Site › Categories & Tags.

| | |
|---|---|
| Slug | `taxonomy` |
| Tables | kernel: `core_vocabularies`, `core_terms`, `core_termables` (DB-02 §4) |
| Permissions | custom: `Manage:Taxonomy` |
| Settings | none |

* A module registers its vocabulary in its Extension:
  `$r->vocabulary('faq.category', label: 'FAQ category', hierarchical: true, multiple: false)`
  and declares `"vocabularies": ["faq.category"]` in `provides`. The kernel adds the shared `shared.tags`.
* Model: `use Platform\Concerns\HasTaxonomy;` — form: `TermSelect::make('category')->vocabulary('faq.category')`.
  In a flat, multi-choice vocabulary (tags) typing a new name creates the tag.
* Seeder: `Vocabularies::ensureTerms('faq.category', [['name' => ['en' => 'General', 'bn' => 'সাধারণ']]])`.
* Here: rename a vocabulary; add, nest, reorder, deactivate, delete and restore terms. Deleting a used term
  shows how many items use it and takes it off them (BRL-TAX-01); restoring brings it back.
* Not yet: term archive pages (`/news/category/…`, FR-TAX-05) with the website theme; term merge (FR-TAX-06, Could).
