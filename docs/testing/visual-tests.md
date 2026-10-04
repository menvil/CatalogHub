# Visual regression tests

Approved PNG files remain under `tests/Visual/baselines`. Playwright writes current screenshots and diffs only under `storage/logs/visual-artifacts/playwright`; the PHP comparison helper uses explicit `current/` and `diff/` children under the visual artifact root. Neither test path modifies a baseline.

The visual project fixes viewport, DPR, locale, timezone, color scheme, reduced motion, fonts readiness, scrollbar visibility, caret, animations, and transitions. Visual inputs come from `FoundationVisualFixture` or an existing versioned UI fixture, never Faker or remote content.

## Fixed screenshot environment

Playwright screenshot capture and comparison run through `tools/visual/run.mjs`. Both local development and CI use the official Playwright Noble image pinned by its immutable amd64 digest in `tools/visual/environment.json`. Its Chromium, rendering libraries and installed system fonts are identical on both paths; Apple Silicon also uses `linux/amd64`. Docker must be running. The first run downloads the image.

Install the locked development dependencies with `npm ci` before `npm run test:frontend`. Frontend config and runner regression tests require the installed Playwright version to match `tools/visual/environment.json`; they run real test collection with a stubbed Docker CLI and need neither Docker nor a browser download. Version drift intentionally fails these contract checks. When upgrading Playwright, update the lockfile and image declaration together.

When the host architecture requires amd64 emulation, individual tests have a 120-second execution budget instead of 30 seconds, and assertion waits allow 30 seconds instead of 10 seconds. Screenshot comparison thresholds remain identical; this only allows time for slower browser execution and page loading.

The runner mounts only the installed, locked Playwright JavaScript packages read-only and starts a temporary browser server on a random localhost port. Node, PHP, fixture setup and the existing application harness run on the host. Playwright forwards loopback traffic to that harness. The runner gives its container a unique owned name before creation, bounds Docker commands, tracks the startup CLI, and removes its container after the test process exits. Cancellation terminates an in-flight startup CLI; ignored signals escalate after a bounded grace period, and repeated interrupts force shutdown. Cleanup failures are reported without replacing an earlier test failure or interruption. It records the image identity in `storage/logs/visual-artifacts/renderer.json`. No application secrets or repository files are mounted in the browser container. The package/image version check fails on drift; there is no native-browser fallback for screenshot tests.

`composer test:visual` still includes the existing PHP legacy reference contracts and their native Chromium smoke captures. These retain their documented tolerances. The modern Playwright PNG comparisons all use the pinned renderer. Browser acceptance tests can still run natively to check platform behavior without comparing macOS and Linux pixels.

```bash
composer test:visual
```

Run only the modern screenshot suite, or filter CA-015:

```bash
npm run test:visual
npm run test:visual -- --grep CA-015
```

An approved reference can be replaced only through the explicit local command below and normal review. CI never calls it.

```bash
npm run test:visual:update -- --grep CA-015
```

Capture uses `--update-snapshots=all` so references cannot silently retain an older layout merely because its difference is below the comparison tolerance. The runner rejects update flags in CI. Direct native `npx playwright test --project=visual` is rejected; use the commands above for both capture and comparison. References, checksums and manifest entries must be reviewed together. Do not increase thresholds or substitute a product font to accommodate a platform change.

When upgrading Playwright, update its package lock and pinned image together, then deliberately inspect any resulting visual differences. System typography remains appropriate to the user's OS; additional macOS-specific references, if needed, must be compared within their own environment rather than to Linux captures. This follows Playwright's guidance on [environment-dependent rendering](https://playwright.dev/docs/test-snapshots) and [Docker browser servers](https://playwright.dev/docs/docker).

Any reference change must also pass the existing baseline checksum/review guard documented in [the visual diff policy](../ui/visual-diff-policy.md).
