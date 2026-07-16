# Raw SQL Exception Register

Architectural exceptions use an exact PHPStan allowlist, not the general
PHPStan baseline. Every entry is bound to a class and method names, explains why
raw SQL exists, declares its binding policy, and points to behavior tests.

Legacy entries additionally name the MR that will remove or isolate them.
Approved entries are allowed only in an explicitly declared persistence
boundary. Registry tests reject missing tests, duplicate pairs, invalid entries,
and entries whose raw method is no longer present.

Example:

```neon
parameters:
    architecture:
        rawSqlExceptions:
            -
                class: App\Queries\Feed\FeedQuery
                methods: [whereRaw, orWhereRaw]
                reason: 'Literal LIKE escaping with bound search patterns.'
                bindings: required
                behaviorTests: [tests/Feature/Queries/FeedQueryTest.php]
                status: approved
```

| Area | Current reason | Target |
| --- | --- | --- |
| Facets | JSON numeric extraction and deterministic NULL-last sorting | MR 7: isolate facet expressions |
| Projections | Atomic version comparison and driver-aware update behavior | MR 7: isolate projection queries |
| Pricing reports | Conditional coverage aggregates and total-price sorting | MR 6: pricing query layer |
| Translations | Grouped completeness aggregates and escaped search patterns | MR 6/7: query layer |
| Public search | Explicit escaped `LIKE` behavior | MR 7: public search query |
| Admin empty-state queries | Legacy `whereRaw('1 = 0')` sentinels | MR 4/7: replace with Eloquent empty scopes |

New exceptions are never added to the PHPStan baseline. They require an exact
registry entry, bindings for dynamic values, behavior tests, and persistence
boundary ownership.
