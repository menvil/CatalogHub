# Foundation test matrix and runtime targets

Runtime values are guidance for a normal developer machine and GitHub-hosted runner, not hard pass/fail budgets. They should be refreshed after a material suite or runner change.

## Risk to suite mapping

| Foundation risk | Primary suite | Verification |
| --- | --- | --- |
| Enum/default drift | Unit | `FoundationBaselineTest` |
| Host, locale, code, or slug normalization drift | Unit | `FoundationBaselineTest` and existing focused normalizer tests |
| Unit baseline accidentally boots Laravel, DB, or network | Unit | `UnitSuiteIsolationTest` |
| Invalid user/site/membership/domain/locale/audit graph | Feature | `FoundationFactoriesTest` |
| Faker leaks into approved visual fixture inputs | Feature | `FoundationFactoriesTest` / `FoundationVisualFixture` |
| Central login or disabled-account regression | Feature + Browser | `SecurityContextSuiteTest`; `central-login.spec.mjs` |
| Cross-panel access or site-id tampering | Feature | `SecurityContextSuiteTest` with no-side-effect assertions |
| Unknown or alias host resolution | Feature | `SecurityContextSuiteTest` |
| Unsupported locale fallback | Feature | `SecurityContextSuiteTest` |
| Presentation context/import leakage | Architecture | `FoundationBoundariesTest` plus PHPStan rules |
| Broad or stale architecture exemption | Architecture | exact-file `allowlist.php` validation and debt report |
| Browser runtime/login integration failure | Browser | deterministic headless Central login smoke |
| Login screen visual drift | Visual | Playwright approved screenshot comparison |
| Screenshot comparator false green | Visual | matching and intentional-mismatch `VisualAssertionsTest` cases |

## Local and CI lanes

| Lane | Commands | Runtime target |
| --- | --- | --- |
| Quick local | `composer test:unit && composer test:architecture` | approximately 5–15 seconds after warm install |
| Context/security | `php vendor/bin/phpunit tests/Feature/Foundation tests/Feature/Factories/FoundationFactoriesTest.php` | approximately 5–20 seconds |
| Browser smoke | `composer test:browser` | approximately 10–30 seconds after browser install |
| Visual | `composer test:visual` | approximately 3–5 minutes because legacy approved screens are also compared |
| Full PHP | `composer test` | four non-overlapping PHPUnit suites run concurrently; runtime is bounded by the slowest suite |
| Full CI | formatter, architecture/PHPStan, build, PHP, browser, visual, DB engines, dependency audit | independent jobs run concurrently; PHPUnit suites and architecture/PHPStan are also parallel within their owning jobs |

## Last observed local run

Observed on 2026-10-01 with PHP 8.5.8, Node 26.5.0, SQLite in memory, and local Google Chrome. Exact measurements are from the Phase 18.4 parallel-runner verification; CI runners are expected to vary.

| Suite | Result | Elapsed |
| --- | --- | --- |
| Unit | passed, 12 tests / 21 assertions | 0.03 s JUnit time; runs concurrently |
| Legacy Unit | passed, 370 tests / 1,214 assertions | 16.16 s JUnit time; runs concurrently |
| Feature | passed, 2,000 tests plus 10 skipped / 8,800 assertions | 104.56 s JUnit time; runs concurrently |
| Browser contract | passed, 1 test / 8 assertions | 0.004 s JUnit time; runs concurrently |
| Architecture/static | passed, 73 architecture tests / 877 assertions, valid debt report, and PHPStan | runs independently from Full PHP |
| Visual | passed, 36 PHPUnit tests plus 28 Playwright screenshot cases | approximately 3–5 minutes |
| Full PHP | passed, 2,383 tests plus 10 skipped / 10,043 assertions | approximately 105 s, bounded by the Feature suite in this run |
| All required PHP layers | passed; Full PHP, architecture/static, and isolated Visual own disjoint responsibilities | concurrent CI lanes; wall time varies by host |
