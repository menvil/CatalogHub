# Raw SQL Exception Register

Architectural exceptions use an exact PHPStan allowlist, not the general
PHPStan baseline. Every entry is bound to a class and method names, explains why
raw SQL exists, declares its binding policy, and points to behavior tests.

Approved entries are allowed only in an explicitly declared persistence
boundary. Registry tests reject missing tests, duplicate pairs, invalid entries,
entries whose raw method is no longer present, and owners outside `App\\Queries`.
Controller validation, controller permission, and low-level DB exception lists
must remain empty.

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

| Boundary | Raw methods | Binding policy | Purpose |
| --- | --- | --- | --- |
| `FacetDocumentExpressionQuery` | `orderByRaw`, `whereRaw` | internal / required | Numeric JSON filters and portable NULL-last sorting |
| `StaleProjectionQuery` | `whereRaw` | internal | Driver-aware timestamp version comparison |
| `PublicProductSearchQuery` | `whereRaw`, `orWhereRaw` | required | Case-insensitive literal wildcard search |
| `CheapestProductsQuery` | `orderByRaw` | literal | Total offer price ordering including delivery |
| `OfferCoverageQuery` | `selectRaw` | literal | Conditional and distinct grouped coverage aggregates |
| `TranslationStatusCountsQuery` | `selectRaw` | literal | Grouped translation status counts |
| `MissingTranslationsQuery` | `whereRaw` | required | Literal escaped admin search |

New exceptions are never added to the PHPStan baseline and are not introduced
as legacy debt. They require an exact approved registry entry, bindings for
dynamic values, behavior tests in `composer test:database-boundaries`, and
persistence-boundary ownership.
