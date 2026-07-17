## Summary

-

## Verification

- [ ] Relevant automated tests pass.
- [ ] `composer analyse -- --no-progress` passes.
- [ ] `composer test:architecture` reports no unregistered, stale, mismatched, expired, or forbidden debt.

## Architecture Review

- [ ] HTTP input validation uses a dedicated Form Request and typed accessor/data object; Livewire and Filament use framework-native validation.
- [ ] Presentation code delegates writes and transactions to Actions.
- [ ] Resource authorization uses Policy/Gate; presentation code does not inspect roles, capabilities, or permission keys directly.
- [ ] Controllers delegate Eloquent reads and relationship loading to Query Objects.
- [ ] Query Objects and policies remain read-only and contain no locks or transactions.
- [ ] Reads use Eloquent models, relationships, scopes, aggregates, and Query Objects where possible.
- [ ] Raw SQL is isolated in an approved `RawSqlPersistenceBoundary` with bindings, an exact registry entry, a reason, and cross-database behavior tests.
- [ ] Paginated queries use a registered `StablePaginationBoundary`, a unique final order, and a tied-sort cross-page behavior test.
- [ ] High-traffic collection rendering has a query-count/N+1 contract when its shape changed.
- [ ] No PHPStan baseline or inline suppression was added without exact debt metadata and expiry; `cataloghub.*` architecture findings were not suppressed.
- [ ] Database behavior remains compatible with SQLite, MariaDB, and PostgreSQL.

## UI Review (when applicable)

- [ ] `npm run build` passes.
- [ ] Desktop and mobile layouts were checked.
- [ ] No accidental debug output, broken media, horizontal overflow, or unsafe external markup is visible.
