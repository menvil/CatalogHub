# Raw SQL Exception Register

Architectural exceptions use an exact PHPStan allowlist, not the general
PHPStan baseline. Every entry is bound to a class, owner methods, and raw method
names, explains why raw SQL exists, declares its binding policy, and points to
behavior tests.

Approved entries are allowed only in an explicitly declared persistence
boundary. Registry tests reject missing tests, duplicate pairs, invalid entries,
entries whose raw method is no longer present, and owners outside `App\\Queries`.
Controller validation, controller permission, and low-level DB exception lists
do not exist. Temporary PHPStan suppressions are tracked separately by the
[technical debt registry](technical-debt-suppressions.md).

Example:

```neon
parameters:
    architecture:
        rawSqlExceptions:
            -
                class: App\Queries\Feed\FeedQuery
                ownerMethods: [applySearch]
                methods: [whereRaw, orWhereRaw]
                reason: 'Literal LIKE escaping with bound search patterns.'
                bindings: required
                behaviorTests: [tests/Feature/Queries/FeedQueryTest.php]
                status: approved
```

| Boundary | Owner methods | Raw methods | Binding policy | Purpose |
| --- | --- | --- | --- | --- |
| `FacetDocumentExpressionQuery` | price/rating ordering, numeric filter | `orderByRaw`, `whereRaw` | internal / required | Numeric JSON filters and portable NULL-last sorting |
| `StaleProjectionQuery` | product/category queries | `whereRaw` | internal | Driver-aware timestamp version comparison |
| `StaleProductVersionGapQuery` | `apply` | `whereRaw` | required | Bound version-gap arithmetic |
| `PublicProductSearchQuery` | `applyLiteralSearch` | `whereRaw`, `orWhereRaw` | required | Case-insensitive literal wildcard search |
| `CheapestProductsQuery` | `forSite` | `orderByRaw` | literal | Total offer price ordering including delivery |
| `OfferCoverageQuery` | overall/category/source reports | `selectRaw` | literal | Conditional and distinct grouped coverage aggregates |
| `TranslationStatusCountsQuery` | `forLocale` | `selectRaw` | literal | Grouped translation status counts |
| `MissingTranslationsQuery` | `get` | `whereRaw` | required | Literal escaped admin search |

The effective key is `class + owner method + raw method`, so approving one raw
call does not approve unrelated calls elsewhere in the same Query Object.

New exceptions are never added to the PHPStan baseline and are not introduced
as legacy debt. They require an exact approved registry entry, bindings for
dynamic values, behavior tests in `composer test:database-boundaries`, and
persistence-boundary ownership.
