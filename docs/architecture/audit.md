# Audit architecture

CatalogHub uses the existing append-only `AuditLogEntry` model and `AuditRecorder`. Entries identify actor, presentation context, optional site, action, subject, request ID, creation time, and action-specific before/after metadata. Model and database guards prohibit updates and deletes.

Administrative mutations write their domain state and audit entry in one database transaction. If audit storage fails, the mutation rolls back. Authentication framework events are the documented best-effort exception; Brand actions are not. HTTP correlation uses the existing request ID mechanism.

`AuditRecorder` applies an action-specific snapshot allowlist. Audit payloads must contain enough information to identify who changed what and the meaningful before/after state, without arbitrary model serialization, internal hashes, storage paths, secrets, binary metadata, or long translated text.

## Brand activity contract

Phase 19.1 implements Category core and schema lifecycle/invalidation audit through the existing AuditAction/AuditRecorder architecture. Its [foundation contract](category-foundation.md) records transaction, snapshot, no-op and rollback behavior. [ADR-0003's registry](adr/0003-categories-schema-ownership.md#required-audit-registry-future-implementation) retains reserved future per-entity/config/translation events; those are not implemented prematurely.

All Brand events use `CentralBrand` as the subject, `central` context for Central Admin requests, and a null site. The registry is:

- `catalog.brand.created`: `name`, `slug`, `status`, `website_url`, semantic `country_code`, `founded_year`, `support_url`, `contact_email`, and `primary_color`;
- `catalog.brand.updated`: changed values only from `name`, `slug`, `website_url`, semantic `country_code`, `founded_year`, `support_url`, `contact_email`, and `primary_color`;
- `catalog.brand.tags.updated`: deterministic human-readable Tag names in `before_json: {"tags": [...]}` and `after_json: {"tags": [...]}`;
- `catalog.brand.external_identity.linked`: `before_json: null` and safe semantic `source_code`, `external_id`, and optional `external_url` after state;
- `catalog.brand.external_identity.updated`: changed semantic fields only, with `source_code` retained as context in before/after;
- `catalog.brand.external_identity.unlinked`: safe semantic identity before state and `after_json: null`;
- `catalog.brand.activated`, `catalog.brand.archived`, `catalog.brand.restored`: status only;
- `catalog.brand.logo.assigned`, `catalog.brand.logo.removed`: media asset ID only;
- `catalog.brand.translation.saved`: translation ID, locale, status, and changed field names only.

No-op updates, reordered/casing-only identical Tag sets, idempotent external-identity links, unchanged identity updates, idempotent lifecycle commands, unchanged logo assignment/removal, and identical translation saves produce no entry. One Save Tags intent produces at most one Brand event; implicit global vocabulary creation does not emit `tag.created` or per-chip events. Tag mutations and each external-identity link/update/unlink share their transaction with the audit write, so audit failure rolls the business mutation back. The generic `(subject_type, subject_id, created_at)` index supports a future subject activity stream.

Audit metadata is a mutation/activity trace, not full content version storage. Activity/Versions UI, diffs, rollback, and any dedicated version model are deferred until a concrete presentation or recovery use case requires them.

Brand `support_url` and `contact_email` are public canonical profile metadata and are permitted in the existing Brand snapshot allowlist. They are not user identities, notification destinations, credentials, or secrets. Normalized name/hash fields, Country models/translations, Media, BrandTranslation content, derived counts, and quality values remain excluded.

Brand Country persistence is an intentional schema/event exception: `central_brands.country_id` is the relational FK, while Brand create/update snapshots retain `country_code` with the resolved Country alpha-2 value. Thus a move from South Korea to Japan is recorded as `KR` → `JP`, clearing as `KR` → `null`, and no event contains opaque Country IDs, translations, or geography metadata. This keeps pre- and post-Phase 9 Brand history coherent and human-readable.

External-identity audit is intentionally Brand-centric: the subject is `CentralBrand`, never the identity row or `ImportSource`. Snapshots exclude `central_brand_external_identity_id`, Brand/source database IDs, `external_id_hash`, source description, and `config_json`; they never serialize an ImportSource model. Human linkage history is not import observation history.

Derived Category coverage is not a Brand mutation and is not Brand-audited. Product category/status changes retain their Product-domain history without cascading `Brand categories changed` noise.

## Phase 19.1 Category activity contract

Implemented events: `catalog.category.created`, `.updated`, `.reparented`, `.reordered`, `.activated`, `.archived`, `.restored`; and `catalog.category.schema.reviewed`, `.approved`, `.archived`, `.restored`, `.invalidated`.

Core snapshots are event-specific: creation uses Category/source identity, parent reference, position/status and affected scope revision; identity update uses changed name/slug fields; reparent/order uses parent reference, deterministic ordered IDs and old/new scope revisions; lifecycle uses status only. Category is the subject except sibling reorder, whose stable hierarchy-scope subject also represents root without inventing a Category. Central context always has null Site.

Schema snapshots use Category identity, schema status/revision, reviewed/approved revisions and (invalidation only) bounded enum reason/origin entity identity. Actor/time are recorded by the audit entry and attributed state, not invented for legacy rows. Semantic invalidation emits exactly one event even when the schema is already Draft. No-op/GET/rejected actions emit none. Domain writes and audit share one transaction; tests inject AuditRecorder failures to prove rollback of create/identity/order/reparent/lifecycle/schema changes. Arbitrary models, template/config JSON, Product values and translated bodies are excluded.

## Phase 19.2 Attribute and assignment activity

New backend actions record `catalog.attribute.created`, `catalog.attribute.updated`, and `catalog.category.attribute.assigned`, `.configured`, `.moved`, `.unassigned`. Global definition is the canonical subject; Category is the assignment subject. Allowlisted snapshots contain stable definition/assignment/Category/Section IDs plus codes/reference names, type/dimension/unit, local position and four booleans, changed field names and affected Category count. Shared edits emit one bounded canonical event and one Phase 19.1 schema invalidation per affected Category. No Product facts, draft JSON, translated bodies or arbitrary config enter audit. Transactions include every mutation/revision; no-op/rejected/GET operations emit nothing and audit failure rolls all back. See the [19.2 contract](attribute-globalization-foundation.md). Option/retirement events are not newly implemented by this phase.

## Phase 19.3 authoritative backend activity

Implemented events are `catalog.attribute.option.created/updated`, `catalog.category.facet.created/updated/removed`, `catalog.category.comparison.updated`, and `catalog.attribute.display_rule.configured`, alongside existing Category schema invalidation. Exact AuditAction/AuditRecorder allowlists are authoritative. No-op/rejected mutations emit nothing; audit failure rolls back mutations and every affected revision. Technical rebuilds use existing ProjectionJob/ProjectionLog records. Pre-launch legacy reconciliation and its event have been removed; no migration receipt or epoch is an audit authority. See [the final contract](schema-consumer-convergence.md).
