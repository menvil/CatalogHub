# CatalogHub

CatalogHub / Product Catalog Platform.

Laravel monolith for Central Catalog and localized portal projections.

## Pre-launch schema consolidation

CatalogHub has no valuable deployed Category/Attribute data. PR #615 rewrites migration history to make the final global Attribute + Category assignment model the initial schema. Existing disposable developer databases must run `php artisan migrate:fresh --seed`; no finalization command is required.

**Schema freeze rule:** after the first environment contains valuable non-disposable data, existing migrations are immutable. All later schema changes require new forward migrations preserving deployed data. See [the consolidation policy](docs/architecture/pre-launch-schema-consolidation.md).

## Local Development

PHP 8.5 or newer and PostgreSQL 18.4 or newer are required for the supported runtime. Node.js 26 is required for frontend tooling. The repository includes an `.nvmrc` file for local version selection.

```bash
nvm use
composer install
cp .env.example .env
php artisan key:generate
docker compose up -d postgres
php artisan cataloghub:platform-check
php artisan migrate:fresh --seed
php artisan serve
```

## Local Infrastructure

PostgreSQL 18.4 or newer is the required production database. SQLite and MariaDB are retained only for automated compatibility coverage.

```bash
docker compose up -d postgres
php artisan cataloghub:platform-check
php artisan migrate:fresh
php artisan db:show
```

Redis is configured for cache infrastructure and future queues/locks.

```bash
docker compose up -d redis
php artisan tinker --execute="Cache::store('redis')->put('health', 'ok', 60); dump(Cache::store('redis')->get('health'));"
```

Laravel queues use Redis for local infrastructure. Run a worker with:

```bash
php artisan queue:work
```

Laravel scheduler is enabled through the standard Artisan entrypoint. Production or local process managers should run:

```bash
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

For local foreground execution:

```bash
php artisan schedule:work
```

Filesystem disks are configured for media, imports, exports, and backups through Laravel Storage. Use disk names and relative paths instead of hardcoded upload paths.

## Verification

```bash
php artisan --version
php artisan about
php artisan test
npm install
npm run build
composer format:test
composer analyse
composer test:architecture
```

## Testing

The primary local test command is:

```bash
composer test
```

The default PHPUnit suite is isolated from local infrastructure through `phpunit.xml`: database `sqlite/:memory:`, cache `array`, queue `sync`, and mail `array`.

## CI

GitHub Actions runs Pint code-style verification, non-overlapping PHPUnit suites in parallel, architecture contracts alongside PHPStan, frontend quality, Playwright browser/visual checks, SQLite/PostgreSQL/MariaDB database lanes, and dependency audits for pull requests to `develop` or `main`.

## First Admin User

Create the first admin user through Filament:

```bash
php artisan filament:make-user --panel=admin
```
