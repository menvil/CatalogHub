# Environment Configuration Audit

Audit date: 2026-07-16  
Scope: application configuration after Phase 20. Values below are variable names and safe defaults only; this document must never contain deployed credentials.

## Classification

- **Required:** deployment must set an environment-specific value.
- **Conditional:** required only when the named driver or integration is enabled.
- **Optional:** a repository default is safe, but production may override it.
- **Local/test:** should not be relied on as a production setting.

## Application

| Variable | Class | Purpose / production rule |
| --- | --- | --- |
| `APP_NAME` | Optional | Display/logging name; use `CatalogHub`. |
| `APP_ENV` | Required | Must be `production` for production. |
| `APP_KEY` | Required, secret | Unique generated application encryption key. |
| `APP_DEBUG` | Required | Must be `false`. |
| `APP_URL` | Required | Canonical HTTPS application URL. |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | Optional | Default and fallback locales. |
| `APP_FAKER_LOCALE` | Local/test | Factory locale. |
| `APP_PREVIOUS_KEYS` | Conditional, secret | Old encryption keys during planned key rotation only. |
| `APP_MAINTENANCE_DRIVER`, `APP_MAINTENANCE_STORE` | Optional | Shared maintenance store is needed for multi-node deployments. |
| `BCRYPT_ROUNDS` | Optional | Password hashing cost; benchmark before changing. |
| `VITE_APP_NAME` | Build-time | Safe application label exposed to client assets. |

## Database

| Variable | Class | Purpose / production rule |
| --- | --- | --- |
| `DB_CONNECTION` | Required | Must be `pgsql`; production requires PostgreSQL 18.4 or newer. |
| `DB_URL` | Conditional, secret | May replace individual connection fields. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Required unless `DB_URL` is used | Database connection; credentials are secrets. |
| `DB_SSLMODE` | Conditional | PostgreSQL TLS policy; set according to the provider. |
| `DB_SOCKET`, `DB_CHARSET`, `DB_COLLATION`, `MYSQL_ATTR_SSL_CA` | Conditional | Driver-specific connection settings. |
| `DB_FOREIGN_KEYS` | Local/test | SQLite behavior only. |

## Redis, Cache, Queue, and Sessions

| Variable | Class | Purpose / production rule |
| --- | --- | --- |
| `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT` | Required for Redis drivers | Redis client and endpoint. |
| `REDIS_URL`, `REDIS_USERNAME`, `REDIS_PASSWORD` | Conditional, secret where applicable | Managed Redis connection/authentication. |
| `REDIS_DB`, `REDIS_CACHE_DB`, `REDIS_PREFIX` | Optional | Logical databases and application namespace. |
| `REDIS_CLUSTER`, `REDIS_PERSISTENT`, `REDIS_MAX_RETRIES`, `REDIS_BACKOFF_*` | Conditional | High-availability and retry tuning. |
| `CACHE_STORE` | Required | Production cache store; project default is Redis. |
| `CACHE_PREFIX` | Optional | Must be unique when Redis is shared. |
| `DB_CACHE_*`, `CACHE_STORAGE_*`, `MEMCACHED_*`, `DYNAMODB_*` | Conditional | Settings for alternate cache drivers only. |
| `QUEUE_CONNECTION` | Required | Must be an intended asynchronous driver in production. |
| `REDIS_QUEUE_CONNECTION`, `REDIS_QUEUE`, `REDIS_QUEUE_RETRY_AFTER` | Conditional | Redis queue routing/timing. |
| `DB_QUEUE_*`, `BEANSTALKD_*`, `SQS_*`, `QUEUE_FAILED_DRIVER` | Conditional | Alternate queue and failed-job settings. |
| `SESSION_DRIVER` | Required | Shared driver required for multi-node deployments. |
| `SESSION_LIFETIME`, `SESSION_EXPIRE_ON_CLOSE`, `SESSION_ENCRYPT` | Optional | Session lifetime and storage behavior. |
| `SESSION_SECURE_COOKIE` | Required | Must be `true` behind production HTTPS. |
| `SESSION_HTTP_ONLY` | Required | Must remain `true`. |
| `SESSION_SAME_SITE` | Required | Approved policy, normally `lax`. |
| `SESSION_CONNECTION`, `SESSION_TABLE`, `SESSION_STORE`, `SESSION_COOKIE`, `SESSION_PATH`, `SESSION_DOMAIN`, `SESSION_PARTITIONED_COOKIE` | Conditional | Driver, cookie, and topology-specific settings. |

## Storage, Media, Imports, and Snapshots

| Variable | Class | Purpose / production rule |
| --- | --- | --- |
| `FILESYSTEM_DISK` | Required | Default application disk. |
| `MEDIA_DISK`, `MEDIA_URL_DISK`, `PUBLIC_MEDIA_DISK` | Required | Private/source media and public derivative disks. |
| `MEDIA_PATH_PREFIX` | Optional | Provider namespace/prefix. |
| `MEDIA_UPLOAD_DISK`, `MEDIA_PLACEHOLDER_URL`, `MEDIA_DISPATCH_VARIANTS_ON_UPLOAD` | Optional | Upload pipeline and placeholder behavior. |
| `MEDIA_MAX_UPLOAD_WIDTH`, `MEDIA_MAX_UPLOAD_HEIGHT`, `MEDIA_MAX_UPLOAD_PIXELS` | Optional | Image safety limits. |
| `IMPORTS_DISK`, `IMPORT_ARTIFACT_PREFIX` | Required / optional | Private import artifacts and path prefix. |
| `IMPORT_QUEUED_ARTIFACT_THRESHOLD_BYTES`, `IMPORT_SERIALIZED_PHP_MAX_BYTES`, `IMPORT_SERIALIZED_PHP_MAX_DEPTH` | Optional | Import memory and serialization safety thresholds. |
| `IMPORT_DUPLICATE_MIN_SCORE`, `IMPORT_PROCESSING_STALE_AFTER_SECONDS` | Optional | Import workflow thresholds. |
| `IMPORT_MEDIA_DOWNLOAD_TIMEOUT`, `IMPORT_MEDIA_DOWNLOAD_MAX_BYTES` | Optional | Remote media safety limits. |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` | Conditional, secret where applicable | S3-compatible storage credentials/settings. |
| `AWS_URL`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` | Conditional | S3/R2-compatible endpoint and URL behavior. |

Snapshot generation currently records its disk per snapshot and defaults to the private `local` disk. `EXPORTS_DISK` and `BACKUPS_DISK` appear in `.env.example` but are not consumed by current configuration; they are reserved/unused and must not be assumed to control Phase 20 snapshots.

## Mail and Logging

| Variable | Class | Purpose / production rule |
| --- | --- | --- |
| `MAIL_MAILER`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Required if mail is sent | Transport and sender identity. |
| `MAIL_URL`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_EHLO_DOMAIN` | Conditional, secret where applicable | SMTP transport. |
| `MAIL_SENDMAIL_PATH`, `MAIL_LOG_CHANNEL` | Conditional | Alternate/local transports. |
| `POSTMARK_API_KEY`, `RESEND_API_KEY` | Conditional, secret | Provider credentials. |
| `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL` | Required | Production log destination and threshold. |
| `LOG_DEPRECATIONS_CHANNEL`, `LOG_DEPRECATIONS_TRACE`, `LOG_DAILY_DAYS` | Optional | Deprecation and retention behavior. |
| `LOG_SLACK_*`, `PAPERTRAIL_*`, `LOG_PAPERTRAIL_HANDLER`, `LOG_STDERR_FORMATTER`, `LOG_SYSLOG_FACILITY` | Conditional, secret where applicable | External/alternate log channels. |

## Pricing and External Services

| Variable | Class | Purpose / production rule |
| --- | --- | --- |
| `PRICE_FRESH_HOURS`, `PRICE_STALE_HOURS`, `PRICE_EXPIRED_HOURS` | Optional | Offer freshness boundaries. |
| `SLACK_BOT_USER_OAUTH_TOKEN`, `SLACK_BOT_USER_DEFAULT_CHANNEL` | Conditional, secret where applicable | Slack notifications. |
| `AUTH_GUARD`, `AUTH_PASSWORD_BROKER`, `AUTH_MODEL`, `AUTH_PASSWORD_RESET_TOKEN_TABLE`, `AUTH_PASSWORD_TIMEOUT` | Optional | Laravel authentication overrides; defaults are expected. |

Price/import source credentials are stored encrypted in application records rather than exposed as a generic `PRICE_SOURCE_*` environment family. Each enabled provider still needs an operator-owned credential inventory and rotation procedure.

## Gaps and Follow-up

- `.env.production.example` must add secure cookie values and clearly separate required production values from optional driver settings.
- Error-reporting variables use a provider-neutral, opt-in configuration; selecting and installing an external SDK remains a separate reviewed change.
- `BROADCAST_CONNECTION` exists in `.env.example` but no real-time Phase 19–21 feature depends on it.
- `EXPORTS_DISK` and `BACKUPS_DISK` are unused by current PHP configuration and should remain documented as reserved until wired deliberately.
- Never run `env()` outside configuration files; deployment relies on `php artisan config:cache`.

## Verification Procedure

1. Compare the target environment keys with this inventory without printing their values.
2. Run `php artisan cataloghub:platform-check`; require PHP 8.5+ and PostgreSQL 18.4+.
3. Run `php artisan config:cache` and boot the application.
4. Run application, queue, and storage health checks.
5. Confirm `APP_DEBUG=false`, secure session cookies, and private snapshot storage from an HTTP client.
6. Record missing conditional variables only for integrations that are actually enabled.
