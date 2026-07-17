# Technical Debt Suppressions

CatalogHub does not use a general PHPStan baseline or untracked inline ignores.
Every temporary static-analysis suppression must be registered exactly in
`tools/architecture/debt.json`.

The registry entry contains:

- a stable debt ID;
- suppression source, file, PHPStan identifier, and exact occurrence count;
- a concrete reason and owner;
- the task that removes the debt;
- an expiry date.

The architecture debt scanner rejects:

- an inline ignore or baseline entry that is not registered;
- duplicate registry entries;
- count mismatches;
- registry entries whose suppression no longer exists;
- expired debt;
- count mismatches between registry and source;
- wildcard ignores;
- every suppression of a `cataloghub.*` architecture-rule identifier;
- missing files or incomplete metadata.

Architecture rules are permanent executable boundaries, not technical debt.
They cannot be placed in the registry or a baseline. The debt mechanism is only
for an explicitly owned, expiring type-level finding whose removal task is known.

Run the contract locally with:

```bash
composer test:architecture
```

The GitHub workflow summary and its single updated PR comment report registered,
actual, inline, baseline, unregistered, stale, mismatched, expired, and forbidden
counts. The downloadable JSON artifact includes the complete registered and
observed suppression entries, not only aggregate counters. Approved raw SQL
persistence boundaries are not technical debt and remain governed separately by
the raw SQL registry.
