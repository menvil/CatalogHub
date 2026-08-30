# Derived Brand quality read model

Brand quality/completeness is a deterministic, read-only projection over authoritative canonical Brand, Shared Media, and Translation data. `CentralBrandQualityEvaluator` is the single rules contract. `CentralBrandQualityQuery` performs bounded loading for one Brand and passes loaded inputs to the evaluator; the evaluator issues no queries and performs no writes.

Quality is not lifecycle. `CentralBrandStatus` remains exactly Draft, Active, or Archived, and every lifecycle transition retains its existing behavior regardless of score. Quality has only the derived presentation states `complete` and `needs_attention`; neither is user-selectable or persisted.

## Checks and score

Every applicable check has equal weight. The six base checks are:

1. `canonical_country`: `country_id` is present.
2. `canonical_website`: `website_url` is nonblank.
3. `canonical_founded_year`: `founded_year` is present.
4. `canonical_support_contact`: at least one of `support_url` or `contact_email` is nonblank.
5. `canonical_primary_color`: `primary_color` is nonblank.
6. `global_primary_logo`: an exact global primary `central_brand` + `brand_logo` assignment exists and `BrandLogoPresenter` can resolve a physically available ready semantic variant or master.

One additional `translation:{locale-code}` check is added for every active `Locale`, in the shared default/position/code ordering. No check is added for an inactive Locale. A translation is complete when its `BrandTranslation` row uses `MachineTranslated`, `HumanReviewed`, or `Approved`. An absent row or an explicit `Missing` status produces a missing issue; `Outdated` produces an outdated issue. Evaluation never creates a missing translation row.

The exact score is:

`round(completed applicable checks / total applicable checks × 100, PHP_ROUND_HALF_UP)`

The denominator is therefore `6 + active locale count`. `complete` means every applicable check is complete; every other result is `needs_attention`. There are no severity weights, thresholds, overrides, manual recalculation commands, or score caches.

## Stable issue codes

- `brand_country_missing`
- `brand_website_missing`
- `brand_founded_year_missing`
- `brand_support_contact_missing`
- `brand_primary_color_missing`
- `brand_logo_missing`
- `brand_logo_unusable`
- `brand_translation_missing`
- `brand_translation_outdated`

Translation codes may occur once per affected active Locale; each check also carries its locale and CA-015 destination. Profile issues target CA-013 and logo issues target CA-014. The read model carries the required existing permission with each destination, so CA-012 can omit inaccessible mutation links while retaining readable issue text.

## Boundaries

The read model loads active Locales and the selected Brand's matching translations, then reuses `CentralBrandMediaQuery` as the authoritative selector for the exact global primary logo assignment with its asset/variants. CA-012, CA-013, CA-014, and quality therefore share one Brand-logo definition in a fixed number of queries. The pure evaluator can later be reused by a batch/list query that preloads the same inputs without running one query per Brand.

There is no `central_brands` quality column, JSON issue payload, calculation timestamp, new table, cache, job, AuditEvent, or write-side hook. Tags, Products, Category coverage, external identities, Parent Company, lifecycle status, and Site publication/projection state are not scored. CA-015 Save/Approve/Mark Outdated are ordinary translation mutations; the next CA-012 read derives the result without recalculation. Site visibility, Published/Unpublished, Synced/Stale, persisted field-level workflow, translation providers/memory, and richer media roles remain separate future concerns.
