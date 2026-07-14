# CatalogHub

CatalogHub / Product Catalog Platform.

Laravel monolith for Central Catalog and localized portal projections.

## Local Development

Node.js 24 LTS is required for frontend tooling. The repository includes an `.nvmrc` file for local version selection.

```bash
nvm use
composer install
cp .env.example .env
php artisan key:generate
docker compose up -d postgres
php artisan migrate
php artisan serve
```

## Local Infrastructure

PostgreSQL is the primary application database.

```bash
docker compose up -d postgres
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
```

## Testing

The primary local test command is:

```bash
composer test
```

The default PHPUnit suite is isolated from local infrastructure through `phpunit.xml`: database `sqlite/:memory:`, cache `array`, queue `sync`, and mail `array`.

## CI

GitHub Actions runs Composer install, npm install, a PostgreSQL migration smoke check, `composer test`, `php artisan test`, Pint, PHPStan, and `npm run build` for pull requests and pushes to `develop`.

## First Admin User

Create the first admin user through Filament:

```bash
php artisan filament:make-user --panel=admin
```
