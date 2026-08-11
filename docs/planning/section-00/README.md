# Section 0 — Platform Foundation

Section 0 establishes the platform boundary used by later business sections.
The historical Phase 0.1 `P00-006` snapshot remains in
[`baseline/`](baseline/check-results.md); it must not be read as the current
architecture.

## Current evidence

- [Section Zero completion report](completion-report.md)
- [Foundation demo users](demo-users.md)
- [Foundation demo sites](demo-sites.md)
- [Fresh-install command](../../setup/fresh-install.md)
- [Testing strategy and matrix](../../testing/README.md)
- [CI acceptance and required checks](../../ci/section-zero-ci-acceptance.md)
- [Approved visual references](../../ui/visual-references.json)

## Current foundation boundary

- Central Admin and Site Admin are distinct Filament panels with separate route,
  layout, navigation, and authorization ownership.
- Public requests resolve an active site from a primary or alias host and bind a
  validated locale, currency, timezone, and whitelisted public theme.
- Six global roles, explicit site memberships, disabled-user enforcement, audit
  correlation, action boundaries, and site-scoped query rules are executable
  contracts.
- Unit, Feature, Architecture, Browser, and Visual suites are separate. CI also
  gates frontend build/lint, fresh PostgreSQL, MariaDB portability, dependency
  audit, and deterministic Chromium flows.
- `FoundationDemoSeeder` is the foundation-only acceptance dataset. It contains
  no catalog or business records and is rejected outside local/testing.

## Handoff

Brands work starts from the contracts and prerequisites in the
[completion report](completion-report.md). Section 0 does not authorize catalog
features, deployment redesign, performance certification, or production demo
credentials.
