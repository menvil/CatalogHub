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
| Full PHP | `composer test` | approximately 1–2 minutes at the current suite size; informational |
| Full CI | formatter, static analysis, build, PHP, browser, visual, DB engines, dependency audit | parallel jobs; no single brittle wall-clock budget |

## Last observed local run

Observed on 2026-08-11 with PHP 8.5.8, Node 26.5.0, SQLite in memory, and local Google Chrome. Exact measurements are filled from the final Phase 0.14 verification run; CI runners are expected to vary.

| Suite | Result | Elapsed |
| --- | --- | --- |
| Unit | passed, 5 tests / 18 assertions | 0.69 s |
| Feature foundation slice | passed, 11 tests / 60 assertions | 3.13 s |
| Architecture | passed, 60 tests / 566 assertions plus valid debt report | 5.66 s |
| Browser | passed, 1 Playwright test with graceful DB/server cleanup | 6.20 s |
| Visual | passed, 25 PHPUnit tests / 551 assertions plus 1 Playwright test | 145.75 s |
| Full PHP | passed, 1,981 tests / 7,739 assertions | 74.60 s |
| All PHP layers | passed, 2,006 tests including the isolated Visual suite | 74.60 s + 145.75 s visual |
