# Audit architecture

CatalogHub uses the existing append-only `AuditLogEntry` model and `AuditRecorder`. Entries identify actor, presentation context, optional site, action, subject, request ID, creation time, and action-specific before/after metadata. Model and database guards prohibit updates and deletes.

Administrative mutations write their domain state and audit entry in one database transaction. If audit storage fails, the mutation rolls back. Authentication framework events are the documented best-effort exception; Brand actions are not. HTTP correlation uses the existing request ID mechanism.

`AuditRecorder` applies an action-specific snapshot allowlist. Audit payloads must contain enough information to identify who changed what and the meaningful before/after state, without arbitrary model serialization, internal hashes, storage paths, secrets, binary metadata, or long translated text.

## Brand activity contract

All Brand events use `CentralBrand` as the subject, `central` context for Central Admin requests, and a null site. The registry is:

- `catalog.brand.created`: canonical create fields;
- `catalog.brand.updated`: changed canonical fields only;
- `catalog.brand.activated`, `catalog.brand.archived`, `catalog.brand.restored`: status only;
- `catalog.brand.logo.assigned`, `catalog.brand.logo.removed`: media asset ID only;
- `catalog.brand.translation.saved`: translation ID, locale, status, and changed field names only.

No-op updates, idempotent lifecycle commands, unchanged logo assignment/removal, and identical translation saves produce no entry. The generic `(subject_type, subject_id, created_at)` index supports a future subject activity stream.

Audit metadata is a mutation/activity trace, not full content version storage. Activity/Versions UI, diffs, rollback, and any dedicated version model are deferred until a concrete presentation or recovery use case requires them.
