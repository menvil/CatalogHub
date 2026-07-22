# Runtime Platform Contract

CatalogHub production releases require:

- PHP 8.5.0 or newer within the supported Composer major range (`^8.5`);
- PostgreSQL 18.4 or newer as the primary production database.

SQLite remains the isolated default for the fast PHPUnit suite, and MariaDB remains a query/migration portability target. Neither is an approved production database for CatalogHub.

## Enforcement

The minimum is protected at several independent boundaries:

1. `composer.json` and `composer.lock` reject PHP older than 8.5.
2. GitHub Actions installs PHP 8.5 in every PHP job.
3. CI and local Docker Compose pin the PostgreSQL service to `postgres:18.4-alpine`.
4. `tests/Unit/Architecture/RuntimePlatformContractTest.php` rejects regressions in Composer, CI, Docker, database defaults, and this central configuration.
5. `php artisan cataloghub:platform-check` reads the active PDO server version and fails unless the runtime is PHP 8.5+ with PostgreSQL 18.4+.

Run the runtime check with the target deployment environment loaded before migrations or traffic switching:

```bash
php artisan cataloghub:platform-check
```

The command is intentionally read-only. It opens the configured database connection, reads PDO driver/server metadata, and does not query or mutate application data.

## Upgrades

A future version increase must update the central values in `config/platform.php`, Composer, CI images, Docker Compose, and this document in the same pull request. The architecture suite will fail if these boundaries diverge.
