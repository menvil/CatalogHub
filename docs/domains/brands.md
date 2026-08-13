# Brand domain contract

`CentralBrand` is the global canonical Brand entity in the Central Catalog. It is not owned by a Site, market, or locale.

The persistent fields are:

- `name`: canonical, non-localized brand name;
- `slug`: canonical, database-unique slug;
- `status`: `draft`, `active`, or `archived`;
- `website_url`: optional official brand website;
- `country_code`: optional two-character country code.

The lifecycle is `draft` → `active` → `archived`. Brands are not soft-deleted, and the domain has no physical deletion workflow.

Localized names, descriptions, and SEO data are not stored on `CentralBrand`. Logo and media data are not stored directly on `CentralBrand`. Site-specific visibility and other site-specific data belong to their respective layers.

A Brand may have many `CentralProduct` records through the existing `products` relationship.
