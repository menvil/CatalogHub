# Foundation testing

Phase 0.14 keeps each test layer explicit. PHPUnit discovers `Unit`, `Feature`, `Architecture`, `Browser`, and `Visual` as separate suites; Playwright owns the real-browser and screenshot journeys. No second browser framework is installed. Pre-Phase-0.14 tests that still boot Laravel from `tests/Unit` remain discovered in a visible `Legacy Unit` transition suite instead of weakening the pure Unit contract or requiring a mass move.

## Commands

| Layer | Command | Purpose |
| --- | --- | --- |
| Unit | `composer test:unit` | Pure services, enums, normalizers, value objects, and suite isolation contracts |
| Legacy Unit | `composer test:legacy-unit` | Existing unit-labelled tests pending normal task-driven classification; no tests are hidden |
| Feature | `composer test:feature` | Laravel HTTP, persistence, authentication, and site-context behavior |
| Architecture | `composer test:architecture` | Namespace/import rules, presentation boundaries, and exact debt registry checks |
| Browser | `composer test:browser` | Headless Central login smoke with failure screenshot and trace |
| Visual | `composer test:visual` | Existing approved-reference checks plus Playwright screenshot comparison |
| Full PHP | `composer test` | Unit, legacy unit, feature, architecture, and browser contract tests; real screenshot tests stay in the isolated Visual lane |

`php vendor/bin/phpunit --list-suites` is the discovery check. `npm run test:browser:install` installs Playwright Chromium when a system Chrome binary is unavailable.

## Small explicit helpers

- `Tests\Support\SiteFixtures` creates named `alpha`, `beta`, or `archived` site graphs with explicit locales.
- `Tests\Support\AuthFixtures` creates users and memberships for authorization matrices.
- `Tests\Support\ClockFixtures` scopes the frozen `2026-08-09T10:00:00Z` visual clock and restores global state.
- `Tests\Support\FoundationVisualFixture` contains immutable visual strings and domains. It never calls Faker.

Helpers stay opt-in traits or value providers. `Tests\TestCase` deliberately contains no hidden site, auth, clock, or database setup.

See [browser-tests.md](browser-tests.md), [visual-tests.md](visual-tests.md), [architecture-tests.md](architecture-tests.md), and [test-matrix.md](test-matrix.md).
