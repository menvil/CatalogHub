# P00-001 — Runtime And Framework Version Inventory

Snapshot date: 2026-08-04  
Snapshot code base: `29a7374` (`origin/develop`)  
Policy: inventory only; no dependency or runtime changes were made.

## Effective versions

| Component | Declared / pinned | Resolved or observed | Source |
| --- | --- | --- | --- |
| PHP | `^8.5`; platform minimum `8.5.0` | local CLI `8.5.8`; CI `8.5` | `composer.json`, `composer.lock`, `config/platform.php`, GitHub Actions, `php -v` |
| Laravel | `^13.8` | `v13.24.0` | `composer.json`, `composer.lock`, `php artisan --version` |
| Filament | `^5.6` | `v5.7.5` | `composer.json`, `composer.lock` |
| Livewire | transitive through Filament | `v4.3.5` | `composer.lock` |
| Composer | not pinned by the repository | local `2.10.2`; CI uses runner-provided Composer | `composer --version`, workflows |
| Node.js | `>=26 <27`; major `26` | local `v26.5.0`; CI major `26` | `package.json`, `.nvmrc`, workflows, `node --version` |
| npm | npm lock format 3; npm is the selected package manager | local `11.17.0`; exact CI version not pinned | `package-lock.json`, `npm --version`, `npm ci` in workflows |
| PostgreSQL | production minimum `18.4`; image `18.4-alpine` | local client `18.4`; CI/Compose service `18.4-alpine` | `config/platform.php`, `.env.example`, `docker-compose.yml`, workflows, `psql --version` |
| MariaDB | portability target only | CI service `11.4` | `.github/workflows/ci.yml` |
| SQLite | test target only | local client `3.43.2`; PHPUnit uses PDO SQLite `:memory:` | `phpunit.xml`, `sqlite3 --version` |
| Redis | local service image `7-alpine` | server patch version is not pinned | `docker-compose.yml` |
| Vite | `^8.2.0` | `8.2.0` | `package.json`, `package-lock.json` |
| Tailwind CSS | `^4.0.0` | `4.3.3` | `package.json`, `package-lock.json` |
| Laravel Vite plugin | `^3.1` | `3.1.3` | `package.json`, `package-lock.json` |
| PHPUnit | `^12.5.12` | `12.5.31` | `composer.json`, `composer.lock` |
| Larastan | `^3.10` | `3.10.0` | `composer.json`, `composer.lock` |
| Pint | `^1.27` | `1.30.2` | `composer.json`, `composer.lock` |

## Runtime sources and differences

- Production defaults to `pgsql`; PostgreSQL 18.4 is the only approved production database. SQLite is the fast PHPUnit and local baseline target; MariaDB is a CI portability target.
- Local PHP, Node and PostgreSQL client satisfy the declared minimums. The exact Composer and npm versions are not repository-pinned, so local and CI can differ while dependency locks remain identical.
- No deploy image or infrastructure manifest pins exact PHP, Node, Composer or npm patch versions. Deployment documentation requires platform verification but delegates process/image selection to the environment owner.
- `composer.json` ranges and lock-file versions intentionally differ: ranges express compatibility, while lock files record the installed dependency graph.
- Redis uses a major-only tag. This is an operational reproducibility gap, not changed in Phase 0.1.

## Reproduction

```bash
php -v
composer --version
node --version
npm --version
psql --version
sqlite3 --version
php artisan --version
php artisan about --only=environment
```

The boot verification is `tests/Feature/Smoke/ApplicationBootTest.php`; the existing runtime contract is `tests/Unit/Architecture/RuntimePlatformContractTest.php`.
