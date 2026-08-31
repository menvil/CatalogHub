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

## Organization ownership

`CentralBrand` and `Organization` are separate canonical concepts. A Brand is a commercial/catalog identity; an Organization is the legal or corporate entity that may directly own it. Phase 16 persists Organization identity in `organizations` with `id`, display `name`, non-unique indexed `normalized_name`, and timestamps. The internal database ID is authoritative. Normalization supports deterministic prefix search and duplicate hints, but same-name Organizations remain valid distinct records; there is no automatic merge or global name uniqueness.

The current direct Parent Company relation is `central_brand_ownerships`. Its Brand foreign key is unique, so one Brand has at most one current owner, while one Organization can own many Brands. Both foreign keys restrict physical deletion. Clearing or replacing ownership removes/changes only the relation and retains every Organization. There is no free-text owner column, `parent_brand_id`, ownership history, percentage, Organization hierarchy, or multiple-current-owner model.

`CentralBrand::ownership()` is the authoritative read relation, with the related `Organization` loaded through it. CA-013 owns the bounded write workflow: assign an existing Organization from indexed server-side search, create a distinct Organization and assign it atomically, replace, or clear. Create Brand remains the scalar Draft workflow; ownership becomes available only after a Brand exists. The scalar `CentralBrandInput`/`UpdateCentralBrandAction` boundary does not accept an Organization ID.

Assign, replace, create-and-assign, and clear lock the Brand and exact ownership context, mutate and audit in one transaction, and emit no write or Audit event for a same-owner no-op. Database uniqueness remains the final concurrent invariant. Ownership uses `catalog.brands.manage`; no standalone Organization CRUD or `organizations.manage` permission exists.

Ownership is contextual metadata, not quality. It does not change Draft/Active/Archived lifecycle, activation/archive/restore eligibility, translation source hashes/status/approval, Media, or Site/public projection state. Phase 17 may consume this relation as read-only CA-012 context without defining another owner model.

## Localized, media, classification, and provenance data

Localized name overrides, tagline, short/long descriptions, SEO title, and SEO description belong only to `BrandTranslation`. The shared row status is `Missing`, `MachineTranslated`, `HumanReviewed`, `Approved`, or `Outdated`, with source hash and server-owned approval actor/time metadata. Brand translation source hashes remain based only on canonical `name` and `slug`; profile-only changes do not make translations Outdated. CA-015 v2 exposes canonical name as the only copyable Source/Target pair and shows slug solely as source-hash context—there is no fabricated source Locale or canonical tagline/description/SEO copy.

Logo and other media belong to `MediaAsset`/`MediaAssignment`; they are not columns or computed model accessors on `CentralBrand`. A Brand may have many `CentralProduct` records through `products`, but Product, translation, and media counts are derived queries, never persisted Brand fields.

Phase 14 confirms only the global canonical `brand_logo` role. Its exact assignment is `central_brand` + Brand ID + `brand_logo`, null locale/site/market, global visibility, and primary status. `CentralBrandMediaQuery` owns that selection for CA-012, CA-014, and derived quality. CA-014 mutates the relation through transactionally locked Actions; upload uses Shared Media ingest, bounded reuse additionally requires `media.manage`, and replace/remove never physically delete a shared asset. Localized, Site, and market media remain future concerns even though the generic assignment model can represent those contexts.

## Derived quality and completeness

`CentralBrandQualityEvaluator` is the authoritative Brand quality contract. Quality is computed on read from canonical profile fields, the exact global primary `brand_logo` assignment and usable Shared Media delivery, plus one check for each active Locale's `BrandTranslation`. It is never stored on `central_brands`, cached in JSON, timestamped, recalculated by a job, or written to Audit.

The equally weighted base checks are country, website, founded year, support URL **or** contact email, primary color, and usable global primary logo. Each active Locale adds one equally weighted translation check; inactive Locales add none. An absent translation or shared `Missing` status is missing, `Outdated` requires attention, and only `MachineTranslated`, `HumanReviewed`, or `Approved` is complete. The integer score is `round(completed applicable checks / total applicable checks × 100, PHP_ROUND_HALF_UP)`. See `docs/architecture/brand-quality.md` for stable issue codes and exact destinations.

The derived states are only Complete and Needs attention. They do not extend or constrain Draft/Active/Archived lifecycle, do not describe Site publication/projection, and cannot be changed directly. Tags, Product usage, Category coverage, external identities, Parent Company, and Site visibility are context rather than completeness requirements.

Explicit editorial classification uses the global `CatalogTag` vocabulary and the explicit `central_brand_tag` pivot. `catalog_tags` contains `id`, the first successfully created canonical administrative `name`, internal `normalized_name`, the unique 64-character SHA-256 `normalized_name_hash`, and timestamps. A Tag name is NFC-normalized, trimmed, whitespace-collapsed, and Unicode case-folded for identity, so `Premium`, `premium`, and ` PREMIUM ` reuse one row without changing its stored display casing. Labels are nonblank, contain no control characters/newlines, are at most 80 characters, and a Brand may have at most 20 assignments. Unused vocabulary rows are retained.

Tags deliberately have no slug, status/lifecycle, translations, colors, descriptions, hierarchy, public routes, Product/Category relation, or global CRUD screen. Brand classification is the only Phase 11 consumer, through the explicit `CentralBrand::tags()` relation. There is no polymorphic `taggables` table; future consumers may receive their own explicit FK-backed pivot when a real contract exists.

Category coverage is derived classification, never editorial Brand data. `CentralBrandCategoryCoverageQuery` groups `central_products` by their direct `central_category_id` for the selected Brand, includes Draft and Active Products, excludes Archived Products and null category assignments, and counts exact-category Products. Category status does not filter the result: a Draft or Archived Category remains visible when a current Product references it. Results sort by product count descending, then Category name ascending, then ID ascending. There is no ancestor roll-up, fake Uncategorized category, persisted cache, sync job, JSON field, `brand_category`/`central_brand_category` pivot, or `CentralBrand::categories()` relation. Product recategorization or archival therefore changes coverage automatically.

External identities are entity-level provenance links in `central_brand_external_identities`, not canonical Brand fields. Each link associates the Brand with an existing `ImportSource` and an opaque, case-sensitive source record ID. Surrounding whitespace is trimmed, but casing, internal whitespace, and leading zeroes are preserved. Cross-database uniqueness is enforced by `(import_source_id, SHA-256(exact normalized external ID))`, with an exact stored-value comparison defending against hash collisions. An optional external record URL must be an absolute HTTP(S) URL without credentials. Source configuration is never presentation or audit data.

The eight Brand concerns remain intentionally separate:

- canonical profile: scalar `central_brands` fields;
- localized content: `BrandTranslation`;
- media: `MediaAssignment` to `MediaAsset`;
- explicit classification: `CatalogTag` assignments;
- derived classification: current Product direct-Category coverage.
- external provenance: `CentralBrandExternalIdentity` links owned by `ImportSource` namespaces.
- corporate ownership: the current `CentralBrandOwnership` relation to a canonical `Organization`.
- derived quality: a non-persisted projection over canonical profile, Shared Media, and active-locale Translation data.

## Ownership and future boundaries

Parent Company is represented only by the current direct Organization relation described above. Historical ownership, multiple concurrent owners, percentages, beneficial/ultimate ownership, mergers/acquisitions, Organization hierarchy, registries, Organization translations/media/lifecycle, a global Organization administration screen, and Site-specific/public owner projection remain future concerns.

Field-level provenance or persisted field workflow, source observation/content history, automatic canonical overwrite, AI/machine-translation providers, translation memory/glossaries, fuzzy matching, social links, public/Site localized delivery, fallback, translated slugs, additional Brand media roles, localized/site/market media, richer identity palettes, global Tag vocabulary management, and final Brand UI convergence remain future domains.

## Permissions and audit

Canonical Brand administration requires `catalog.brands.manage`; Product, Media Library, and translation permissions remain independent. Brand translation authoring uses `translations.manage`.

Create, meaningful profile update, lifecycle transitions, logo assignment/removal, Tag synchronization, external-identity link/update/unlink, ownership assign/replace/clear, meaningful translation saves, explicit translation approval, and explicit Mark Outdated record transactional append-only audit events with `CentralBrand` as subject. Canonical create snapshots allow `name`, `slug`, `status`, `website_url`, `country_code`, `founded_year`, `support_url`, `contact_email`, and `primary_color`; update snapshots contain only changed values from the same profile allowlist except status. Tag synchronization records exactly one `catalog.brand.tags.updated` event with deterministic human-readable `{"tags": [...]}` before/after sets; reordering/casing-only no-ops record nothing, and audit failure rolls back assignments and vocabulary rows created by that transaction. External-identity snapshots contain only semantic `source_code`, `external_id`, and safe `external_url`; internal IDs, hashes, and `ImportSource.config_json` are excluded. Ownership uses `catalog.brand.owner.assigned` for assign/replace and `catalog.brand.owner.cleared` for clear, with only semantic `organization_id`/`organization_name` before and after; no-op writes nothing and audit failure rolls back the relation and any Organization created by that bounded operation. Public Brand contact email/support URL are accepted canonical audit values under the existing data-minimization policy; no unrelated redaction subsystem is introduced.

Snapshots exclude normalized identities and relation metadata. Country intentionally remains semantic `country_code` alpha-2 in audit even though persistence uses `country_id`, preserving a readable timeline. Logo snapshots contain only MediaAsset IDs and the semantic `brand_logo` role; repairing a non-canonical assignment records a null prior MediaAsset ID, and no-op assignment records nothing. Translation snapshots contain identifiers, locale, old/new row status, and changed field names rather than localized content. CA-015 recent activity is a bounded locale-specific projection of this existing log, not a translation-history table. True no-op Save/Approve/Mark Outdated emits nothing, and Audit failure rolls back its mutation. Derived Category coverage and quality calculation do not emit cascading Brand audit events. Tags and external identities do not participate in the Brand translation source hash.
