# CatalogHub Design System Foundation

Phase 0.5 establishes a small semantic foundation shared by Central Admin, Site Admin, and Public Site without merging their layout ownership.

- [Existing visual primitives audit](visual-primitives-audit.md)
- [Token contract and change process](tokens.md)
- [Iconography and status semantics](icons.md)
- [Responsive breakpoints and density](responsive.md)
- [Established admin compatibility tokens](admin-ui-tokens.md)
- [Shared administrative component contracts](components.md)

The canonical component gallery is `/admin/central/component-gallery`. It is available only in local/testing environments and still requires Central Admin authentication and authorization. The deterministic capture-only route is also local/testing-only and is not a production interface.

The approved token reference is `tests/Visual/baselines/component-gallery-wide.png`. Shared component references use the `admin-components-*.png` files described in [the component contract](components.md). Checked-in SHA-256 values and fresh fixed-viewport Chrome captures are validated by the required `composer test:visual` CI job. The visual command requires built assets, GD, and Chrome and fails instead of silently skipping when the browser is unavailable. Updating a baseline is a deliberate review action: build assets, capture at the fixed viewport, inspect the image, then update the checksum. Tests never generate or accept a replacement.

Regression coverage validates token keys, palette uniqueness, shared build imports, approved icon usage, exact viewport definitions, protected gallery behavior, documentation links, forbidden raw values in new foundation sources, and the approved visual checksum.
