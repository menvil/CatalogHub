# ADR-0002 — Transactions and side effects

**Status:** Accepted  
**Date:** 2026-08-11  
**Task:** P00-100

## Decision

- An Action owns the smallest transaction that must succeed or fail together, including local audit writes.
- Authorization happens inside that boundary before mutation. A failed authorization has no persistence side effect.
- HTTP calls, filesystem writes, email, and job dispatches that can perform external work do not run inside the transaction. They are scheduled only after commit through framework after-commit facilities or an explicit outbox in a future task.
- Retrying is owned by the queue/job or integration boundary, never by silently repeating a user mutation transaction.

## Demonstration

`UpsertSiteMembershipAction` locks and updates the membership, then records its audit row in one transaction. Its regression test makes audit recording fail and proves the membership update rolls back.

This decision does not add a generic transaction wrapper, an event bus, or an outbox implementation.
