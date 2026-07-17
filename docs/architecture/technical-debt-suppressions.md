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
- missing files or incomplete metadata.

Run the contract locally with:

```bash
composer test:architecture
```

The GitHub CI summary reports active, inline, baseline, unregistered, stale,
and expired suppression counts. Approved raw SQL persistence boundaries are not
technical debt and remain governed separately by the raw SQL registry.
