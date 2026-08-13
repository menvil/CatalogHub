# Brand domain contract

`CentralBrand` is the global canonical Brand entity in the Central Catalog. It is not owned by a Site, market, or locale.

The persistent fields are:

- `name`: canonical, non-localized brand name;
- `slug`: canonical, database-unique slug;
- `status`: `draft`, `active`, or `archived`;
- `website_url`: optional official brand website;
- `country_code`: optional two-character country code.

Write operations use explicit application actions. Create always produces a `draft` Brand. The allowed lifecycle transitions are `draft` → `active`, `draft` → `archived`, `active` → `archived`, and `archived` → `draft`. Restore returns an archived Brand to `draft` so it can be reviewed before explicit activation. Generic update cannot change status. Brands are not soft-deleted, and the domain has no physical deletion workflow.

On create, `slug` is generated from the normalized name when omitted. A slug is stable when the name changes and can only be changed explicitly. Slug uniqueness is the hard database invariant. Exact normalized, case-insensitive canonical name duplicates are rejected by the application layer, while similar names remain valid and `name` remains non-unique in the database.

Canonical names preserve case, punctuation, and Unicode while trimming and collapsing whitespace. `website_url` is an optional HTTP or HTTPS URL. `country_code` is an optional, structurally validated uppercase two-letter ASCII code.

Localized names, descriptions, and SEO data are not stored on `CentralBrand`. Logo and media data are not stored directly on `CentralBrand`. Site-specific visibility and other site-specific data belong to their respective layers.

A Brand may have many `CentralProduct` records through the existing `products` relationship.

Authorization, audit logging, media, translations, and site-specific configuration are outside this write contract.
