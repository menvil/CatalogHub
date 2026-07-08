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

## Verification

```bash
php artisan --version
php artisan about
php artisan test
```
