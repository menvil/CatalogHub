# Parallel Agent Workflow for Roadmap v2

## Objective

Execute three or four independent screen-driven work packages in parallel
without splitting CatalogHub into separate products, duplicating domain logic,
or allowing shared admin infrastructure to drift.

The unit of assignment is a bounded group of approved screens with its domain,
seed fixtures, actions, permissions, tests, and visual evidence. Agents are not
assigned vague horizontal tasks such as "finish the UI" or "refactor models".

## Mandatory sequence

### Gate 0 — no fan-out

Phase 01 — Admin Shell, Design System, Workspace and Site Switcher — is completed
and merged by one owner before any screen-package fan-out.

The gate requires:

- one working `/admin` shell;
- Central Admin and Site Admin workspace switching;
- a functional authorized-site switcher and active-site contract;
- stable navigation/route/component conventions;
- baseline permission and cross-site tests;
- seeded role/site accounts;
- responsive and visual-test conventions;
- a green integrated repository gate.

Agents must not independently invent workspace state, navigation, page chrome,
tokens, breakpoint behavior, or a second Filament panel.

### Fan-out

After Gate 0, the integration owner publishes a package ledger. Up to four agents
may work concurrently when their allowed paths and owned screens do not overlap.

## Work-package contract

Every assignment contains the following fields before work starts:

```text
Package ID:
Roadmap phase / slice:
Owned screen IDs:
Owned primary actions:
Owned domains:
Dependencies and pinned commit:
Allowed paths:
Forbidden paths:
Seed class / fixture namespace:
Required role and site scenarios:
Required tests:
Required screenshot names and widths:
Out-of-scope findings log:
```

If a requested change falls outside the allowed paths, the agent stops that part,
records the need, and requests a separate dependency/shared MR. It does not edit
the shared file "because the change is small."

## Path ownership rules

### Package-owned paths

An agent may edit only the exact resource/page, action/service, model/migration,
view, factory/seeder, and test paths listed in its package. A broad directory such
as `app/Filament/**` or `tests/Feature/**` is not an acceptable ownership rule.

New files use a package-specific namespace or basename so ownership remains
obvious. Each package gets its own idempotent seeder; agents do not concurrently
edit `DatabaseSeeder.php`.

### Shared hot spots

These paths are always shared and therefore forbidden to ordinary parallel
packages:

- `app/Providers/Filament/AdminPanelProvider.php`;
- workspace and active-site context support;
- `resources/css/app.css`;
- global admin JavaScript;
- `resources/views/components/admin/**`;
- Central/Site admin layouts and shared page chrome;
- `routes/web.php` sections used by more than one package;
- `config/cataloghub_permissions.php` and the global permission matrix;
- `database/seeders/DatabaseSeeder.php`;
- dependency manifests and lockfiles;
- common test bootstrap and visual-test configuration.

### Shared-component MR rule

Shared components change only through a separate, small MR with one purpose. The
MR is owned by the shell/integration maintainer, lands before dependent packages,
and contains:

- the smallest backward-compatible API change;
- component/unit/browser tests;
- before/after evidence on at least one Central and one Site screen when visual;
- migration notes for package owners;
- no domain-specific screen implementation.

Dependent packages rebase after that MR. Multiple agents never carry competing
copies of a shared-component change.

## Recommended parallel waves

The exact wave may change with dependencies, but no wave bypasses Roadmap phase
gates.

### Wave 0 — single owner

| Package | Screens | Allowed emphasis | Forbidden emphasis |
| --- | --- | --- | --- |
| Shell | CA-001, SA-001 | Panel shell, workspaces, switcher, design system, visual harness. | All later domain screens. |

### Wave 1 — four independent packages after Shell

| Package | Screens | Representative allowed paths | Representative forbidden paths |
| --- | --- | --- | --- |
| Central Catalog | CA-002…CA-015 | CentralProduct/CentralBrand resources, actions, models, factories, owned tests/views. | Category schema, shell, Site, imports/pricing. |
| Schema and Units | CA-016…CA-032 | CentralCategory/Facet/Measurement resources, CategorySchema actions, Units/Facets services, owned tests/views. | Product identity, shell, Site, pricing. |
| Central Pricing | CA-066…CA-074 | Pricing resources/actions/services/jobs/adapters, owned tests/views. | Site pricing, shell, catalog forms. |
| Site Foundation | SA-002…SA-012 | Site settings/category pages, Site/Market/Locale/local-category behavior, owned tests/views. | Switcher implementation, canonical schema editors, later Site modules. |

These packages may use already merged v1 domain contracts. If one requires an
unmerged behavior from another, it records a dependency instead of editing the
other package's files.

### Wave 2 — three or four packages after required Wave 1 merges

| Package | Screens | Depends on |
| --- | --- | --- |
| Central Imports | CA-033…CA-043 | Central Catalog + Schema/Units contracts. |
| Central Media/Translations | CA-044…CA-059 | Central Catalog + Schema/Units. |
| Site Products | SA-013…SA-021 | Central Catalog + Site Foundation. |
| Site Themes | SA-022…SA-028 | Site Foundation; Site Products for preview fixtures. |

### Wave 3 — four packages

| Package | Screens | Depends on |
| --- | --- | --- |
| Central Corrections/Conflicts | CA-060…CA-065 | Central Catalog, Media/Translations. |
| Site Sync/Corrections | SA-029…SA-038 | Site Products, Themes, Central correction contract. |
| Site Pricing | SA-039…SA-046 | Central Pricing, Site Products. |
| Site Reviews/Leads | SA-047…SA-055 | Site Foundation, Site Products. |

### Wave 4 — integration-heavy packages

| Package | Screens | Depends on |
| --- | --- | --- |
| Central Snapshots/Users | CA-075…CA-085 | Stable Central modules and shell permission model. |
| Site Content/Polls | SA-056…SA-064 | Site Foundation and Themes. |
| Public Local Site | Approved PUB inventory | All data-producing admin phases required by each public journey. |

Only three packages are recommended in the final wave so one concurrency slot can
be reserved for integration, conflict resolution, and full-gate verification.

## Branch and MR discipline

1. Every package branches from the pinned green integration commit.
2. The first commit may add only package-owned seed/test scaffolding when useful;
   later commits deliver screens vertically, not as disconnected backend/UI
   layers.
3. Each MR contains only its owned screens and paths. Unrelated cleanup is logged,
   not performed.
4. Each MR lists every changed path and confirms it is allowed.
5. The MR includes screen-to-route, screen-to-seed, screen-to-action,
   screen-to-permission, screen-to-test, and screen-to-screenshot traceability.
6. The package gate runs before review. The full repository gate runs again after
   merge.
7. Agents rebase on merged shared/dependency MRs before final acceptance; they do
   not resolve semantic conflicts by choosing one side wholesale.
8. A package is merged only when all owned approved screens are complete. Partial
   backend foundations do not close a phase.

## Seeder coordination

- Each package owns an idempotent seeder such as
  `Database\Seeders\RoadmapV2\<Package>DemoSeeder`.
- Stable natural keys and documented fixture IDs are used for cross-package
  references.
- Package seeders may depend on an already merged seed contract but must not
  delete or rewrite another package's fixtures.
- Only an integration MR edits `DatabaseSeeder.php` to compose completed package
  seeders in deterministic order.
- Synthetic data is used for credentials and PII. Failure states must not require
  external network access.

## Route, permission, and migration coordination

- Route names are reserved in the package ledger before implementation.
- A package may add only routes for its approved screens; common route-group
  changes use a shared MR.
- New permissions are not inferred. Existing permissions are reused unless an
  approved screen cannot be secured correctly; global matrix changes then use a
  shared MR.
- One migration has one owning package. Another package consumes it only after it
  merges.
- Cross-package schema changes require a short data-contract note covering
  ownership, backfill, rollback/forward-fix, factories, and projection impact.

## Visual acceptance coordination

Screenshot artifacts use the approved screen ID and width:

```text
<package>/CA-002-1440.png
<package>/SA-013-375.png
<package>/PUB-005-1280.png
```

The agent records:

- reference image or approved public inventory item;
- seeded scenario and role/site context;
- viewport and browser;
- functional actions exercised before capture;
- accepted deviations and reviewer.

A shared visual-harness defect is fixed once in a small shared MR, not worked
around differently by multiple screen packages.

## Integration owner responsibilities

The integration owner does not silently expand package scope. The owner:

- maintains the package/path/route/seed ledger;
- merges shared prerequisite MRs;
- checks cross-workspace ownership and active-site isolation;
- composes package seeders;
- runs the complete repository gate;
- checks that no unapproved screen entered navigation;
- verifies visual evidence coverage by screen ID;
- records deferred findings without converting them into opportunistic refactors.

## Completion criteria for a parallel wave

A wave completes only when all merged packages are green together, their seeders
compose from a fresh database, permissions hold across package boundaries, the
single admin shell remains consistent, public projection contracts still pass,
and every owned screen has reviewed visual evidence. Merge count or agent
utilization is not a completion metric.
