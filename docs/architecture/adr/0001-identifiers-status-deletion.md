# ADR-0001 — Identifier, status, and deletion conventions

**Status:** Accepted  
**Date:** 2026-08-11  
**Owners:** Platform Engineering<br>
**Task:** P00-097

## Context

Section 0 foundation storage already uses database-generated numeric primary keys, stable site and market codes, public slugs where a public resource needs one, typed lifecycle enums, and one soft-deleted aggregate (`Site`). Replacing existing identifiers or adding soft deletes broadly would require a migration of unrelated business sections.

## Decision

- New relational foundation records use an auto-incrementing integer primary key unless a cross-system identifier is explicitly required. UUIDs are additive external identifiers; they do not replace an established relational key without an ADR.
- `code` is a stable machine identifier, scoped by its database uniqueness constraint. It is not a user-facing URL contract. `slug` is a human-readable URL identifier and is used only by resources that expose a public route.
- Uniqueness remains enforced by the database. A soft-deleted row continues to reserve a unique `code` or `slug` unless a future migration explicitly changes that index and documents restoration behaviour.
- Lifecycle status is a backed enum for new Section 0 models when it controls availability or authorization. Boolean fields are reserved for orthogonal flags such as `is_active`, `is_primary`, `is_default`, and `is_enabled`.
- `Site` is the only Section 0 lifecycle aggregate with soft deletion. Its dependent domains, locale rows, and memberships are hard deleted through their explicit foreign-key policy. Audit rows are append-only and are never soft deleted.

## Consequences

New foundation code must not introduce client-generated IDs, raw lifecycle status strings, or a soft-delete trait merely to make a record recoverable. Deletion semantics and unique-key behaviour must be stated in the owning migration and model tests.

This decision does not retrofit every existing business model, change existing public slug policy, or introduce a generic repository layer.
