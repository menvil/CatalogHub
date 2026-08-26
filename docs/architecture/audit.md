# Audit architecture

CatalogHub uses the existing append-only `AuditLogEntry` model and `AuditRecorder`. Entries identify actor, presentation context, optional site, action, subject, request ID, creation time, and action-specific before/after metadata. Model and database guards prohibit updates and deletes.

Administrative mutations write their domain state and audit entry in one database transaction. If audit storage fails, the mutation rolls back. Authentication framework events are the documented best-effort exception; Brand actions are not. HTTP correlation uses the existing request ID mechanism.

`AuditRecorder` applies an action-specific snapshot allowlist. Audit payloads must contain enough information to identify who changed what and the meaningful before/after state, without arbitrary model serialization, internal hashes, storage paths, secrets, binary metadata, or long translated text.

## Brand activity contract

All Brand events use `CentralBrand` as the subject, `central` context for Central Admin requests, and a null site. The registry is:

- `catalog.brand.created`: `name`, `slug`, `status`, `website_url`, semantic `country_code`, `founded_year`, `support_url`, `contact_email`, and `primary_color`;
- `catalog.brand.updated`: changed values only from `name`, `slug`, `website_url`, semantic `country_code`, `founded_year`, `support_url`, `contact_email`, and `primary_color`;
- `catalog.brand.tags.updated`: deterministic human-readable Tag names in `before_json: {"tags": [...]}` and `after_json: {"tags": [...]}`;
- `catalog.brand.activated`, `catalog.brand.archived`, `catalog.brand.restored`: status only;
- `catalog.brand.logo.assigned`, `catalog.brand.logo.removed`: media asset ID only;
- `catalog.brand.translation.saved`: translation ID, locale, status, and changed field names only.

No-op updates, reordered/casing-only identical Tag sets, idempotent lifecycle commands, unchanged logo assignment/removal, and identical translation saves produce no entry. One Save Tags intent produces at most one Brand event; implicit global vocabulary creation does not emit `tag.created` or per-chip events. The Tag pivot mutation, any new vocabulary rows, and audit entry share one transaction, so audit failure rolls everything back. The generic `(subject_type, subject_id, created_at)` index supports a future subject activity stream.

Audit metadata is a mutation/activity trace, not full content version storage. Activity/Versions UI, diffs, rollback, and any dedicated version model are deferred until a concrete presentation or recovery use case requires them.

Brand `support_url` and `contact_email` are public canonical profile metadata and are permitted in the existing Brand snapshot allowlist. They are not user identities, notification destinations, credentials, or secrets. Normalized name/hash fields, Country models/translations, Media, BrandTranslation content, derived counts, and quality values remain excluded.

Brand Country persistence is an intentional schema/event exception: `central_brands.country_id` is the relational FK, while Brand create/update snapshots retain `country_code` with the resolved Country alpha-2 value. Thus a move from South Korea to Japan is recorded as `KR` → `JP`, clearing as `KR` → `null`, and no event contains opaque Country IDs, translations, or geography metadata. This keeps pre- and post-Phase 9 Brand history coherent and human-readable.

Derived Category coverage is not a Brand mutation and is not Brand-audited. Product category/status changes retain their Product-domain history without cascading `Brand categories changed` noise.
