# Backup Restore Dry-Run Checklist

Run this procedure against an isolated, disposable target. A CatalogHub catalog snapshot is a portable catalog export; it is not a substitute for the database and object-storage backups required below.

## Record the Exercise

- [ ] Name the operator, reviewer, date, source environment, and isolated target.
- [ ] Record the application release/tag and database engine versions.
- [ ] Define the recovery point and success criteria before changing the target.
- [ ] Confirm no target endpoint is publicly routed and no real external source credentials are enabled.

## Validate Inputs

- [ ] Locate a database backup created at or before the chosen recovery point.
- [ ] Locate the matching private media/object-storage backup.
- [ ] Select a completed `CatalogSnapshot` and record its UUID.
- [ ] Confirm the snapshot contains expected entity JSONL files and `media_manifest.jsonl`.
- [ ] Run `php artisan cataloghub:verify-checksums --snapshot=<UUID>`.
- [ ] Review the snapshot's file count, line counts, checksums, and generation failure history.
- [ ] Run checksum verification for the restored media manifest where available.

## Prepare the Isolated Target

- [ ] Create empty database and storage namespaces dedicated to the exercise.
- [ ] Install the application release compatible with the backup schema.
- [ ] Copy `.env.production.example` to a secure target-specific environment file and inject test credentials outside git.
- [ ] Set `APP_DEBUG=false`, disable outbound mail, and use non-production queues/cache namespaces.
- [ ] Disable scheduled imports, price-source sync, and all external API credentials.
- [ ] Put the target in maintenance mode before loading data.

## Restore Database and Media

- [ ] Restore the database using provider-reviewed tooling; retain its command output separately.
- [ ] Do not run destructive down migrations to force schema compatibility.
- [ ] Restore original media and generated variants to their configured private/public disks.
- [ ] Confirm storage ownership and permissions for the application/worker identities.
- [ ] Run `php artisan migrate:status` and document any migration mismatch.
- [ ] Apply forward migrations only when the exercise explicitly tests upgrade recovery.

Phase 20 intentionally has no automated snapshot import-to-production action. JSONL snapshot contents may be inspected and compared during this dry run, but importing them requires separately reviewed tooling.

## Rebuild Derived Data

- [ ] Restart workers with the restored release code.
- [ ] Rebuild affected product/category projections using the existing projection commands or admin actions.
- [ ] Rebuild search documents, sitemap URLs, and caches as required by the release.
- [ ] Run `php artisan cataloghub:media-integrity-check` and retain the missing-media summary.
- [ ] Run `php artisan cataloghub:verify-checksums --media` when manifest checksums are populated.

## Verify the Restored Target

- [ ] `php artisan migrate:status` shows the expected schema state.
- [ ] `php artisan test --group=smoke` passes against the isolated application test environment.
- [ ] Application, queue, and storage health checks report accepted states.
- [ ] Admin can open catalog, import, projection, price-source, sync, and snapshot pages.
- [ ] Public home, listing, and product pages render from projections.
- [ ] Sample products retain expected attributes, translations, media, offers, and versions.
- [ ] No real email, webhook, import, or price-source request left the target.

## Close the Exercise

- [ ] Record measured restore time and whether recovery-point/recovery-time expectations were met.
- [ ] List missing files, checksum mismatches, schema issues, and manual workarounds.
- [ ] Decide whether the result is pass, pass with accepted risks, or fail.
- [ ] Assign owners and deadlines for every unresolved recovery risk.
- [ ] Destroy the isolated target and its temporary credentials according to policy.
- [ ] Link the result from the release readiness record; do not mark production restore readiness complete based only on document review.

