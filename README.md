# CatalogHub

CatalogHub / Product Catalog Platform.

Laravel monolith for Central Catalog and localized portal projections.

## Local Development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Verification

```bash
php artisan --version
php artisan about
php artisan test
```
