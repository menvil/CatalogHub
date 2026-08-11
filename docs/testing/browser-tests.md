# Browser tests

Playwright Test `1.62.1` is the single browser framework. The lockfile pins the effective package version. `playwright.config.mjs` runs Chromium headlessly with one worker, fixed locale/timezone, DPR 1, and a fixed viewport.

The harness creates only its port-scoped `storage/logs/browser-harness-8014.sqlite`, migrates it without seeders, inserts the deterministic Central operator, and starts Laravel on `127.0.0.1:8014`. The visual project uses `8015` and its own database, so both lanes can run concurrently. Databases are isolated from PHPUnit and removed when each server stops.

```bash
npm ci
npm run test:browser:install  # only when system Chrome is unavailable
composer test:browser
```

The smoke signs in as `central-admin@fixture.test` and waits on visible elements and URL state. `waitForTimeout`, fixed sleeps, and network services are forbidden. A failed test retains a trace and screenshot under `storage/logs/browser-artifacts`; CI uploads that ignored runtime directory only on failure.
