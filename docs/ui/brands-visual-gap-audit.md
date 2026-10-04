# Brands visual gap audit — CA-011…CA-015

Final section audit: 2026-10-04. Baseline: `develop` at `be2817ca` after merged Phase 18.5 / PR #610. Original prototype audit: 2026-09-02 at `8e51ab9` after Phase 17. Prototype reference version: `brand-prototype-v1`.

This audit compares the original Brand prototypes with the current desktop implementation and its responsive mobile composition. The PNGs in `pictures/1. Central Admin/1.3. Brands/` are the design source; `tests/Visual/baselines/` are regression evidence only. Rows use **A** for implementation from an approved domain source, **B** for mapping prototype language to existing semantics, **C** for intentional future-domain gaps, and **D** for pure composition/visual debt. A row may carry a compound classification when one prototype region contains separable concerns with different outcomes. Phases 18.1–18.5 closed all eligible A/B/D work. Phase 18.6 accepts CA-011…CA-015 as one finished section; C rows identify explicit future exclusions, and controls that conflict with approved ownership are rejected rather than left as open gaps. See [final section acceptance](brands-section-acceptance.md).

| Screen | Final acceptance |
|---|---|
| CA-011 — Brands List | Final converged / accepted |
| CA-012 — Brand Overview | Final converged / accepted |
| CA-013 — Brand Create/Edit | Final converged / accepted |
| CA-014 — Brand Media | Final converged / accepted |
| CA-015 — Brand Translations | Final converged / accepted |

## Prototype references

| Screen | Original file | SHA-256 | Native / intended viewport |
|---|---|---|---|
| CA-011 | `CA-011 — Brands List.png` | `7750e8cb470c3ee1dd58c3cf95c30c9d6636ad4e05ef76aa4050b72bedf6354b` | 1448×1086 |
| CA-012 | `CA-012 — Brand Detail.png` | `b1e3e71386e41d79e9f5fed1904ec9ba77a8cf291a1c04a5b01a3fd3bdf6f4de` | 1448×1086 |
| CA-013 | `CA-013 — Brand Create:Edit.png` | `ed3c038a940ae44f10a95ec49ae58a3be3b9c2ce65b0522abe4c26f351dd68af` | 1448×1086 |
| CA-014 | `CA-014 — Brand Media : Logo.png` | `79973a4c00b177e49d3f5401e5e4fab122a2e4c0d803953b72998584f42dc1e1` | 1448×1086 |
| CA-015 | `CA-015 — Brand Translations.png` | `cf129080993a2aa03dd4dfbfa5cf824ef6da5c68d23be52b7275063ab05ce66b` | 1448×1086 |

The five files match the filenames and dimensions recorded by the original screen registry. No alternative approved artifact was found in the repository. Only these five formerly local files are versioned by Phase 17.

## CA-011 — Brands List

Phase 18.1 converges CA-011 into a dense operating dashboard while retaining approved domain boundaries. Desktop now follows the prototype's header → metrics → filters → operational table → pagination hierarchy; mobile uses stacked operational rows without page-level overflow.

| Prototype region | Final desktop / mobile equivalent | Domain source | Decision | Phase | Notes |
|---|---|---|---|---|---|
| Total, Active and review summary cards | Five real KPI cards, responsive 5/3/2 grid | `CentralBrand`, lifecycle counts, derived Brand Quality | Implemented (A) | Phase 18.1 | Active/Logo/Missing/Needs attention percentages use current total; no fake trends. |
| `Needs Review` | Dedicated Quality column with score and state | `CentralBrandQualityEvaluator` | Adapted (B) | Phase 18.1 | Label is `Needs attention`; lifecycle and translation remain separate. |
| Logo-led Brand rows | Larger wordmark-safe canonical logo/fallback, name, slug and optional Parent Company | exact Shared Media `brand_logo`; Organization ownership | Implemented (A) | Phase 18.1 | Ready needs no technical label; missing uses a fallback and unavailable gets a compact warning. |
| Product and category context | Grouped Product count and distinct Category coverage columns | non-archived Products and direct Categories | Implemented (A) | Phase 18.1 | No counters or manual Brand categories are stored. |
| Translation health | Active-Locale percentage, progress and Missing/Outdated reason | `BrandTranslation.status` + active Locales | Implemented (A) | Phase 18.1 | Complete statuses are MachineTranslated/HumanReviewed/Approved; absent/Missing and Outdated are distinct incomplete reasons. |
| Lifecycle filter/status | Dense Draft/Active/Archived badge and filter | `CentralBrandStatus` | Converged (D) | Phase 18.1 | No review/publication values added. |
| Rich search/filter/action composition | Name/slug/company search; consistent Country, coverage, translation and quality filters; rightmost in-grid global Clear; overflow actions | approved read model and routes | Converged (D) | Phase 18.1 | Explicit responsive control grids prevent intermediate-width overflow; query state persists through sort/page/per-page and history. Footer selects and row actions use viewport-aware placement. |
| Media/site coverage and Published/Synced columns | No numeric Media or technical Logo Health column; Sites/publication remain absent | exact logo contract; future SiteBrand/projection | Adapted/deferred (B/C) | Phase 18.1 / Deferred | Canonical logo state is integrated into identity; unsupported concepts are not synthesized. |

## CA-012 — Brand Detail

CA-012 is final-converged and accepted. Its current overview uses the Phase 18.2 consolidated composition; the former Phase 16/17 card gaps are closed.

| Prototype region | Final desktop / mobile equivalent | Domain source | Decision | Notes |
|---|---|---|---|---|
| Logo/name/slug/status identity | Dominant identity/contact surface, contained logo, lifecycle and Edit | Canonical Brand, exact Shared Media logo | Implemented / converged (A/D) | Mobile stacks the same identity and actions. |
| Parent Company and profile facts | Read-only Organization owner and compact Country/founded/contact/color grid | Canonical profile + ownership | Implemented (A) | All scalar/owner writes remain CA-013. |
| Needs Review / completeness | Authoritative Quality and repair destinations | Quality evaluator and query | Mapped (B) | Complete / Needs attention are derived, never lifecycle. |
| Translation coverage | One active-locale summary in Brand summary | BrandTranslation + active Locales | Implemented (A) | No stored counters or locale N+1. |
| Products / Categories / Tags | One usage summary and separate derived Category/editorial Tag chips | Current Products, Category coverage, Catalog Tags | Implemented / converged (A/D) | No duplicate Product portfolio or editable Brand Categories. |
| Source information | Safe external identities with configured ImportSource namespaces | Entity-level provenance | Mapped (B) | No field provenance or exposed source configuration. |
| Metadata and lifecycle | Compact record facts; explicit legal header actions | Brand ID/timestamps, existing Actions | Implemented / mapped (A/B) | No generic status edit or quality recalculation. |
| Published/Synced/Sites/Versions | Omitted | Future Site projection/version domains | Intentionally deferred (C) | Not a canonical lifecycle or an accepted tab. |
| Hero / dark / light / richer media | Omitted | Unapproved media roles | Intentionally deferred (C) | Only global primary brand_logo is accepted. |
| Recent Products / price / rating | Current usage totals only | No approved Brand-filtered Product destination or metric | Mapped / intentionally deferred (B/C) | No duplicate list, invented price or Brand rating. |
| Field source/confidence/history | Entity-level external identities only | No field-level provenance contract | Intentionally deferred (C) | No inferred field ownership or confidence. |

## CA-013 — Brand Create / Edit

Phase 18.3 converges the approved canonical form and Phase 16 Organization ownership into one dense Brand information editor. The prototype remains a density/hierarchy reference, not a source of deprecated Brand domains.

| Prototype region | Current desktop / mobile equivalent | Domain source | Decision | Phase | Notes |
|---|---|---|---|---|---|
| Compact canonical identity/profile sections | One Brand information card with four divided subsections and 3→2→1 responsive field grids | Canonical Brand input | Implemented (A) | Phase 18.3 | Header actions and compact rail keep most canonical data in the first desktop viewport. |
| Parent Company selector | Embedded Company & origin row with Assign/Change plus contextual Create/Clear | `CentralBrandOwnership → Organization` | Implemented (A) | Phase 18.3 | Visual integration only; one owner and separate authoritative mutations remain exact. |
| Website/support/contact/color | Compact internal subsections without redundant helper copy | Canonical Brand fields | Implemented (A) | Phase 18.3 | No persistence or validation changes. |
| Tags in prototype form | Managed only from CA-012 Classification | Existing editorial Tags | Mapped (B) | Phase 18.3 | Intentionally not duplicated in CA-013. |
| Publish/save controls | Create/Save in page header; lifecycle remains CA-012 | Existing save plus CA-012 lifecycle | Implemented (A) | Phase 18.3 | No sticky footer, Save Draft, preview, or publication shortcut. |
| Description / SEO and Site visibility | Localized copy on CA-015; Site controls omitted | BrandTranslation; future Site projection | Mapped / intentionally deferred (B/C) | Phase 18.5 / Deferred | No localized canonical columns. |
| Manual category assignment | No editor by design | Category coverage is derived from Products | Rejected as speculative | Closed | Prototype control conflicts with approved derived ownership. |
| Arbitrary external identifier fields | Entity-level external identities on CA-012 | `CentralBrandExternalIdentity` + `ImportSource` | Mapped (B); free-form fields rejected | Closed | Provenance stays outside canonical profile input. |

## CA-014 — Brand Media / Logo

Phase 18.4 converges the honest single-role implementation into a focused Brand logo manager. The prototype remains a hierarchy and polish reference rather than authority for its multi-role DAM domain.

| Prototype region | Current desktop / mobile equivalent | Domain source | Decision | Phase | Notes |
|---|---|---|---|---|---|
| Primary logo preview and controls | Contained preview, real state badges, compact action rail, on-demand replacement dialog, quiet confirmed assignment removal | Shared Media Core assignment and variants | Converged (A/D) | Phase 18.4 | Choosing a file does not mutate the assignment until explicit submission. |
| Missing/unavailable media health | Polished no-logo, Processing, Failed and Unavailable states with existing recovery actions | Media assignment usability + derived Quality | Implemented (A) | Phase 18.4 | Missing variants never override a usable normalized master. |
| Responsive asset metadata/actions | Compact details/variants rail; single-column before 1280px; explicit tablet/mobile coverage | Existing Media asset/variant read model | Converged (D) | Phase 18.4 | Long values wrap and controls remain usable at 390px. |
| Wordmark, symbol, dark/light, hero and OG slots | No equivalent | Unsupported Brand media roles | Intentionally deferred (C) | Deferred | Do not render placeholders that imply role support. |
| Localized/site media | No equivalent | Future localized/site media | Intentionally deferred (C) | Deferred | Remains outside global canonical media. |
| Generic library/DAM browser | On-demand bounded 24-card Shared Media selector | Existing compatible-asset selector; generic DAM redesign | Adapted / C | Phase 18.4 / Deferred | Four columns at wide desktop, server search/pagination, and no generic asset management. |

## CA-015 — Brand Translations

Phase 18.5 converges CA-015 to the original prototype's translation workspace using existing localized data and workflow authority.

| Prototype region | Final equivalent | Domain source | Result | Notes |
|---|---|---|---|---|
| Source → Target | Explicit direction, two labeled language selectors | Active Locales + existing BrandTranslation | Converged | Shareable reference choice; independent of canonical source hash. |
| Dense locale navigation | Compact Source and Target menus with language/code/status | Active Locales + row enum states | Adapted | Twenty languages do not expand the panel; selecting the opposite language swaps direction. No locale management. |
| Side-by-side fields | Field / Source / Target rows for six approved fields | BrandTranslation | Converged | Tablet pairs within field; mobile source then target. |
| Source copy | Field copy and Copy all, overwrite confirmation | Existing reference values | Converged | Client-side only, explicit Save. |
| Workflow overview | Compact status/row/current context and secondary actions | Existing Phase 15 actions and hashes | Adapted | No field-level completeness engine. |
| Translation activity | Bounded real audit feed | Existing translation audit | Converged | No fake events or history subsystem. |
| Donut / field counts | Omitted | No authoritative per-field completeness | Rejected as speculative | No 78% or invented Complete/In Progress counts. |
| Localized elements | Omitted | No hero/footer/support fields | Rejected as speculative | Only the approved six-field contract. |
| Add Language / AI / publication | Omitted | Separate or deferred domains | Intentional divergence | No machine provider, Site or Published/Synced semantics. |

## Phase 17 CA-012 convergence decision

Phase 17 implements only CA-012 A/B/D rows. It introduces no migration or persistence field. The result uses an identity-first 8/4 desktop grid, a compact canonical profile, a prominent read-only Parent Company, a derived Brand health/translation summary, concise portfolio/classification/source regions, secondary record metadata and existing lifecycle controls. At 390px the same information is reordered into readable stacked surfaces with internally bounded content and wrapping opaque values.

Intentional divergences are architectural: lifecycle remains Draft/Active/Archived; Quality remains Complete/Needs attention; ownership remains a single Organization relation; translations remain `BrandTranslation`; media remains the one global primary `brand_logo`; Published/Synced/Sites, additional media roles and field provenance remain future concerns.

## Phase 18.1 CA-011 convergence decision

CA-011 A/B/D work is closed. The final screen uses the original prototype—not the former regression baseline—as its hierarchy and density target. The reviewed result has five database-derived KPIs, a six-control operational filter grid with its conditional global Clear action in the rightmost available grid cell, larger linked logo-led identity rows, grouped Product and Category context, explainable active-Locale translation coverage, a separate authoritative Quality column, viewport-safe overflow actions, and bounded pagination. The table header uses the compact spacing and muted surface of the shared UI data-table example. The `1440x1000`, `1024x900`, `768x1024`, and `390x844` references were reviewed against the original and pre-polish Phase 18.1 result before their `brands-list-v3` baselines were approved.

Intentional differences remain explicit: Sites requires a future Site Brand projection; `Needs Review` is derived `Needs attention`; Language/Market is active-Locale Translation; numeric Media is omitted because only canonical logo identity has an approved contract; checkboxes wait for an approved bulk workflow; monthly trends wait for historical analytics; and the global shell remains outside screen ownership. The stable Imports destination is Product-oriented, so no misleading Brand import action is shown.

## Phase 18.2 CA-012 convergence decision

CA-012 A/B/D work is closed. The final screen maps the prototype into a strong name/slug/lifecycle/Quality header with confirmed lifecycle actions, Overview/Media/Translations navigation, a dense three-column Identity and contact overview, provenance-backed External identities and actionable Issues. One Brand summary consolidates Product/Category usage, authoritative Quality, Translation coverage and Record metadata; redundant Product Portfolio, Brand Health, Recent Products, Lifecycle and Record cards are removed. Classification is integrated below the identity fields and exclusively owns Category and Tag chips; one full-width section rule replaces the short Contact email field rule. Wide desktop keeps independent main/operations stacks without shared-row gaps, while Issues is the sole operational-rail card.

The `brand-detail-v9` Samsung acceptance fixture persists its logo, ownership, five Tags, two External identities, eight realistically named Products across five derived Categories and four mixed-status translations. Its intentionally absent support/contact pair and outdated German translation produce two real authoritative issues and 80% Quality; archived Sony remains the separate 100% Complete state. The responsive composition uses the main/operations dashboard at 1440/1280, a stable two-column internal overview at 1024/768, and the explicit mobile priority Identity with integrated Summary/Classification → Issues → External identities at 390. Published/Synced, Publication Status, Sites/Site Coverage, Versions, hero/banner media, a Recent Products overview list, rating/price/SEO metrics, source-feed internals and a category breadcrumb remain intentional architectural divergences.

## Phase 18.4 CA-014 convergence decision

CA-014 A/B/D work is closed and final-converged. Before convergence, a tall preview, fragmented controls, a full-width empty Variants card, and prominent red removal control made the page read as a technical file warehouse. The final `brand-media-v7` composition uses an approximately 65/35 wide-desktop workspace: the Primary logo card and full-height Asset details table share aligned edges. Inside Primary logo, the contained preview and compact action rail keep replacement controls subordinate; Generated variants remains compact inside the same card. `Remove logo from brand` is a quiet confirmed link below that row. Empty, Processing, Failed, and Unavailable remain explicit delivery states.

The Shared Media selector is an on-demand dialog rather than a permanent page section. The default workspace does not query the library; opening the picker loads stable 24-item server pagination with existing filename/checksum/numeric-ID search, eager variant loading, safe URL resolution, server-side compatibility checks, a visible first-page `Current`/`aria-current` state, and no-op prevention. Wide desktop shows four cards per row; narrower layouts step down without overflow. Upload remains the existing secure Shared Media ingest behind an explicit replacement dialog and submit action; assignment changes only after success, while failed replacement preserves the prior logo and old assets survive replacement/removal.

The reviewed 1440×1000 and 1280×900 frames show the aligned Primary logo/Asset details workspace and compact Generated variants. At 1024×900 the rails stack before metadata gets narrow; 768×1024 and 390×844 preserve the same priority order with no horizontal overflow. The 1440×1000 picker frame verifies the dedicated dialog and exactly 24 compact cards in a four-column grid. Dark/light logos, wordmarks as a separate role, favicon, hero, OG, localized or Site media, Media Completeness, alt-text completeness, Edit Image, and Delete Asset remain intentional architectural divergences. CA-014 manages one exact global primary `brand_logo` assignment and never deletes the Shared Media asset.

## Closed section and future exclusions

Brands CA-011…CA-015 — COMPLETE. No open implementation or visual convergence gaps remain after Phase 18.6 acceptance. Explicit future C capabilities remain deferred; conflicting prototype controls are rejected. The next section is Categories / Schema, outside this closure MR.

## Phase 18.5 CA-015 convergence decision

The separate Source Context sidebar is replaced by actual localized reference text beside every editable field. Existing canonical hash/outdated authority remains intact. English Approved → German Outdated, English → French Missing, outdated reference, tablet/mobile and independent English LTR → Arabic RTL references demonstrate the final workspace. Screenshots use persisted Zotac fixture records, not hardcoded view values. Source selection and copy introduce no write on GET or before Save and no additional locale-by-locale queries. CA-015 retains the original shell system typography; its eight references are captured and compared with the pinned Linux renderer, not reconciled by a product font override.

## Phase 18.6 section acceptance

Cross-screen acceptance uses the same persisted Samsung Brand for list, Overview, Edit, Media and Translations. A focused product fix delays the CA-011 one-row filter layout until 1440px, retaining the compact three-column layout at 1280px so an active Clear action stays inside the workspace. Long CA-015 slug metadata is bounded/wrapped, and modal initialization preserves native control tab order so Shift+Tab stays inside Brand dialogs. The existing journey is extended through Media and Translations and back to Brands; section-level browser coverage checks all five widths. All approved PNG references, prototype hashes, typography and pinned visual infrastructure remain unchanged.
