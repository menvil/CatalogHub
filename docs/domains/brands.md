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

## Separate and derived data

Localized name overrides, tagline, descriptions, and SEO belong to `BrandTranslation`. Brand translation source hashes remain based only on canonical `name` and `slug`; profile-only changes do not make translations Outdated.

Logo and other media belong to `MediaAsset`/`MediaAssignment`; they are not columns or computed model accessors on `CentralBrand`. A Brand may have many `CentralProduct` records through `products`, but Product, translation, and media counts are derived queries, never persisted Brand fields. Quality/completeness is likewise derived and not implemented in this phase.

## Ownership and future boundaries

Parent Company from the design reference is intentionally not represented as free text. A legal or corporate owner is not necessarily another Brand, so `parent_brand_id` is not a substitute. Ownership requires a future organization/ownership relation before it can be stored or edited.

Tags/classification, external identities/provenance/social links, Site publication/visibility, richer identity palettes/media, richer translation workflow, and quality/completeness rules remain future domains.

## Permissions and audit

Canonical Brand administration requires `catalog.brands.manage`; Product, Media Library, and translation permissions remain independent. Brand translation authoring uses `translations.manage`.

Create, meaningful profile update, lifecycle transitions, logo assignment/removal, and meaningful translation saves record transactional append-only audit events with `CentralBrand` as subject. Canonical create snapshots allow `name`, `slug`, `status`, `website_url`, `country_code`, `founded_year`, `support_url`, `contact_email`, and `primary_color`; update snapshots contain only changed values from the same profile allowlist except status. Public Brand contact email/support URL are accepted canonical audit values under the existing data-minimization policy; no unrelated redaction subsystem is introduced.

Snapshots exclude normalized identities and relations. Country intentionally remains semantic `country_code` alpha-2 in audit even though persistence uses `country_id`, preserving a readable timeline. Logo snapshots contain only media asset IDs, and translation snapshots contain identifiers, locale, status, and changed field names rather than localized content.
