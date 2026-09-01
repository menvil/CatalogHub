# Brand organization ownership

## Boundary

Brand, Organization, and ownership are distinct concepts. `CentralBrand` represents a commercial catalog mark. `Organization` represents the legal/corporate identity that can directly own Brands. `CentralBrandOwnership` represents one current direct Parent Company relation. Neither `CentralBrand`, `Site`, an import/price source, nor a free-text value substitutes for Organization identity.

## Persistence and identity

`organizations` contains `id`, `name`, full `normalized_name`, indexed `normalized_name_prefix`, and timestamps. ID is authoritative identity. The normalized form is NFC-normalized, whitespace-collapsed, Unicode case-folded search data and is stored as text because full case folding can expand a valid 255-character display name beyond a fixed 512-character field. The first 191 normalized characters form the portable utf8mb4 index key; this supports deterministic prefix search without rejecting or merging legitimate same-name entities.

`central_brand_ownerships` contains Brand and Organization foreign keys plus timestamps. The unique Brand foreign key enforces at most one current owner. Organization is non-unique, allowing one Organization to own many Brands. Both delete rules restrict while a relation exists; clear/replace never delete Organizations.

## Mutation and concurrency

Ownership writes are domain Actions separate from `UpdateCentralBrandAction`. Assign, replace, and clear lock the route Brand and exact ownership context, then record Audit within the same transaction. The unique Brand key is the final cross-database race invariant. The coordinated database test verifies two concurrent assignments serialize to one row and a deterministic final owner on lock-capable engines.

Create-and-assign opens one outer transaction around Organization creation and the shared assignment Action. An Audit exception rolls back the relation and the new Organization. Same-owner assignment and clearing an empty relation are no-ops without timestamp or Audit noise.

## Read and search

`CentralBrand::ownership()` is the single Brand read relation; callers eager-load `ownership.organization`. CA-013 initial HTML includes at most the current selected Organization. The server-side search query uses the indexed normalized prefix (and verifies the full value for longer queries), stable normalized-name/ID ordering, and a limit of 20. Every option includes the authoritative Organization ID, and `#ID` performs an exact lookup so same-name rows beyond the ordinary name-result cap remain reachable and distinguishable without unbounded directory payloads.

## Cross-domain invariants

Ownership requires `catalog.brands.manage`. It does not affect Brand lifecycle, derived Quality, `TranslationSourceHashService`, translation status/approval, Media, or Site/public publication. Audit actions are `catalog.brand.owner.assigned` and `catalog.brand.owner.cleared`, with only `organization_id` and `organization_name` before/after snapshots on the Brand subject.

Historical or multiple ownership, percentages, beneficial/ultimate parents, Organization hierarchy/registry data, global Organization administration, and Site projections are explicitly deferred.
