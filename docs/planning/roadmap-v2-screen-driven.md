# CatalogHub Roadmap v2 — Screen-Driven Delivery

Status: proposed implementation roadmap

Phases: 16 vertical phases

Source of scope: product contract, CA-001…CA-085, SA-001…SA-064, and the
approved public page inventory.

## Roadmap rules

This roadmap preserves verified v1 domain behavior and completes the approved
product surfaces. It does not authorize additional product features or screens.

Every phase delivers its owned screens as working vertical slices. A migration,
model, service, resource, static template, or test in isolation is not a phase
deliverable.

### Mandatory gate for every phase

- All owned screens are reachable from the correct workspace and have working
  primary actions.
- Deterministic seed data demonstrates relevant happy, empty, warning, error,
  stale, and permission states.
- Authorization is enforced server-side, including active-site isolation for Site
  Admin.
- Unit, feature, integration, and screen/browser acceptance tests pass.
- Desktop screenshots are compared with the exact matching reference images;
  responsive evidence is recorded at 375, 768, 1280, and 1440 pixels.
- No unresolved severity-1 visual or functional deviation remains. Accepted
  lower-severity deviations are documented with an owner.
- `composer format:test`, `composer analyse`, `composer test`, and
  `npm run build` pass on the integrated commit.

### File-area enforcement

Allowed areas below are maximum boundaries, not a requirement to edit every
path. Existing persistence and services should be reused when they satisfy the
contract. Migration/model changes require proof that the approved screen cannot
be completed safely with the current schema.

For phases 02–16, the following shared areas are forbidden unless changed in a
separate, small prerequisite MR owned by the shell/integration maintainer:

- `app/Providers/Filament/AdminPanelProvider.php`;
- shared workspace/site-context support;
- `resources/css/app.css` and global admin JavaScript;
- `resources/views/components/admin/**` and admin layouts;
- `composer.json`, `composer.lock`, `package.json`, and lockfiles;
- `database/seeders/DatabaseSeeder.php`;
- global permission configuration;
- unrelated routes or modules.

## Phase 01 — Admin Shell, Design System, Workspace and Site Switcher

- **Owned screens:** CA-001 Central Dashboard; SA-001 Site Dashboard. The shared
  shell behavior is accepted on both screens and becomes mandatory for every
  later CA/SA screen.
- **Owned domains:** authenticated admin context, Central/Site workspace context,
  authorized-site selection, active-site lifecycle, common navigation, design
  tokens, accessibility, dashboard composition.
- **Dependencies:** v1 authentication, `User`, `Site`, permission matrix, existing
  metric/query services, and approved CA-001/SA-001 references.
- **Allowed file areas:** `app/Providers/Filament/AdminPanelProvider.php`;
  `app/Filament/Pages/CentralDashboard.php`; Site Dashboard page; new narrowly
  scoped `app/Filament/Support/*Workspace*` and `*SiteContext*`; relevant
  middleware/policies; `app/Models/User.php` and `app/Models/Site.php` only if the
  access contract requires it; one minimal site-access migration if proven
  necessary; shared admin CSS/JS/components/layouts; phase-specific factories,
  seeders, and Admin/Authorization tests; visual-test tooling configuration.
- **Forbidden file areas:** catalog/import/media/pricing/content domain behavior;
  public controllers/views; unrelated models/migrations; any second Filament
  panel or per-site admin route tree.
- **Required seed data:** Central Admin, Site Admin, specialist and denied-role
  accounts; at least three sites; one user with multiple authorized sites and one
  with a single site; dashboard metrics with healthy, warning, critical, and
  empty queues.
- **Definition of Done:** one `/admin` shell exposes two permission-aware
  workspaces; the Site workspace always shows and changes the active authorized
  site; all dashboard links resolve; dashboard data is real; cross-workspace and
  cross-site context cannot leak; no placeholder/disabled shell controls are
  presented as functional.
- **Test acceptance:** workspace switch, persistence and deep-link tests; allowed
  and denied site-resolution tests; navigation tests for every role; dashboard
  query/action tests; accessibility and responsive browser smoke tests.
- **Visual acceptance:** CA-001 and SA-001 match their references in hierarchy,
  navigation, context controls, cards, tables/charts, alerts, and quick actions;
  shell screenshots pass at all four target widths and establish the baseline for
  later phases.

## Phase 02 — Central Products and Brands

- **Owned screens:** CA-002…CA-010 Products List, Product Detail, Product
  Create/Edit, Product Variants, Product Specs Editor, Product Media Manager,
  Product Translations, Product Version History, Product Data Quality View;
  CA-011…CA-015 Brands List, Brand Detail, Brand Create/Edit, Brand Media/Logo,
  Brand Translations.
- **Owned domains:** canonical product/variant/brand identity and lifecycle,
  product attributes, versions, catalog quality, canonical brand media and
  translation composition.
- **Dependencies:** Phase 01; existing Central catalog models/actions/policies;
  current media and translation services as stable dependencies.
- **Allowed file areas:** CentralProduct/CentralBrand Filament resources and
  pages; `app/Actions/CentralCatalog/**`, `ProductAttributes/**`, `Versions/**`;
  relevant Central catalog models/policies/services/queries; screen-specific
  Central views; product/brand factories and a phase-owned seeder; matching
  Feature/Unit/Authorization tests; narrowly named migrations only when an
  approved field/state is absent.
- **Forbidden file areas:** shared shell; Site models/resources; imports, pricing,
  sync, themes, public pages; generic media/translation internals unless a
  separately reviewed dependency MR is required.
- **Required seed data:** multiple brands and product states; products with and
  without variants/media/translations/required specs; archived and restored
  products; version history; duplicate and quality-warning examples.
- **Definition of Done:** all fourteen screens support the reference filters,
  details, tabs, previews and primary actions using canonical services; product
  lifecycle/version rules remain intact; brand usage and assets are visible; no
  Site-local field is edited here.
- **Test acceptance:** CRUD/lifecycle/variant/spec/version/media/translation
  journeys; action failure/validation tests; permission matrix for Central Admin,
  Catalog Editor, Translator and denied Site Admin; screen route and seed smoke.
- **Visual acceptance:** one approved screenshot per CA-002…CA-015 plus empty,
  validation, archive, missing-data and responsive evidence; comparison is made
  against the identically numbered PNG.

## Phase 03 — Central Categories, Schema and Units

- **Owned screens:** CA-016…CA-026 Categories List, Category Detail, Category
  Create/Edit, Category Schema Builder, Attribute Sections, Definitions and
  Options Editors, Facet Config, Comparison Config, SEO Templates, Category
  Translation Editor; CA-027…CA-032 Measurement Dimensions, Measurement Units,
  Unit Aliases, Unit Translations, Market Unit Preferences, Attribute Display
  Rules.
- **Owned domains:** category hierarchy/lifecycle, schema status/validation,
  sections/attributes/options, facets/comparison/SEO templates, units,
  conversions, aliases, preferences, and display rules.
- **Dependencies:** Phase 01; v1 category/schema/unit models and services; Phase 02
  only for representative product impact previews, not for shell work.
- **Allowed file areas:** CentralCategory, Facet, Measurement, MarketUnit and
  AttributeDisplay Filament resources/pages; `app/Actions/CategorySchema/**`;
  category-schema, facets, units and product-attribute services; relevant models,
  enums, DTOs, rules, factories, phase seeder, tests and narrowly named
  category/attribute/facet/unit migrations.
- **Forbidden file areas:** shared shell; product identity CRUD; imports/media
  ingestion; Site workspace; pricing; public rendering except read-only test
  fixtures in integration tests.
- **Required seed data:** category tree with active/draft/incomplete states; a
  Gaming Monitors-style schema with multiple sections/data types/options;
  invalid and approval-ready schemas; facet/comparison/SEO configurations;
  metric/imperial units, aliases, locales and market preferences.
- **Definition of Done:** all seventeen screens compose one safe schema workflow;
  ordering, validation, clone/review/approve/archive/export and configuration
  actions work; units convert and preview correctly; schema impact is visible
  without mutating Site state.
- **Test acceptance:** hierarchy and lifecycle tests; every schema action and
  validation failure; unit parsing/conversion/alias/preference tests; permission
  tests; projection/import contract regression tests for affected fields.
- **Visual acceptance:** CA-016…CA-032 are individually reviewed against their
  references, including drag/order states, previews, validation issues, empty
  options and responsive overflow behavior.

## Phase 04 — Central Imports

- **Owned screens:** CA-033…CA-043 Import Sources, Import Batches List, Import
  Batch Detail, Import Wizard, Raw Product Viewer, Normalized Draft Review,
  Mapping Rules Editor, Unmapped Fields, Duplicate Candidates, Normalization
  Errors, Media Download Errors.
- **Owned domains:** source configuration, batch/artifact lifecycle, raw
  persistence, mapping, normalization, review/publish/reject, duplicates, and
  import media failure handling.
- **Dependencies:** Phases 01–03; existing import contracts/services/jobs/actions;
  stable canonical schema and unit rules.
- **Allowed file areas:** `app/Filament/Pages/ImportWizard.php` and Import/Raw/
  Mapping/Draft/Error/Duplicate resources; `app/Actions/Imports/**`;
  `app/Services/Imports/**`; `app/Contracts/Imports/**`; `app/Jobs/Imports/**`;
  import models/importers/DTOs/factories/views; phase seeder and import tests;
  narrowly named import migrations only when required.
- **Forbidden file areas:** shared shell; canonical editor behavior; generic media
  library; Site workspace, pricing, public pages, themes and sync internals.
- **Required seed data:** at least two sources and batches in queued/running/
  needs-mapping/review/failed/completed states; raw payloads; high/low confidence
  drafts; unmapped fields; duplicates; normalization and media-download errors;
  an approvable draft.
- **Definition of Done:** an operator can configure/start an import, follow batch
  progress, inspect raw data, resolve mapping/error/duplicate issues, review a
  normalized draft and publish it through existing canonical actions; retries
  and failures remain idempotent and visible.
- **Test acceptance:** full seeded import journey; job retry/idempotency tests;
  mapping/unit/enum normalization; duplicate/error resolution; authorization;
  offline smoke without production network dependency.
- **Visual acceptance:** CA-033…CA-043 match their references for queues, filters,
  progress, side-by-side review, errors and empty states at desktop and required
  responsive widths.

## Phase 05 — Central Media and Translations

- **Owned screens:** CA-044…CA-051 Media Library, Asset Detail, Upload, Variants
  Preview, Assignments, Localized Media Manager, Sources/Licenses, Integrity
  Report; CA-052…CA-059 Translation Dashboard, Missing, Outdated, Product,
  Category, Attribute and Unit Editors, Bulk Translation Review.
- **Owned domains:** canonical assets/variants/assignments/sources, localized
  media, integrity, canonical translations, source hashes, status/approval,
  coverage and bulk review.
- **Dependencies:** Phases 01–03; existing media and translation actions/services;
  product/brand/category/unit records from Phases 02–03.
- **Allowed file areas:** Central Media/Translation controllers and routes only;
  media/translation Filament pages/resources; `app/Actions/Media/**` and
  `Translations/**`; media/translation services, queries, models, value objects,
  jobs and Central views; related factories/phase seeder/tests; narrowly named
  media/translation migrations if required.
- **Forbidden file areas:** shared shell; unrelated Central CRUD; Site-local media
  override screens; imports except read-only source linkage; pricing/themes/
  public rendering.
- **Required seed data:** original assets and variants in processing/ready/failed/
  missing states; global/localized assignments; licensed/broken sources;
  integrity failures; multiple locales and entity translations in missing,
  outdated, draft, review and approved states.
- **Definition of Done:** upload/generate/assign/localize/source/integrity actions
  and every translation edit/review action work in the Central workspace;
  `/central/*` presentation is reconciled into the one admin experience; bulk
  review preserves per-record authorization and audit data.
- **Test acceptance:** storage/variant/assignment/source/integrity journeys;
  translation save/hash/outdated/approve/bulk tests; media and translator role
  boundaries; broken-file and cross-entity validation.
- **Visual acceptance:** CA-044…CA-059 are reviewed individually against the
  corresponding PNGs, including upload progress, variant grids, side-by-side
  translation, coverage and report states.

## Phase 06 — Central Change Requests and Conflicts

- **Owned screens:** CA-060 Change Requests Queue; CA-061 Change Request Detail;
  CA-062 Correction Diff Viewer; CA-063 Conflicts List; CA-064 Conflict Resolver;
  CA-065 Data Source Comparison.
- **Owned domains:** Central review of Site corrections, evidence, canonical diff,
  conflict lifecycle, data-source comparison, approval/rejection/application and
  version/projection consequences.
- **Dependencies:** Phases 01–05; existing request/conflict/version actions;
  Site-submitted records may be seeded until Phase 12 owns the Site UI.
- **Allowed file areas:** ChangeRequest, SyncConflict and ProjectionConflict
  resources/pages; `app/Actions/Corrections/**`, `Sync/**`, `Versions/**` only for
  owned behavior; request/conflict services/models/policies/components; phase
  factories/seeder/tests; narrowly named correction/conflict migrations.
- **Forbidden file areas:** shared shell; Site correction screens; catalog forms
  except approved apply action integration; pricing/import UI; public pages.
- **Required seed data:** requests in every approved lifecycle state, evidence and
  missing-evidence cases, duplicate/conflicting proposals, source comparisons,
  resolvable and blocked conflicts, impacted product versions/sites.
- **Definition of Done:** reviewers can triage, compare, comment where approved,
  approve/reject/apply and resolve with auditable outcomes; applying a canonical
  change increments the version and schedules only affected projections.
- **Test acceptance:** lifecycle and invalid-transition tests; diff/evidence
  rendering; permission and concurrency tests; version/rebuild integration;
  duplicate and blocked conflict behavior.
- **Visual acceptance:** CA-060…CA-065 match queue density, detail hierarchy,
  side-by-side diffs, source evidence and resolver states in their references.

## Phase 07 — Central Price Sources

- **Owned screens:** CA-066…CA-074 Price Sources List, Price Source Detail,
  Create/Edit, Credentials, Sync Logs, Raw Price Offers Viewer, External Product
  Mapping, Mapping Approval Queue, Price Source Error Report.
- **Owned domains:** central source configuration/credentials, adapters,
  scheduling/retry, raw offer normalization, external mapping review, logs,
  health and errors.
- **Dependencies:** Phase 01, canonical products from Phase 02, markets from v1,
  and existing pricing pipeline.
- **Allowed file areas:** PriceSource/SyncLog/RawOffer/ExternalMapping Filament
  resources/pages/widgets; `app/Pricing/**`; `app/Actions/Pricing/**`;
  `app/Services/Pricing/**`; pricing jobs/models/queries/data/contracts; phase
  factories/seeder/tests; narrowly named price-source migrations.
- **Forbidden file areas:** shared shell; Site pricing presentation/configuration;
  catalog editors; import pipeline; public offer UI.
- **Required seed data:** API/feed/manual sources across markets; healthy/delayed/
  failing/disabled states; configured/missing/invalid credentials without real
  secrets; sync logs; valid/malformed/stale raw offers; pending/approved/rejected
  mappings and errors.
- **Definition of Done:** safe source and credential management, sync inspection,
  raw-offer review, manual mapping and approval actions operate with redacted
  secrets and truthful health/error states.
- **Test acceptance:** credential encryption/redaction; adapter normalization;
  job retry/idempotency; mapping transitions; authorization; safe failure and
  queue/status integration.
- **Visual acceptance:** CA-066…CA-074 match their list/detail/form/log/queue/report
  references with seeded health, error and empty states.

## Phase 08 — Central Snapshots, Backup Status, Users and Roles

- **Owned screens:** CA-075…CA-081 Snapshots List, Snapshot Detail, Create
  Snapshot, Export History, Media Manifest Viewer, Backup Status, Restore
  Checklist; CA-082…CA-085 Users List, User Create/Edit, Roles & Permissions,
  Activity Log.
- **Owned domains:** portable catalog snapshots/exports/manifests/checksums,
  backup-status reporting, restore readiness, admin users, existing roles and
  permissions, activity audit.
- **Dependencies:** Phases 01–07 for representative export content and role-aware
  navigation; v1 export/backup services and permission matrix.
- **Allowed file areas:** CatalogSnapshot/MissingMedia resources/pages/widgets;
  export/backup services and commands; User/role/activity Filament resources;
  User/policy/permission support only for approved management behavior; relevant
  models/factories/phase seeder/tests; narrowly named snapshot/user/activity
  migrations; snapshot download route only.
- **Forbidden file areas:** shared shell except separate prerequisite MR;
  canonical module behavior; Site workspace; infrastructure backup automation;
  public pages; new role types not present in the approved model.
- **Required seed data:** snapshots/exports in queued/running/ready/failed/expired
  states, manifests and checksum failures, truthful backup-health examples;
  accounts for every existing role, invited/active/disabled states, permission
  examples and activity entries.
- **Definition of Done:** export/snapshot/manifests/download/check/restore-readiness
  workflows are complete and accurately distinguish portable export from full
  backup; authorized admins can manage users and existing role assignments and
  inspect activity without privilege escalation.
- **Test acceptance:** generation/download/checksum/manifest/failure tests;
  authorization and path-safety tests; user lifecycle and role escalation denial;
  activity recording; seed smoke.
- **Visual acceptance:** CA-075…CA-085 match references and terminology; status,
  manifest, permission matrix and audit detail remain usable responsively.

## Phase 09 — Site Settings and Category Presentation

- **Owned screens:** SA-002…SA-012 Site Settings, Domain Settings, Market
  Settings, Locale Settings, Currency Settings, SEO Defaults, Enabled Categories,
  Category Visibility, Category Local SEO, Category Local Media, Category Page
  Preview.
- **Owned domains:** active-site identity/domain, market/locale/currency, SEO
  defaults, category eligibility/visibility and local category presentation.
- **Dependencies:** Phase 01 site switcher; Phase 03 canonical categories/schema/
  units; Phase 05 media/translations.
- **Allowed file areas:** Site/Market resources and Site settings/category pages;
  `app/Actions/Sites/**` for settings/category behavior; Site/Market/Locale/
  SiteCategory/SiteOverride/MediaAssignment models and policies; category
  projection preview support; site-specific factories/phase seeder/tests;
  narrowly named site settings/category migrations.
- **Forbidden file areas:** shared shell/site switcher; canonical category/schema
  editors; product, pricing, review, lead, content and theme behavior; public page
  redesign.
- **Required seed data:** at least three switchable sites with distinct domains,
  markets, locales and currencies; enabled/hidden/incomplete categories; local
  SEO/media overrides; missing content and preview-ready projections.
- **Definition of Done:** every setting action is active-site scoped, validated and
  previewable; Site Admin can control only local category presentation and cannot
  mutate canonical category/schema data.
- **Test acceptance:** domain/market/locale/currency validation; category enable/
  visibility/SEO/media actions; cross-site denial; projection preview updates;
  role matrix and deep-link tests.
- **Visual acceptance:** SA-002…SA-012 match the corresponding references with the
  persistent switcher, summaries, forms, tables, previews and responsive states.

## Phase 10 — Site Products, Overrides and Projection Preview

- **Owned screens:** SA-013…SA-021 Site Products List, Product Visibility Manager,
  Product Local Detail, Local SEO Override, Local Media Override, Local
  Title/Slug Override, Product Projection Preview, Products Without Projection,
  Stale Products.
- **Owned domains:** active-site product eligibility/visibility, local
  presentation overrides, projection inspection, missing/stale detection.
- **Dependencies:** Phases 01, 02, 05 and 09; existing projection and override
  services.
- **Allowed file areas:** SiteResource product/override/SEO pages and
  SiteProductProjection/Stale resources; `app/Actions/Sites/**` for owned product
  behavior; site product/override/projection queries/services/models/policies;
  screen-specific views; factories/phase seeder/tests; narrowly named site
  product/override migrations.
- **Forbidden file areas:** shared shell; canonical product/spec editors; generic
  projection engine internals except a separately reviewed defect fix; themes,
  pricing, corrections, public UI.
- **Required seed data:** visible/hidden/excluded/draft products; local SEO/media/
  title/slug overrides; canonical changes with preserved overrides; missing,
  stale, blocked and current projections across multiple sites.
- **Definition of Done:** Site Admin can manage approved local fields and
  visibility, inspect the resulting projection and resolve missing/stale work
  through approved actions; canonical fields remain immutable.
- **Test acceptance:** visibility and every override journey; slug/locale/media
  validation; cross-site and canonical-mutation denial; stale/missing detection;
  projection preview consistency.
- **Visual acceptance:** SA-013…SA-021 match list/detail/editor/preview/report
  references, including warning badges, filters and before/after previews.

## Phase 11 — Site Themes, Layouts, Homepage Blocks and Feature Flags

- **Owned screens:** SA-022…SA-028 Theme Selection, Theme Compatibility Check,
  Theme Settings, Homepage Blocks Editor, Layout Templates Preview, Block Config
  Editor, Feature Flags.
- **Owned domains:** theme manifests/activation/settings, compatibility, layout
  templates, homepage blocks/configuration, approved Site features.
- **Dependencies:** Phases 01, 09 and 10; existing theme/block registry and
  validators.
- **Allowed file areas:** Site theme/home-block pages and new screen-specific
  pages; `app/Domains/Themes/**`; theme/block actions/models/config/views;
  SiteFeature relation behavior; factories/phase seeder/tests; narrowly named
  theme/block migrations.
- **Forbidden file areas:** shared shell; canonical catalog; sync/pricing/review/
  lead/content behavior; public page redesign beyond preview adapters owned here.
- **Required seed data:** active/draft themes; compatible/warning/incompatible
  manifests; multiple templates; valid/invalid blocks; missing referenced items;
  enabled/disabled/role-restricted feature flags.
- **Definition of Done:** users can check compatibility before activation, edit
  approved settings, preview/select layouts, compose/configure/reorder blocks and
  manage approved flags; invalid combinations are blocked and explained.
- **Test acceptance:** manifest/config validation, compatibility outcomes,
  activation, block actions/order, feature permissions, cross-site isolation and
  preview integration.
- **Visual acceptance:** SA-022…SA-028 match cards, compatibility results, live
  previews, block editor and flag states in the references at all target widths.

## Phase 12 — Site Sync and Correction Requests

- **Owned screens:** SA-029…SA-035 Sync Dashboard, Sync Logs, Projection Jobs,
  Projection Errors, Manual Sync Product, Manual Sync Category, Manual Sync Whole
  Site; SA-036…SA-038 Create Correction Request, My Correction Requests,
  Correction Request Detail.
- **Owned domains:** active-site sync observability and manual rebuild requests;
  Site creation/tracking of canonical correction proposals.
- **Dependencies:** Phases 06, 09–11; existing projection/sync/correction services,
  jobs, logs and actions.
- **Allowed file areas:** Sync/Projection resources/pages scoped to Site;
  CorrectionRequest resources/pages; projection/sync/correction actions/jobs/
  services/queries/models/policies for owned behavior; factories/phase seeder/
  tests; narrowly named sync/correction migrations.
- **Forbidden file areas:** shared shell; Central conflict/review UI; projection
  payload shape outside approved fixes; catalog editors; pricing/public UI.
- **Required seed data:** sync logs/jobs in queued/running/success/warning/failed
  states; product/category/site impact estimates; stale/error examples; correction
  drafts/submitted/reviewing/approved/rejected/needs-info with evidence/comments.
- **Definition of Done:** Site Admin can understand sync health, inspect failures,
  request the three approved rebuild scopes with impact confirmation, and create/
  track correction requests without direct canonical mutation.
- **Test acceptance:** active-site log/job/error isolation; manual-sync impact,
  confirmation, dispatch and idempotency; correction lifecycle/evidence; denied
  canonical edits; integration with Central review.
- **Visual acceptance:** SA-029…SA-038 match dashboards, charts/tables, error
  diagnostics, manual-sync checklists and correction detail timelines in the
  approved references.

## Phase 13 — Site Price Sources and Local Offers

- **Owned screens:** SA-039…SA-046 Site Price Sources, Offer Provider Settings,
  External Widget Config, Local Offers List, Local Offer Editor, Products Without
  Offers, Price Freshness Report, Price Coverage Dashboard.
- **Owned domains:** active-site source selection/configuration, provider/widget
  presentation, local offers, freshness and coverage.
- **Dependencies:** Phases 07, 09, 10 and 12; central normalized offers and market
  configuration.
- **Allowed file areas:** Site pricing pages under SiteResource or a Site-workspace
  resource; site pricing actions/services/queries/data/models/policies/views;
  approved widget configuration; factories/phase seeder/tests; narrowly named
  site price/offer migrations.
- **Forbidden file areas:** shared shell; central credentials/adapters/source
  ingestion; catalog editors; unrelated sync/public rendering.
- **Required seed data:** active/paused/failing site sources; provider modes and
  widget configurations; fresh/stale/expired local offers; products with no
  offers; merchants/categories with high/low coverage.
- **Definition of Done:** Site Admin can configure allowed sources/provider
  presentation, manage approved local offers, and act on freshness/coverage gaps
  without accessing Central credentials or changing canonical products.
- **Test acceptance:** source eligibility and active-site scoping; offer
  validation/history/freshness; widget safety/fallback; coverage/no-offer queries;
  permission and cross-site tests.
- **Visual acceptance:** SA-039…SA-046 match the approved settings, offer table/
  editor, warnings, trend charts and coverage breakdowns.

## Phase 14 — Site Reviews and Leads

- **Owned screens:** SA-047…SA-050 Reviews List, Review Detail, Review Moderation
  Queue, Review Settings; SA-051…SA-055 Leads List, Lead Detail, Lead Status Board,
  Lead Form Settings, Lead Notifications Settings.
- **Owned domains:** site review moderation/settings and lead intake, assignment,
  status, form and notification configuration.
- **Dependencies:** Phases 01, 09 and 10; v1 public forms, actions, policies,
  notification and rate limits.
- **Allowed file areas:** Review/Lead Filament resources and new approved pages;
  `app/Actions/Reviews/**`, `Leads/**`; review/lead models/policies/services/
  Livewire forms/notifications for owned behavior; site settings for approved
  form/moderation options; factories/phase seeder/tests; narrowly named review/
  lead settings migrations.
- **Forbidden file areas:** shared shell; canonical catalog; content/polls;
  pricing/sync; unrelated public page layout.
- **Required seed data:** reviews in pending/approved/rejected/spam/reported states;
  empty and busy moderation queues; leads in every board state with product/
  category/general contexts, assignments, spam, form variants and notification
  outcomes, using synthetic PII.
- **Definition of Done:** moderators and support operators can complete every
  approved list/detail/queue/board/settings action within the active site;
  public intake reflects settings and preserves privacy/consent/rate limits.
- **Test acceptance:** moderation/status/assignment/settings/notification
  journeys; site/role isolation; public validation/consent/rate-limit regression;
  PII-safe logging and export behavior.
- **Visual acceptance:** SA-047…SA-055 match the references for dense lists,
  detail sidebars, queues, board columns and settings previews at target widths.

## Phase 15 — Site Content and Polls

- **Owned screens:** SA-056…SA-061 Content List, Article Editor, Guide Editor, FAQ
  Editor, Content Translation Editor, Content Relations; SA-062…SA-064 Polls List,
  Poll Editor, Poll Results.
- **Owned domains:** site content lifecycle/types/translations/relations and the
  approved poll lifecycle/options/responses/results.
- **Dependencies:** Phases 01, 09–11; v1 content models/actions/queries and public
  block registry.
- **Allowed file areas:** ContentItem resources/pages/relation managers and new
  Poll pages; `app/Actions/Content/**` and narrowly scoped poll actions;
  content/poll models/enums/policies/queries/services/views/config; factories/
  phase seeder/tests; narrowly named poll or content migrations required by the
  approved screens.
- **Forbidden file areas:** shared shell; canonical catalog; reviews/leads;
  pricing/sync; general public layout except owned content/poll rendering
  integration.
- **Required seed data:** articles/guides/FAQs in draft/scheduled/published/
  archived states; multiple locales and translation quality states; product/
  category/brand/content relations including broken/hidden targets; polls in
  draft/active/closed states with options and deterministic responses.
- **Definition of Done:** each approved content type has appropriate validation
  and publication behavior; translations/relations are visible and safe; poll
  creation, publication, closing and results are validated and site-scoped; no
  arbitrary unvalidated poll JSON substitutes for the approved workflow.
- **Test acceptance:** type-specific editors and publication; slug/translation/
  relation integrity; poll lifecycle, vote/result integrity and permissions;
  cross-site denial; public content/poll regression.
- **Visual acceptance:** SA-056…SA-064 match their list/editor/translation/
  relations/results references, including rich content, previews, charts and
  responsive behavior.

## Phase 16 — Public Local Site Integration and Visual Acceptance

- **Owned screens:** the existing approved Public inventory only:
  PUB-001 Multi-category Home, PUB-002 Single-category Home, PUB-003 Category,
  PUB-004 Product Listing, PUB-005 Product Detail, PUB-006 Compare, PUB-022
  Desktop Facets, PUB-023 Mobile Facet Drawer, PUB-046 Offers Table, PUB-057
  Repair Lead Form, plus the already named approved Search, Content, Review,
  Lead, and System states in the discovery inventory.
- **Owned domains:** host/locale resolution, projection-only public reads, theme
  rendering, navigation, search/facets/comparison, offers, reviews/leads, content/
  polls, SEO and responsive behavior.
- **Dependencies:** all prior phases and their seeded integration data.
- **Allowed file areas:** `app/Http/Controllers/Public/**` and public requests/
  queries/data; `app/Domains/PublicSite/**`; public-only theme renderers/services;
  `resources/views/public/**`, public CSS/JS/components; public routes only;
  integrated public demo seeders/factories/tests and narrowly scoped projection
  fixes required by an approved page.
- **Forbidden file areas:** admin shell/workspaces; Central or Site admin screens;
  canonical write paths; new public product features or page types; cart,
  checkout, orders and payments.
- **Required seed data:** the three approved demo configurations with complete
  localized categories/products/media/specs/facets/comparison/offers/reviews/
  leads/content/polls; current/stale/no-offer/no-result/hidden/error states; stable
  hosts and URLs.
- **Definition of Done:** every approved public page/state resolves by host and
  locale from published projections, respects local presentation/visibility,
  provides its approved interactions and SEO, and remains usable across target
  widths; no draft Central record is read as public content.
- **Test acceptance:** end-to-end journeys for each demo; projection-only query
  boundaries; visibility/locale/host/SEO tests; facets/search/compare/offers;
  review/lead/poll validation and rate limits; accessibility, performance budget,
  404/error and smoke tests.
- **Visual acceptance:** a versioned screenshot set covers every approved PUB page
  and named state at the applicable target widths; listing desktop/mobile states
  and offer-table responsiveness receive explicit review and product sign-off.

## Completion of Roadmap v2

Roadmap v2 is complete only when all 85 Central Admin references, all 64 Site
Admin references, and the approved Public inventory have a traceable route,
seeded scenario, working action set, permission evidence, automated acceptance,
and signed visual evidence inside the single product contract.
