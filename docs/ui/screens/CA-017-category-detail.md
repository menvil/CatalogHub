---
screen_id: CA-017
context: central-admin
purpose: Inspect one canonical Category with its real hierarchy, schema, direct usage, localized description and attributable activity.
roles: Central Admin and Catalog Editor with Category capability and active Central admission
route: /admin/central/categories/{category}
viewports: desktop=1440x1000;medium=1024x900;tablet=768x1024;mobile=390x844
fixture: category-detail-v1
regions: central-shell;breadcrumbs;identity-actions;category-information;schema-summary;usage-summary;site-selection;translation-summary;recent-activity
actions: back-to-categories;open-ancestor;copy-id;copy-slug;select-locale;edit-existing-category;open-existing-schema;open-exact-locale-translation;activate;archive;restore
states: populated;empty;archived;read-only;no-active-locales;missing-description;fallback-description;hierarchy-unavailable;no-activity;validation-error;forbidden;not-found
permissions: catalog.categories.manage;central.panel.access;central.page.access;central.mutation.execute for writes;catalog.schema.manage for schema destination;translations.manage for exact-Locale translation destination
responsive: Three desktop columns; two columns at 1024px; one column at 768px and 390px; wrapping header controls, readable activity and shared navigation drawer.
out_of_scope: CA-018-through-CA-026;schema-editing;translation-editing;publication;quality-score;comparison-sets;facet-sets;channels;variants;duplicate;export-category;hard-delete;bulk-actions;full-activity-screen;shared-shell-redesign
reference_version: categories-schema-prototype-v1
visual_acceptance: pending-product-owner-review
---

# CA-017 — Category Detail

Phase 19.5 implements the canonical operational overview at `GET /admin/central/categories/{category}`, named `central.categories.show`. Binding uses the numeric Category ID; slug and canonical source name are mutable administrative reference text. Nonexistent IDs return 404. Authorized operators can read Archived Categories. No migration or domain authority changes are introduced.

## Product contract and prototype mapping

The immutable source is `pictures/1. Central Admin/1.4. Categories : Schema/CA-017 — Category Detail.png`, 1448×1086, SHA-256 `afa4cd4539d19818effb5796ef4e6b1da23c0c41dcd36fe144d6e92ae51a20de`, version `categories-schema-prototype-v1`.

The source's header/actions and Information → Schema Summary → Usage Summary cards remain the primary grouping. The second row contains Selected by Sites, Translations and Recent Activity. The existing Central shell is unchanged.

| Source concept | Implemented meaning |
| --- | --- |
| Category visual | Shared fallback Category icon; no uploaded image/color. |
| Description | Clearly labeled localized CategoryTranslation description, exact Locale status and resolver fallback provenance. |
| Schema Summary | Local Sections, assignments, required assignments, canonical Facets and configured comparison attributes. |
| Comparison Sets / Facet Sets | Omitted; canonical row counts use their actual domain names. |
| Sites / publication | Selected by Sites through retained SiteCategory relationships. |
| Created By / Updated By | Exact Category-created actor and explicitly labeled last Category identity-update actor, otherwise Not recorded. |
| Quick Actions | Real existing editor/schema/translation destinations in their owning cards; no speculative shortcuts. |
| Tabs, Duplicate, Export Category, Channels, variants, SEO | Omitted; their unsupported workflows are not implemented. |
| Quality score / monthly growth | Omitted; no fabricated readiness or analytics. |

## Identity, hierarchy and navigation

Identity shows source/reference name, stable ID, slug, actual created_at/updated_at, separate Category and Schema lifecycle badges, parent and ancestry. ID/slug copy buttons use the browser clipboard, accessible live feedback and a manual-copy message on failure. Copying performs no write or audit.

One minimal Category ID/parent/name snapshot supplies ancestry. Iteration tracks visited IDs, terminates on cycles or missing parents and displays Hierarchy unavailable without repairs. Breadcrumbs link Categories → real ancestors → current Category using stable IDs and the same Category read authorization, preserving selected Locale context. There is no inherited schema or inherited usage. CA-016 source identity now links here; its one Categories navigation entry and legacy Filament list redirect remain intact.

## Schema summary and validation

| Fact | Exact source |
| --- | --- |
| Sections | Category-local AttributeSection rows. |
| Attributes | CategoryAttributeAssignment rows, including hidden assignments. |
| Required Attributes | Local assignments with is_required=true. |
| Facets | Canonical FacetDefinition.category_id rows, including inactive/hidden configuration, matching CA-016. |
| Configured comparison attributes | CategoryComparisonAttribute rows, including hidden configuration; no comparison-set catalog. |
| Schema lifecycle | Existing Draft / Reviewed / Approved / Archived enum. |
| Revisions | Actual current schema_revision, reviewed revision and approved revision when present. |
| Attribution | Actual schema review/approval actor and timestamps; unavailable actor is labeled honestly. |

The existing CategorySchemaValidator runs once with batched relations. Issue count is separate from both lifecycles; at most five real issue descriptions are displayed. No second validator, persisted readiness, quality score, inline schema mutation or approval shortcut exists. View Schema targets the existing temporary `central-categories/{record}/schema` builder only with independent schema permission.

## Usage and Site selection

Direct Products count all persisted central_products rows whose central_category_id equals this ID, across Draft/Active/Archived. Parent/child Products and SiteProducts are excluded. No fake filtered Product destination is added.

Selected by Sites counts distinct non-deleted administrable Sites (Draft, Active, Suspended) with a retained SiteCategory row for this Category. Archived/soft-deleted Sites are excluded; disabled and hidden selections still count. At most eight names/statuses appear, with a remainder count. These chips are read-only text and grant no Site navigation or membership access. A selection is not publication, visibility or availability.

## Locale, description and translation semantics

GET `locale` is an optional positive active Locale ID. Explicit values are validated by the request and rechecked against the authoritative active-Locale snapshot before rendering. Stale/inactive/unknown values reject with the normal locale validation error. Initial context uses the first active default Locale, then active Locale position/code/ID order, then no Locale. Selecting a Locale remains URL-addressable and read-only.

The active-Locale denominator is the same as CA-016. Covered includes MachineTranslated, HumanReviewed and Approved; Missing includes absent rows and explicit Missing; Outdated is separate. The selected Locale always shows its exact status. No active Locales means a neutral summary without a percentage.

Description resolution delegates to TranslationResolver. The rendered description identifies the supplying Locale when it falls back and retains the exact selected-Locale status. Fallback never turns Missing into covered or Approved. No available description yields a truthful empty state; source identity remains readable. No translation rows are created during GET.

Edit Locale translation opens only the existing exact-Locale Category translation editor with its owning permission and active context. CA-026 is not implemented.

## Activity and attribution

CategoryActivityQuery reads a strict allowlist of actual central AuditLogEntry actions whose subject_type is the Category morph class and subject_id is this ID, with site_id null. One explicit exception includes genuine CategoryHierarchyScope reorder events only when SQL JSON membership proves this numeric Category ID was in the recorded ordered_ids. It retrieves the latest **12**, ordered created_at DESC, id DESC, and batches actor names. Category create/update/reparent/lifecycle, schema lifecycle/invalidation, assignment, facet and comparison actions are eligible only when their recorded subject proves ownership.

Unrelated hierarchy-scope events, global AttributeDefinition events and unrelated Category events are excluded. Reorder attribution uses recorded IDs, not the Category’s current parent; later reparenting does not erase genuine history or attribute it to a new parent. Current assignment membership does not prove historical ownership. Section actions that record Category schema invalidation are visible through that genuine Category event; no synthetic section history is inferred. Retrieval uses SQL scope/limit rather than scanning all audit JSON.

A separate limited lookup identifies the earliest Category-created event actor for Created By. The latest exact Category-updated event supplies **Last identity update by**; unrelated schema activity is never presented as a Category identity update. Null actor is Not recorded; dangling/deleted actor is Actor unavailable. Actor IDs and timestamps are actual historical values. Safe summaries allow only action labels, known lifecycle values and positive integer schema revisions, never arbitrary payloads, credentials, emails or raw JSON. No full Activity destination or editing workflow is invented.

## Lifecycle and authorization

| Current Category state | Commands |
| --- | --- |
| Draft | Activate → Active; Archive → Archived. |
| Active | Archive → Archived. |
| Archived | Restore → Draft. |

Header controls require active Central admission, Category capability and central.mutation.execute. Both list and detail call the same existing Activate/Archive/Restore actions. An optional expected status is checked after the existing row lock, so stale confirmations reject without writes or audit. Archive requires server-side confirmation; shared keyboard/focus dialogs also confirm Activate and Restore. Restore explicitly explains the return to Draft. Successful commands redirect to this stable-ID detail with validated Locale context and success feedback; no arbitrary return URL is accepted. Schema lifecycle and retained data are preserved.

| Actor/access | Detail read | Category commands / Edit | Schema link | Translation link |
| --- | --- | --- | --- | --- |
| Active Central Admin with Category capability | Allow | With Central mutation | With schema capability | With translation capability |
| Catalog Editor with Category capability | Allow | With Central mutation | Independently authorized | Independently authorized |
| Category reader without Central mutation | Allow | Deny / absent | Independent schema read | Independent translation read |
| Guest, disabled, Site-only, Moderator, Translator without Category capability | Deny | Deny | Owning policy elsewhere | Owning policy elsewhere |
| Schema-only, Product-only, Brand-only | Deny | Deny | Schema-only may use the separate existing builder | Owning policy elsewhere |
| Missing Central panel/page admission | Deny | Deny | Deny | Deny |

Edit Category uses the actual temporary `/admin/central/central-categories/{record}/edit`. No final CA-018 editor or CA-019 builder redesign is added. Mutations fail closed server-side even for tampered requests. Hard delete, duplicate, export-category, imports, bulk, direct Site mutation and inline translations/schema are absent.

## Read architecture, bounds and GET purity

CentralCategoryDetailController orchestrates the validated request, CategoryDetailReadModelQuery and authorized existing destination URLs. Typed CategoryDetailReadModelData, CategoryDetailSchemaSummary, CategoryDetailTranslationSummary, CategoryActivitySummary and CategoryActivityItem supply Blade. CategoryDetailTranslationQuery and CategoryActivityQuery separately own translation and audit rules. Blade performs no relationship lookups.

A minimal hierarchy snapshot, scalar counts, batch assignment/section/definition loading, active Locale and translation batches, limited Sites and limited audit reads keep SQL query count bounded. The regression compares **22 queries** for a minimal Category against **27 queries** with 31 Products, 25 Sections/assignments, 10 canonical Facets, multiple Locales, 11 selected Sites and 56 audit entries. The ceiling is 30, allowing conditional eager-load batches without per-record SQL growth. Site names are capped at eight, audit events at 12 and issue descriptions at five. Products and full audit histories are never loaded to count them.

HTTP tests compare all non-migration database tables before/after GET for populated and missing Locale contexts. GET does not change Category timestamps/status, translations, assignments, selections, projections, jobs or append-only audit. Copy and Locale changes are pure reads.

## Deterministic fixture

CategoryDetailFixture supplies `category-detail-v1` independently of the unchanged CA-016 browser fixture. The browser harness selects it only for CA-017 tests, then restores the default fixture.

Primary Gaming Monitors is Active under Electronics → Displays, schema Approved revision **6**, reviewed/approved revision 6 by Sam Schema. It has **2 Sections, 4 assignments, 2 required, 1 inactive/hidden canonical Facet, 2 configured comparison attributes (one hidden), 4 direct Products and 3 eligible selected Sites**. Five Site selections include Archived and deleted Sites excluded from three; Preview Store is disabled/hidden but included. Parent has three Products, child has two, excluded from primary totals. One global Definition is shared with the parent.

Five active Locales: en-US Approved, fr-FR HumanReviewed, es-ES MachineTranslated, ja-JP Outdated, de-DE absent. Summary is **3/5 covered (60%), 1 Missing, 1 Outdated**. German description explicitly uses English fallback. Real create/schema-review/schema-approval/activation actions supply historical events and attribution. A nullable-actor identity-update event represents historical unknown attribution through the existing recorder; no impossible deletion of audit-referenced users is forced. Dates are fixed in October 2026; Ada Catalog and Sam Schema are deterministic actors.

Browser SQLite IDs are primary **195003**, empty **195004**, archived **195005**. Other database-engine tests use the returned stable primary ID. Empty Draft has no assignments, usage, translations or history. Archived retains a Product and assignment and exposes Restore. No fabricated metric columns or legacy architecture are used.

## Empty, error and responsive states

Zero Sections/attributes/facets/comparison, Products or Sites remain truthful zeros with contextual copy. Missing description/translations, no active Locales, absent history/actor, Draft/Approved schema, Archived lifecycle and unavailable hierarchy are independently represented. Forbidden actors receive 403, missing IDs 404, invalid/stale Locale and lifecycle requests normal validation responses with no side effects.

At 1440×1000 the six cards use three columns with compact operational typography. At 1024×900 they use two columns; at 768×1024 and 390×844 they stack in one column. Header controls wrap, IDs/slugs can wrap, descriptions stay readable, Site names/statuses fit and activity retains actor/time context. Shared navigation drawer, focus indicators and confirmation dialogs remain keyboard usable. There is no horizontal page overflow or shrinking desktop table. Copy buttons have accessible names/live feedback; headings, Locale label and status text remain semantic.

## Visual evidence and review gate

The pinned Linux renderer captures populated Active at 1440×1000, 1024×900, 768×1024 and 390×844, plus scrolled mobile activity and Archived/empty desktop evidence. `tests/Visual/playwright/category-detail.visual.spec.mjs` saves PNGs, SHA-256 sidecars and provenance JSON under `storage/logs/visual-artifacts/ca-017-candidates`, attached to the normal visual diagnostics artifact.

These are **implementation candidates awaiting Product Owner review**, not approved baselines. The immutable registered prototype remains unchanged. No CA-017 approved implementation reference is claimed and no approved CA-016 baseline is changed. Screen-contract validation uses the existing pending-prototype-review rule; global visual policy is unchanged. Phase 19.5 remains short of COMPLETE until explicit visual acceptance and unchanged approved-capture promotion into the normal comparison system.
