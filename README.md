# CatalogHub

CatalogHub / Product Catalog Platform.

Laravel monolith for Central Catalog and localized portal projections.

## Local Development

```bash
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

## Verification

```bash
php artisan --version
php artisan about
php artisan test
```
