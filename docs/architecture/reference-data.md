# Global reference data

CatalogHub owns small, controlled global reference domains independently of editorial catalog content. Countries are the first such domain. `Country` and `CountryTranslation` live in the global Geography model namespace; Brands consume them but do not own them. A Country row represents an ISO 3166 / UN M49 country or area present in the pinned dataset, including coded territories. It does not imply political independence.

## Country schema and identity

`countries.id` is the internal relational identity. `alpha2`, `alpha3`, and the three-character string `numeric_code` are unique external standard identities. Keeping the numeric code as text preserves values such as `004`. `canonical_name` is the CLDR English display name. Nullable `region_code`, `subregion_code`, and `intermediate_region_code` contain UN M49 geography metadata. `is_active` means available for new selections; inactive rows remain valid historical FK targets and are not deleted by synchronization.

`country_translations` stores unique `(country_id, locale)` system-managed display names. Its normalized BCP 47 language/locale string deliberately has no FK to editorial `locales`: reference names can be provisioned before any Site or Locale rows. These translations have no `TranslationStatus`, source hash, review, approval, or Translation Dashboard lifecycle. `CountryNameResolver` resolves exact locale, then base language, then `canonical_name`.

## Pinned sources and repository payload

The immutable `database/reference-data/countries` v1 payload is normalized and reviewable:

- `manifest.json` selects the current sync dataset and pins record counts, reference locales, source versions/URLs/hashes, and payload SHA-256 hashes;
- `manifest-v1.json` is the immutable migration manifest for the v1 payload;
- `countries-v1.json` contains compact Country records;
- `country-translations-v1.json` contains compact localized names;
- `UNICODE-LICENSE.txt` preserves the Unicode License v3 required by the derived CLDR data.

Identifiers and geography come from the United Nations Statistics Division M49 overview snapshot captured on 2026-08-25. The current overview exposes 248 rows. ISO 3166-1:2020, reviewed and confirmed in 2025, still assigns `TW`/`TWN`; the dataset therefore contains one explicit, manifest-documented supplement using the public UN M49 Rev.4 numeric identity `158` and Eastern Asia metadata. ISO states that its country codes may be used free of charge; no proprietary ISO database dump is vendored.

Canonical and localized territory display names come from Unicode CLDR 48.2 (`cldr-json` package/tag `48.2.0`, released 2026-03-17). The initial reference locales are `en` and `de`, matching the application/admin fallback and Foundation's intentional `en-DE`/`de-DE` setup through base-language fallback. More locales can be added in a future versioned payload without creating editorial Locale rows.

Sources:

- <https://unstats.un.org/unsd/methodology/m49/overview>
- <https://www.iso.org/iso-3166-country-codes.html>
- <https://cldr.unicode.org/index/downloads>
- <https://www.unicode.org/copyright.html>

## Provisioning and synchronization

The schema migration creates both reference tables and synchronizes the immutable v1 payload through `manifest-v1.json`. Consequently `php artisan migrate:fresh` produces a usable Country selector without a manual seed command. Production correctness never performs an HTTP request and Foundation demo seeders do not own Country data.

Future deployments can run:

```shell
php artisan reference:countries:sync --dry-run
php artisan reference:countries:sync
```

The loader validates the complete dataset and payload hashes before any write: manifest counts, uppercase alpha codes, three-digit numeric/geography codes, nonblank names/locales, identity uniqueness, translation references, and `(country, locale)` uniqueness. Synchronization is transactional and idempotent. It creates new rows, updates canonical/geography metadata and translations, reactivates source rows, and marks active database rows absent from the committed source inactive. It never hard-deletes Countries and emits no per-row business audit events. Dry-run reports the same planned counters without writing.

## Brand cutover

The Phase 9 migration prevalidates every non-null legacy `central_brands.country_code`, normalizing whitespace and case for lookup against `countries.alpha2`. An unknown or blank legacy value aborts with the affected Brand ID before schema mutation; it is never converted to null. Once validation succeeds, the migration adds nullable `country_id`, backfills in deterministic chunks, verifies every legacy value mapped, and removes `country_code`. The FK is indexed and restricts Country deletion, so existing Brands remain intact. Brand audit remains intentionally semantic: snapshots continue to use the resolved alpha-2 `country_code`, never the internal FK.

## Updating the dataset

Upstream access occurs only during deliberate developer regeneration:

1. choose and record pinned UN M49 and stable CLDR releases;
2. download the source inputs outside application runtime;
3. run `tools/reference-data/generate-countries.php` with explicit source-file paths;
4. review normalized payload and manifest diffs, including source hashes, versions, counts, and attribution;
5. run dataset, synchronizer, database, browser, and visual tests;
6. deploy through the normal migration path and optionally confirm with a dry run.

Old migrations always name immutable `v1` files; there is no mutable `latest` payload and no live fetch during startup, migration, deploy, or sync.

Country intentionally does not contain currency, calling code, timezone, capital, coordinates, flags, TLD, languages, population, EU/VAT rules, postal patterns, or subdivisions. Countries have no CRUD UI or management permission. Markets, Sites, Offers, and imports keep their existing country semantics until separately reviewed.
