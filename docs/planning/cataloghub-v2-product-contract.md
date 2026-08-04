# CatalogHub v2 Product Contract

| Field | Value |
| --- | --- |
| Contract version | 2.1.0 |
| Status | Proposed; approval required before implementation |
| Owner | CatalogHub Product Owner |
| Approver | `TBD — approver must be named` |
| Approval date | `TBD — YYYY-MM-DD` |
| Last updated | 2026-08-04 |

## Changelog

| Version | Date | Change |
| --- | --- | --- |
| 2.0.0 | 2026-08-04 | Recorded the three product surfaces and the Central/Site ownership boundary. |
| 2.1.0 | 2026-08-04 | Added document precedence, deterministic projection ownership, immutable Site context rules, and serial-first delivery governance. |

## Authority and precedence

When two sources disagree, the higher source in this order controls:

1. this Product ownership contract;
2. the authoritative screen registry (`cataloghub-v2-screen-registry.md`);
3. the approved visual reference manifest (`cataloghub-v2-visual-reference-manifest.md`);
4. Roadmap v2 (`roadmap-v2-screen-driven.md`);
5. discovery information architecture and wireframes;
6. the existing v1 implementation.

If documents, screenshots, discovery notes, or the existing implementation
conflict, the work package is blocked until a product decision updates this
contract and/or the authoritative screen registry. An implementer must not
resolve the conflict by copying v1, guessing from a filename, or creating a new
screen. Approval metadata and the changelog must be updated with the decision.

## Product shape

CatalogHub v2 is one product with three surfaces:

| Surface | Audience | Purpose | Runtime boundary |
| --- | --- | --- | --- |
| CatalogHub Admin — Central Admin workspace | Central operators and scoped specialists | Own and govern canonical catalog truth and central operations. | Authenticated workspace inside the single CatalogHub Admin. |
| CatalogHub Admin — Site Admin workspace | Site operators and scoped specialists | Own local presentation, site configuration, projections, and site-local operations for the active site. | Authenticated workspace inside the same CatalogHub Admin. |
| Public Local Site | Public visitors | Render the active site's published, localized projection and public interactions. | Host- and locale-resolved public runtime outside the admin shell. |

There is one CatalogHub Admin deployment and one authenticated admin shell.
Central Admin and Site Admin are workspace contexts inside it; they are not
separate products, separate per-site applications, or separately deployed admin
panels.

## Canonical ownership rule

> Central owns canonical truth. Site owns local presentation and projections.

This rule is the primary decision boundary for data, permissions, actions, and
screen placement.

### Central-owned truth

Central Admin owns:

- canonical product identity, brand, category, variant, and lifecycle state;
- category schema, sections, attributes, options, facets, comparison behavior,
  units, and display rules;
- canonical product specifications and versions;
- canonical media and global translations;
- import sources, mappings, normalized drafts, duplicates, and errors;
- central price-source ingestion, external mappings, and raw offer quality;
- review and application of Site-to-Central correction requests;
- central conflicts, snapshots/exports, platform users, roles, and audit history.

### Site-owned local state

Site Admin owns, within the active site:

- site identity, domain, market, locale, currency, and SEO defaults;
- category enablement, local category visibility, SEO, and media;
- product visibility and local title, slug, SEO, media, and presentation
  overrides;
- theme, layout template, homepage blocks, and approved feature flags;
- projection inspection, rebuild requests, sync status, and local error handling;
- site price-source selection, local offers, provider/widget presentation, and
  freshness/coverage review;
- review moderation, lead operations, local content, translations, relations,
  and polls;
- creation and tracking of correction requests to Central.

Site Admin may display canonical values for context, but it must not directly
mutate Central-owned truth. A reusable canonical correction is proposed through
the correction-request workflow.

### Projection ownership

Site owns projection policy, visibility, local inputs, preview requests, and
rebuild requests. Projection records are derived artifacts and are never edited
directly. Central owns the canonical inputs. The projection engine
deterministically combines those canonical inputs with Site-owned local inputs.

An editor that writes directly to a projection record, a Site action that writes
a canonical product field, or a Central action that silently overwrites a local
presentation choice violates this contract even if the resulting screen looks
correct.

## Workspace contract

### Shared admin shell

Both workspaces use the same authentication session, design system, top-level
shell, global identity, accessibility rules, notifications/profile affordances,
and audited navigation framework. Workspace selection changes navigation and
capabilities; it does not start a second admin application.

### Central Admin workspace

- Does not require an active site for canonical operations.
- May show site health, projection status, and affected sites as read-only
  operational context.
- Must not expose local override editors, site review moderation, lead handling,
  or homepage composition as Central-owned data.

### Site Admin workspace and site switcher

- Site Admin always has one explicit active site context.
- The site switcher lists only sites the authenticated user is authorized to
  access.
- Switching site changes the active context for all Site Admin navigation,
  queries, actions, counts, breadcrumbs, previews, and links.
- The active site is continuously visible. A screen must not silently fall back
  to an arbitrary site.
- A deep link to a different site must be authorized before context is changed;
  unauthorized site IDs return no data.
- Users with one authorized site still use the same workspace contract; the
  switcher may have one option but there is no separate admin instance.
- Central workspace state and Site workspace state must not leak into one
  another.

Every Site-context implementation must also satisfy all of these invariants:

- every Site mutation carries an explicit, immutable `site_id` captured when the
  operation is submitted;
- every Site queue job carries an explicit, immutable `site_id` in its payload;
- authorization for that `site_id` and operation is rechecked when an
  asynchronous job executes;
- every Site-scoped cache key and query key includes `site_id`;
- switching the selected site in the UI does not change the meaning or target of
  an operation that has already been submitted;
- two browser tabs using different Site contexts remain isolated in navigation,
  reads, writes, jobs, notifications, and caches.

The exact persistence mechanism for authorized sites and active-site state is an
implementation decision for the Admin Shell phase. It must satisfy this contract
and must not be inferred from menu visibility alone.

## Public Local Site contract

- The public runtime resolves a Site from the request host and an enabled locale.
- Catalog pages read published site projections/search documents, not draft
  Central models.
- Local presentation may alter title, slug, SEO, media, blocks, and visibility
  without rewriting canonical data.
- Offers, reviews, leads, and content are site-scoped.
- Hidden, excluded, draft, incompatible, or otherwise non-public projections do
  not appear publicly.
- The approved scope does not include cart, checkout, order, payment, delivery,
  or per-site admin applications.

## Data-flow invariant

```text
Central canonical truth
        |
        | publish/version/import/approved correction
        v
Site eligibility + local presentation + market/locale/theme
        |
        | deterministic projection/rebuild
        v
Site product/category/search/sitemap projections
        |
        v
Public Local Site

Site-discovered canonical issue
        |
        v
Correction request -> Central review -> canonical update -> new projection
```

Local overrides survive Central updates according to explicit conflict rules;
they must never become accidental canonical changes.

## Approved screen map

The authoritative product boundary is
`docs/planning/cataloghub-v2-screen-registry.md`: CA-001 through CA-085,
SA-001 through SA-064, and PUB-001 through PUB-080. A listed ID whose definition
is marked blocked is not implementable until an approved product decision fills
that row; it is not permission to invent the missing screen.

Known visual sources and their reproducibility status are controlled by
`docs/planning/cataloghub-v2-visual-reference-manifest.md`. Local, untracked, or
missing images cannot support reproducible automated visual regression.

Reference images define the expected information hierarchy, visible states,
actions, and visual direction. Their example names, counts, dates, domains, and
metrics are illustrative seed content, not hard-coded production values.

No roadmap phase may introduce an additional product screen. An implementation
route that only supports an approved screen is not a new screen. Any genuinely
new screen requires an explicit product-contract amendment before planning or
implementation.

## Permission invariants

- Server-side authorization and site scoping are mandatory; hidden navigation is
  not an authorization boundary.
- Central mutations require the relevant Central permission.
- Site mutations require both the relevant capability and access to the active
  site.
- Destructive or high-impact actions require confirmation and an auditable
  actor/context.
- Public guests cannot authenticate to or receive admin data.
- Cross-site object resolution must fail closed.

## Phase exit contract

Every future implementation phase ends with all owned approved screens meeting
the following gate:

1. every owned screen is reachable through the correct workspace and contains no
   placeholder controls or dead primary actions;
2. deterministic seed data demonstrates happy, empty, warning, error, and
   permission-relevant states appropriate to the screen group;
3. primary actions persist, validate, report failure, and update the visible
   state;
4. permissions and active-site isolation are tested for allowed and denied
   actors;
5. unit/feature/integration/browser tests for the owned behavior pass;
6. screenshots at the agreed desktop and responsive widths are reviewed against
   the matching approved references;
7. no unrelated product screen, domain, or shared component has been changed;
8. the complete repository gate passes after integration.

A phase is not complete when only migrations, models, resources, service tests,
or static markup exist. The working approved screen is the unit of delivery.

The current delivery mode is serial-first: one agent completes one work package
in one MR at a time. Parallel UI phase execution is not part of this contract.
