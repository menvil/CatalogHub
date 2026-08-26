# Brand domain contract

`CentralBrand` is the global, canonical, language-neutral Brand entity in the Central Catalog. It is not owned by a Site, market, or locale.

## Canonical profile

The persistent canonical fields are:

- `name`: canonical, non-localized Brand name;
- `normalized_name` and `normalized_name_hash`: internal Unicode case-folded identity and its unique SHA-256 key;
- `slug`: canonical, database-unique identifier;
- `status`: `draft`, `active`, or `archived`;
- `website_url`: optional official primary HTTP(S) website;
- `country_id`: optional FK to the global `countries` reference table;
- `founded_year`: optional establishment year from 1000 through the current year;
- `support_url`: optional official customer/support HTTP(S) page;
- `contact_email`: optional public Brand contact/support email, at most 254 characters;
- `primary_color`: optional canonical uppercase `#RRGGBB` identity color.

Create always produces Draft. Allowed lifecycle transitions are Draft → Active, Draft → Archived, Active → Archived, and Archived → Draft. Generic profile update cannot change status. Brands are not soft-deleted and have no physical deletion workflow.

On create, `slug` is generated from the normalized name when omitted. A slug remains stable when the name changes unless explicitly changed. Exact normalized canonical-name duplicates are rejected by the application and protected against races by the unique normalized-name hash. The identity uses locale-independent Unicode case folding and NFC normalization consistently across SQLite, MariaDB, and PostgreSQL, while remaining accent-sensitive.

Canonical names preserve case, punctuation, and Unicode while trimming/collapsing whitespace. Website and Support URL accept only absolute HTTP(S) URLs. Contact email trims surrounding whitespace without changing local-part case and performs no DNS/network verification. Primary color accepts case-insensitive six-digit hex input with a leading `#` and persists uppercase. Founded stores a year, never a fabricated date.

Country is persisted by `country_id`; no free-form Brand country code exists. New assignments require an active Country. If an assigned Country later becomes inactive, omission or retention remains valid, while the Brand may clear it or switch to another active Country.

Profile writes keep the typed `CentralBrandInput` boundary. Optional fields use presence semantics: omitted retains the locked current value, explicit blank/null clears, and a submitted value is normalized and validated. Create/update mutation and audit remain one transaction, and no-op updates emit no `BrandUpdated` audit entry.

## Localized, media, and classification data

Localized name overrides, tagline, descriptions, and SEO belong to `BrandTranslation`. Brand translation source hashes remain based only on canonical `name` and `slug`; profile-only changes do not make translations Outdated.

Logo and other media belong to `MediaAsset`/`MediaAssignment`; they are not columns or computed model accessors on `CentralBrand`. A Brand may have many `CentralProduct` records through `products`, but Product, translation, and media counts are derived queries, never persisted Brand fields. Quality/completeness is likewise derived and not implemented in this phase.

Explicit editorial classification uses the global `CatalogTag` vocabulary and the explicit `central_brand_tag` pivot. `catalog_tags` contains `id`, the first successfully created canonical administrative `name`, internal `normalized_name`, the unique 64-character SHA-256 `normalized_name_hash`, and timestamps. A Tag name is NFC-normalized, trimmed, whitespace-collapsed, and Unicode case-folded for identity, so `Premium`, `premium`, and ` PREMIUM ` reuse one row without changing its stored display casing. Labels are nonblank, contain no control characters/newlines, are at most 80 characters, and a Brand may have at most 20 assignments. Unused vocabulary rows are retained.

Tags deliberately have no slug, status/lifecycle, translations, colors, descriptions, hierarchy, public routes, Product/Category relation, or global CRUD screen. Brand classification is the only Phase 11 consumer, through the explicit `CentralBrand::tags()` relation. There is no polymorphic `taggables` table; future consumers may receive their own explicit FK-backed pivot when a real contract exists.

Category coverage is derived classification, never editorial Brand data. `CentralBrandCategoryCoverageQuery` groups `central_products` by their direct `central_category_id` for the selected Brand, includes Draft and Active Products, excludes Archived Products and null category assignments, and counts exact-category Products. Category status does not filter the result: a Draft or Archived Category remains visible when a current Product references it. Results sort by product count descending, then Category name ascending, then ID ascending. There is no ancestor roll-up, fake Uncategorized category, persisted cache, sync job, JSON field, `brand_category`/`central_brand_category` pivot, or `CentralBrand::categories()` relation. Product recategorization or archival therefore changes coverage automatically.

The five Brand concerns remain intentionally separate:

- canonical profile: scalar `central_brands` fields;
- localized content: `BrandTranslation`;
- media: `MediaAssignment` to `MediaAsset`;
- explicit classification: `CatalogTag` assignments;
- derived classification: current Product direct-Category coverage.

## Ownership and future boundaries

Parent Company from the design reference is intentionally not represented as free text. A legal or corporate owner is not necessarily another Brand, so `parent_brand_id` is not a substitute. Ownership requires a future organization/ownership relation before it can be stored or edited.

External identities/provenance/social links, Site publication/visibility, richer identity palettes/media, richer translation workflow, global Tag vocabulary management, and quality/completeness rules remain future domains.

## Permissions and audit

Canonical Brand administration requires `catalog.brands.manage`; Product, Media Library, and translation permissions remain independent. Brand translation authoring uses `translations.manage`.

Create, meaningful profile update, lifecycle transitions, logo assignment/removal, Tag synchronization, and meaningful translation saves record transactional append-only audit events with `CentralBrand` as subject. Canonical create snapshots allow `name`, `slug`, `status`, `website_url`, `country_code`, `founded_year`, `support_url`, `contact_email`, and `primary_color`; update snapshots contain only changed values from the same profile allowlist except status. Tag synchronization records exactly one `catalog.brand.tags.updated` event with deterministic human-readable `{"tags": [...]}` before/after sets; reordering/casing-only no-ops record nothing, and audit failure rolls back assignments and vocabulary rows created by that transaction. Public Brand contact email/support URL are accepted canonical audit values under the existing data-minimization policy; no unrelated redaction subsystem is introduced.

Snapshots exclude normalized identities and relation metadata. Country intentionally remains semantic `country_code` alpha-2 in audit even though persistence uses `country_id`, preserving a readable timeline. Logo snapshots contain only media asset IDs, and translation snapshots contain identifiers, locale, status, and changed field names rather than localized content. Derived Category coverage changes are Product-domain history and do not emit cascading Brand audit events.
