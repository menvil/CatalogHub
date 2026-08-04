# P00-005 — Baseline Check Results

Snapshot date: 2026-08-04  
Code base: `origin/develop` at `29a7374`, after the separately reviewed dependency MR #549, #551 and #554 merges. Pre-existing `.gitignore` and `pictures/` changes remain excluded from the Phase 0.1 MR.  
Environment: macOS local shell, PHP 8.5.8, Node 26.5.0, npm 11.17.0, SQLite 3.43.2.

Initial commands ran between 19:08 and 19:14 EEST. The complete matrix was rerun between 19:35 and 19:37 EEST after the three separately authorized dependency/workflow MRs were merged. No failing command was masked by Phase 0.1.

| Check | Command | Exit | Result |
| --- | --- | ---: | --- |
| Documentation/target absence proof | required-file `test -f` chain before implementation | 1 | expected red: baseline documents and verification files did not exist |
| Isolated fresh migration and seed inventory | temporary SQLite `migrate:fresh --seed --force` | 0 | pass: 98 migrations, 84 tables, seed counts recorded in domain inventory |
| Targeted Phase 0.1 verification | `php artisan test` for the four added test files | 0 | pass: 13 tests, 52 assertions |
| Composer manifest | `composer validate --strict` | 0 | pass: manifest valid |
| Architecture suite | `composer test:architecture` | 0 | pass: 35 tests, 383 assertions; architecture debt report valid with 0 active entries |
| Static analysis | `composer analyse -- --no-progress` | 0 | pass: 0 PHPStan errors |
| Formatter | `composer format:test` | 0 | pass |
| Backend tests | `composer test` | 0 | pass: 1,522 tests, 5,574 assertions in 42.1 seconds |
| Coverage | environment discovery | n/a | not run locally: PCOV/Xdebug is not installed; CI workflow remains authoritative |
| Composer install | `composer install --no-interaction --prefer-dist --no-progress` | 0 | pass; local vendor refresh emitted non-failing fixture PSR-4 and pre-uninstall warnings |
| Frontend install | `npm ci` | 0 | pass: 62 packages audited, 0 vulnerabilities |
| Frontend lint/tests | discovery only | n/a | no repository scripts exist |
| Production build | `npm run build` | 0 | pass in 1.99 seconds; non-failing optional Fontaine warning emitted |
| Composer dependency audit | `composer audit --locked` | 0 | pass: no security advisories with Guzzle 7.15.2 |
| npm dependency audit | `npm audit --audit-level=high` | 0 | pass: 0 vulnerabilities with PostCSS 8.5.25 |
| Fresh migration and seed | isolated temporary SQLite command | 0 | pass in 1 second: 98 migrations, 84 tables, 3 sites |

## Failure classification and external changes

- Current repository failures: none in the commands that were run.
- Initial repository/dependency failures: Guzzle 7.15.1 and PostCSS 8.5.16 advisories. They were resolved outside this task by the separately merged Dependabot MR #551 and #554; the Phase 0.1 diff contains no lock-file change.
- Environment failures: none among executed commands.
- Not run locally: PCOV coverage and the PostgreSQL/MariaDB migration/query/rollback jobs. PCOV/Xdebug is absent and the database service containers were not started. These are not recorded as passes or failures; GitHub Actions remains authoritative.

MR #554 CI passed SQLite tests, MariaDB/PostgreSQL migration and boundary suites, architecture/static analysis, formatting, frontend build and both dependency audits on the same dependency base. The production build warning says optimized font fallbacks need the optional `fontaine` package or an explicit disable setting. It does not alter the exit code and is recorded rather than changed.
