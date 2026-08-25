# Brand domain contract

`CentralBrand` is the global canonical Brand entity in the Central Catalog. It is not owned by a Site, market, or locale.

The persistent fields are:

- `name`: canonical, non-localized brand name;
- `normalized_name`: internal Unicode case-folded canonical-name identity;
- `normalized_name_hash`: internal database-unique SHA-256 identity key;
- `slug`: canonical, database-unique slug;
- `status`: `draft`, `active`, or `archived`;
- `website_url`: optional official brand website;
- `country_code`: optional two-character country code.

Write operations use explicit application actions. Create always produces a `draft` Brand. The allowed lifecycle transitions are `draft` → `active`, `draft` → `archived`, `active` → `archived`, and `archived` → `draft`. Restore returns an archived Brand to `draft` so it can be reviewed before explicit activation. Generic update cannot change status. Brands are not soft-deleted, and the domain has no physical deletion workflow.

On create, `slug` is generated from the normalized name when omitted. A slug is stable when the name changes and can only be changed explicitly. Slug uniqueness is a hard database invariant. Exact normalized, case-insensitive canonical name duplicates are rejected by the application layer and protected against concurrent writes by the unique normalized-name identity hash. The identity uses locale-independent Unicode case folding and NFC normalization so its behavior is consistent across SQLite, MariaDB, and PostgreSQL. It remains accent-sensitive, so `Électro` and `Electro` are distinct. Similar names remain valid and `name` remains non-unique in the database.

Canonical names preserve case, punctuation, and Unicode while trimming and collapsing whitespace. `website_url` is an optional HTTP or HTTPS URL. `country_code` is an optional, structurally validated uppercase two-letter ASCII code.

Localized names, descriptions, and SEO data are not stored on `CentralBrand`. Logo and media data are not stored directly on `CentralBrand`. Site-specific visibility and other site-specific data belong to their respective layers.

A Brand may have many `CentralProduct` records through the existing `products` relationship.

Canonical Brand administration requires `catalog.brands.manage`; Product and global Media Library permissions are independent. Brand translation authoring remains under `translations.manage`.

Create, meaningful canonical update, lifecycle transitions, logo assignment/removal, and meaningful translation saves record transactional, append-only audit events with `CentralBrand` as their common subject. Canonical snapshots exclude normalized identity fields; logo snapshots contain only media asset IDs; translation snapshots contain identifiers, locale, status, and changed field names rather than translated content. Audit is an activity trace, not content version storage.

Site-specific configuration remains outside this write contract.
