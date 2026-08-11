# Visual regression tests

Approved PNG files remain under `tests/Visual/baselines`. Playwright writes current screenshots and diffs only under `storage/logs/visual-artifacts/playwright`; the PHP comparison helper uses explicit `current/` and `diff/` children under the visual artifact root. Neither test path modifies a baseline.

The visual project fixes viewport, DPR, locale, timezone, color scheme, reduced motion, fonts readiness, scrollbar visibility, caret, animations, and transitions. Visual inputs come from `FoundationVisualFixture` or an existing versioned UI fixture, never Faker or remote content.

```bash
composer test:visual
```

An approved reference can be replaced only through the explicit local command below and normal review. CI never calls it.

```bash
npm run test:visual:update
```

Any reference change must also pass the existing baseline checksum/review guard documented in [the visual diff policy](../ui/visual-diff-policy.md).
