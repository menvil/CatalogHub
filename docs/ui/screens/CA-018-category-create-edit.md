---
screen_id: CA-018
context: central-admin
purpose: Create a canonical Category or edit its source name, slug, parent and sibling order through the authoritative hierarchy actions.
roles: Central Admin and Catalog Editor with Category capability, Central mutation and active Central admission
route: /admin/central/categories/create
viewports: desktop=1440x1000;medium=1024x900;tablet=768x1024;mobile=390x844
fixture: category-create-edit-v1
regions: central-shell;breadcrumbs;header-actions;category-fields;slug-impact;display-order;category-summary;lifecycle-context;description-context;creation-guidance;form-actions
actions: save-category;cancel;back-to-category-detail;choose-parent;clear-parent;move-within-siblings;review-slug-impact;acknowledge-slug-impact;open-existing-schema;open-existing-translation;manage-lifecycle-in-detail
states: create;edit;archived;slug-impact;slug-impact-no-acknowledgement;validation-error;stale-revision;stale-identity;moved-category;forbidden;not-found
permissions: catalog.categories.manage;central.panel.access;central.page.access;central.mutation.execute for reading and submitting the form;catalog.schema.manage for schema destination;translations.manage for translation destination
responsive: Main form with a side context column on desktop; context below the form at 1024px and 768px; one column with sticky Save/Cancel at 390px; no page-level horizontal overflow.
out_of_scope: CA-019-through-CA-026;category-code;icon-or-media;color;canonical-description;publication-toggle;seo;url-template;fake-tabs;quality-score;import;duplicate;hard-delete;site-assignment;translation-mutation;attribute-configuration;redirect-engine;lifecycle-editing;shared-shell-redesign
reference_version: categories-schema-prototype-v1
visual_acceptance: pending-product-owner-review
---

# CA-018 — Category Create / Edit

Phase 19.6 replaces the temporary Filament Category Create/Edit pages with one canonical form. It changes no schema and adds no migration. Domain authority stays with the Phase 19.1 hierarchy actions.

| Route | Name | Purpose |
| --- | --- | --- |
| `GET /admin/central/categories/create` | `central.categories.create` | Empty Create form. |
| `POST /admin/central/categories` | `central.categories.store` | `CreateCentralCategoryAction`. |
| `GET /admin/central/categories/{category}/edit` | `central.categories.edit` | Edit form; optional `slug_preview` renders the routing impact. |
| `PATCH /admin/central/categories/{category}` | `central.categories.update` | `SaveCentralCategoryAction`. |
| `GET /admin/central/categories/{category}/slug-impact` | `central.categories.slug-impact` | Read-only impact fragment for live preview. |

`{category}` binds the numeric ID only (`whereNumber`), so `create` can never be read as an ID. List (CA-016) and Detail (CA-017) routes are unchanged. Successful Create and Edit redirect to the CA-017 Detail. Cancel on Create returns to CA-016, and Cancel or Back on Edit returns to CA-017.

## Retired temporary routes

`/admin/central/central-categories/create` and `/admin/central/central-categories/{record}/edit` remain registered only as redirect pages. They have no form, record state or Livewire mutation method; they authorize the same capability and redirect to the canonical routes. `CentralCategoryResource::getUrl('create'|'edit')` generates the canonical URLs directly, so legacy callers such as the Sync Log resource cannot loop. The resource form schema and `SaveLegacyCentralCategoryAction` are removed. `/central-categories/{record}/schema` (the Schema Builder) is unchanged. The single Categories navigation entry is unchanged.

## Source prototype and adaptation

Immutable source: `pictures/1. Central Admin/1.4. Categories : Schema/CA-018 — Category Create:Edit.png`, **1448×1086**, SHA-256 `91008de57b8a2f0e8a59761d907606caa68aaa12ca7c942e16bb487e466268dd`, version `categories-schema-prototype-v1`. The [visual gap audit](../categories-schema-visual-gap-audit.md#ca-018--category-create--edit-196) governs each element.

| Prototype element | Implemented meaning |
| --- | --- |
| Parent Category | Searchable canonical parent; empty means Root. Options are labelled by full path; identical paths get `· #ID`. Self and descendants are excluded on Edit. Archived Categories stay eligible, as in the domain rules. |
| Name | **Category Name**, the source/reference name (max 255). It is never a translation. |
| Slug | Required canonical URL key (max 255, unique). On Create it is suggested from the name client-side until edited. On Edit the saved slug is shown and never regenerated. |
| Status dropdown | Omitted. Read-only Category and Schema badges plus "Active is canonical Category availability. Site selection and publication are managed separately." Lifecycle commands stay on CA-016/CA-017. |
| Display Order | Real position among siblings. Edit saves it through the complete-set reorder; Create appends. |
| Description editor | Omitted. "Category descriptions are localized and managed separately." links to the existing exact-Locale translation editor when authorized. |
| Icon, Color, Category Code, tabs | Omitted; no field replaces them. |
| Category Summary | Persisted-Category context on Edit only (see below). Create shows creation guidance and no fabricated ID, timestamps or counts. |

## Form fields and tokens

Create submits `name`, `slug`, `parent_id` and `scope_revision`, the destination parent's hierarchy scope revision as rendered. A small script updates the token when the parent changes, using the revision map that is read with the page.

Edit submits the following:

- **Identity and placement:** `name`, `slug`, `parent_id`, and `display_position` (target index among the original parent's siblings).
- **Expected-state tokens read at load:**
  - `expected_name`, `expected_slug`, `expected_parent_id`;
  - `current_scope_revision` (original parent scope);
  - `destination_scope_revision` (selected parent scope);
  - `sibling_ids[]` (the sibling order shown).
- **Slug impact:** `slug_impact_fingerprint` and `slug_impact_acknowledged` when the slug changes.

Tokens are expected state, not permissions; every one is revalidated under locks. After a rejected submit, every value, including null parents and tokens, comes from that submission, so a stale form is never silently re-based on fresh state. Ineligible submitted parent IDs are never shown as selected.

`status`, `schema_status`, `position` and `schema_revision` are `prohibited`. Arrays in scalar fields, malformed tokens and out-of-range positions are rejected by the FormRequests.

## Create behavior

`CreateCentralCategoryAction` keeps all Phase 19.1 guarantees:

- authorization;
- the root tree lock first;
- the expected destination revision;
- parent existence and ancestry;
- contiguous siblings and append at the end;
- Draft Category and Draft schema;
- a transactional `catalog.category.created` audit.

The controller never persists the model. Success redirects to CA-017 with "Category #ID created as Draft." The new row appears on CA-016. A stale revision (for example a concurrent sibling create) rejects with "The sibling scope changed. Reload before saving."

## Edit behavior and composite transaction

`SaveCentralCategoryAction` runs one `DB::transaction` (three attempts) composed of existing actions in the established lock order:

```text
validate intent (pure)         parent change + order change together → reject before any write
1. structural (optional)       Reparent  OR  complete-set Reorder   (root tree lock first)
2. identity                    UpdateCentralCategoryAction with expected name/slug/parent (Category row lock)
3. slug impact (slug changed)  recompute the impact and compare the reviewed fingerprint
any failure                    rolls back positions, scope revisions, identity and every audit row
```

- **Name only:** one `catalog.category.updated` event. Parent, order, lifecycle, schema revision/approval, Products, Site selections, translations and projections are unchanged.
- **Slug only:** the ID is stable and nothing is duplicated. No redirect, publication or Site change.
- **Parent only:** `ReparentCentralCategoryAction` checks the original-parent snapshot and both scope revisions. It compacts the old siblings and appends to the new parent. Descendants stay attached, only the two affected scope revisions increment, and `catalog.category.reparented` is recorded.
- **Order only:** the submitted intent becomes the complete sibling order for `ReorderCentralCategoriesAction`, which verifies the exact set and the expected revision and records `catalog.category.reordered`. A no-op reorder changes nothing.
- **Parent and order in one save:** rejected. Moving appends to the new parent, and the order can be adjusted in a later save. This avoids reusing a revision consumed by the reparent.
- **Unchanged save:** a full no-op. There is no audit, revision churn or timestamp change, and the user sees "No changes to save."

Lost-update protection was added to `UpdateCentralCategoryAction` as an optional `CentralCategoryExpectedState`, compared under its existing row lock. A changed name or slug rejects with `category`, and a moved Category rejects with `parent_id`. No version column was added. The action now fills only `name`/`slug`; previously a `null` `parent_id` passed the `prohibited` rule and could have moved a Category.

## Slug routing-impact preview

The [ADR-0003](../../architecture/adr/0003-categories-schema-ownership.md) F01 requirement is implemented as a read-only consequence assessment by `CategorySlugImpactQuery`.

Facts:

- **Canonical slug is not served directly.** Public Category routes (`/{locale}/categories/{slug}`) resolve persisted `SiteCategoryProjection` slugs, never `central_categories.slug`.
- **No automatic refresh.** A canonical change reaches a Site only when its owning sync (`cataloghub:sync-category` / `cataloghub:sync-site`) rebuilds the projection. Nothing marks projections stale on a slug edit.
- **After a rebuild:** the previous path stops resolving, with no redirect.

Preview contents:

- current slug, proposed slug and the unchanged Category ID;
- Sites selecting the Category (the CA-016/017 `Site::selectingCategory` definition, at most eight names);
- the counts of persisted Category projections, of those using the current slug, and of those served now;
- up to twelve Site/Locale routes. Each shows its current path, whether it is served now (with the reason when not), and the path after the next Site sync.
  - **Path after sync:** computed by `CategoryProjectionBuilder::effectiveSlug`, the same code the projection build uses. Site or market `slug`/`local_slug` overrides therefore show "unchanged".
  - **Absolute URLs:** come from `LocalizedUrlResolver` only when the route is served now and `sites.domain` is one of the Site's active Primary/Alias hosts. Otherwise only the path is shown.

Explanations state that the Category ID stays stable and that saving changes no live route by itself. They say no HTTP redirect is created, that Product and neighbouring Category links refresh only on their own rebuilds, and that overrides, Site selection and publication follow their own workflows.

Safety:

- **Read-only.** GET Edit with `slug_preview` and the fragment endpoint write nothing.
- **Fingerprint.** A SHA-256 over every reviewed fact: selections, all persisted Category projections, active slug overrides and the involved Site states.
- **Review required.** A slug change without a reviewed fingerprint is rejected and redirected to Edit with `slug_preview`, so the impact also renders without JavaScript.
- **Staleness check.** Save recomputes the impact inside the transaction after the identity update. A different fingerprint is rejected and rolls everything back.
- **Acknowledgement.** Required only when the impact is meaningful (any selecting Site or persisted projection). An unchanged slug needs no preview or confirmation.

No redirect table, redirect engine, URL template or stale-marking side effect is introduced.

## Context summary (Edit)

| Fact | Source (same as CA-016/017) |
| --- | --- |
| Category ID, Created, Last updated | Persisted Category columns. |
| Category / Schema | Separate lifecycle badges, read-only. |
| With Schema | At least one Category attribute assignment; presence, not approval. |
| Sections | Category-local AttributeSection rows. |
| Direct Products | `central_products.central_category_id` rows across lifecycles. |
| Selected by Sites | `Site::selectingCategory`: non-deleted administrable Sites with any SiteCategory row. The CA-017 query now uses the same scope. |
| Parent | Ancestry from one minimal tree snapshot; hierarchy unavailable when a cycle or missing parent is detected. |

`CategoryFormReadModelQuery` uses one tree snapshot and reads scope revisions without creating rows (missing means 0). It never normalizes positions. Legacy non-contiguous sibling positions are reported, and only an explicit complete-set reorder repairs them. Query count is independent of tree size (seven queries for Edit).

## Validation and errors

Field errors appear beside Name, Slug, Parent and Display Order. Expected-state and hierarchy errors appear in one alert with a "Discard them and reload the current Category" link:

- `hierarchy_revision`;
- `category`;
- moved-parent `parent_id`;
- prohibited fields;
- malformed tokens.

Slug-impact errors appear in the impact region. Every rejected request preserves persisted state, hierarchy, dependent Product/Site/translation facts and audit. Safe user input is preserved. There is no canonical slug format rule beyond required/string/255/unique, and none is added, so existing slugs stay valid.

## Authorization

The routes sit inside `can:catalog.categories.manage` and `can:central.mutation.execute`; actions authorize again through `CategoryAccess`.

| Actor | Create/Edit read and submit |
| --- | --- |
| Active Central Admin / Catalog Editor with Category capability and Central mutation | Allow |
| Category reader without Central mutation | 403 (CA-016/017 remain readable) |
| Guest | Redirect to login |
| Disabled, Site-only, Moderator, Translator, Schema-only, Product-only, Brand-only | 403 |
| Missing Central panel or page admission | 403 |

`catalog.schema.manage` and `translations.manage` grant no canonical identity edits. Forged POST/PATCH requests fail closed.

## Concurrency, audit and GET purity

Coordinated two-process tests run on a SQLite shared file, PostgreSQL and MariaDB:

- two creates under one parent from the same form state;
- two display-order saves in one scope;
- an identity edit from an old form after a concurrent reparent;
- two concurrent identity edits;
- opposite composite reparents.

In every case the second writer rejects on its stale expected state. Sequential tests cover a Category moved after load, a sibling inserted after load, duplicate slugs, and audit failure during a combined save (everything rolls back).

Only the existing `catalog.category.created`, `.updated`, `.reparented` and `.reordered` events are recorded, with their existing allowlists. No form-open, preview, no-op or failed-save event exists. CA-017 Recent Activity shows the genuine events.

Full database snapshots prove GET Create, Edit, Edit with `slug_preview` and the impact fragment write nothing: no scopes, positions, timestamps, translations, audit, projections, jobs or selections.

## Fixture `category-create-edit-v1`

`Tests\Support\CategoryCreateEditFixture` creates a deterministic hierarchy with stable IDs:

```text
Electronics 196001 → Laptops 196005, Displays 196006, Phones 196007 (Draft)
  Displays → Televisions 196008, Monitors 196009 (primary), Projectors 196010 (Draft)
    Monitors → Gaming Monitors 196011 (Draft; forbidden as Monitors' parent)
Home & Garden 196002 (alternative parent) → Garden Tools 196012
Legacy Peripherals 196003 (Archived; retains a Product and a Site selection)
Smart Home 196004 (Draft; no dependent data)
```

- **Real history.** Actions give the Electronics, Displays, Home & Garden and root scopes real revisions. Monitors has an Approved schema (one Section, two assignments), three direct Products and en-US/de-DE translations.
- **Sites and projections.** Monitors is selected by Display World (Active, `display-world.test`, en-US/de-DE) and Preview Outlet (Draft). Display World's Category projections are built by the real `SiteSyncService`. Its de-DE projection keeps a real `bildschirme` Site override.
- **Browser harness.** The fixture is selected with `CATALOGHUB_BROWSER_FIXTURE=category-create-edit-v1`. It first repairs the legacy non-contiguous root order of the harness's factory-made fixtures through the explicit complete-set reorder, then appends its roots. Dates are fixed in October 2026; the actor is Ada Catalog.

## Responsive behavior

- **1440×1000:** the form card holds Parent, Name/Slug side by side, the slug impact and Display Order; the side column holds Summary, Lifecycle and Localized description.
- **1024×900:** a narrower side column.
- **768×1024:** the context cards move below the form in two columns.
- **390×844:** one column, readable inputs and full-width header actions. Save/Cancel stay reachable in a sticky footer, and validation and impact text wrap without clipping.

The searchable parent selector and sibling select keep viewport-aware listboxes. The shared navigation drawer is unchanged.

## Visual evidence and review gate

`tests/Visual/playwright/category-create-edit.visual.spec.mjs` captures implementation candidates with the pinned Linux renderer into `storage/logs/visual-artifacts/ca-018-candidates`:

- Create at 1440×1000 and 390×844;
- Edit at all four viewports;
- the parent selector, slug impact, validation error, archived context, stale-revision error and scrolled mobile actions.

Each PNG has a SHA-256 sidecar and JSON provenance stating `pending-product-owner-review`.

These are **implementation candidates awaiting Product Owner review**, not approved baselines. No CA-018 baseline or manifest reference is added, and the visual gates and thresholds are unchanged. Phase 19.6 is not COMPLETE until explicit visual acceptance and unchanged promotion through the normal review process.

## Out of scope

CA-019 onward is not implemented:

- the Schema Builder redesign, schema or translation editing, and the CA-026 Translation Workspace;
- bulk creation, import, duplicate and hard delete;
- Site assignment and publication;
- redirects;
- Category code, media, color and SEO.
