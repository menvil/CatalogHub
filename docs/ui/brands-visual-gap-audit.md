# Brands visual gap audit — CA-011…CA-015

Audit date: 2026-09-02. Baseline: `develop` at `8e51ab9` after Brands Phase 17. Prototype reference version: `brand-prototype-v1`.

This audit compares the original Brand prototypes with the current desktop implementation and its responsive mobile composition. The PNGs in `pictures/1. Central Admin/1.3. Brands/` are the design source; `tests/Visual/baselines/` are regression evidence only. Every row is classified once: **A** implement from an approved domain source, **B** map prototype language to existing semantics, **C** intentional future-domain gap, or **D** pure composition/visual debt. Phases 18.1–18.4 close the eligible CA-011 through CA-014 A/B/D work.

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

Before Phase 17, desktop rendered all approved data but as a long succession of generic cards; identity, quality and canonical facts competed at equal weight. Mobile stacked safely but inherited that fragmentation. This is the only screen converged in Phase 17.

| Prototype region | Phase 16 desktop / mobile equivalent | Domain source | Gap | Phase | Notes |
|---|---|---|---|---|---|
| Logo/name/slug/status identity header | Small logo context and separate generic profile cards; mobile fragments identity | Canonical Brand, `brand_logo`, lifecycle | D | Phase 17 | Recompose as one dominant identity/profile surface with clear primary action. |
| Parent Company | Absent from CA-012 after Phase 16 introduced ownership | `CentralBrandOwnership.organization` | A | Phase 17 | Prominent read-only identity metadata; mutation remains CA-013. |
| Official profile facts | Split across General Information, Online Presence and Brand Identity cards | Canonical profile fields | D | Phase 17 | One compact definition grid for Country, Founded, URLs, email and color. |
| `Needs Review` / completeness | Derived Quality card exists but composition is detached | `CentralBrandQualitySummary` | B | Phase 17 | Keep `Complete / Needs attention`; present score, progress and repair destinations. |
| Translation coverage | Only individual translation quality issues | Active Locales + existing Brand translations | A | Phase 17 | Add a read-only grouped status summary with no stored counter and no N+1. |
| Product portfolio and category coverage | Separate usage/category blocks lower in a long page | Product count and grouped derived coverage | D | Phase 17 | Compact portfolio surface; categories remain derived. |
| Categories and Tags | Correct semantics, visually scattered | Derived Category coverage; editorial Tags | D | Phase 17 | Keep concepts distinct while aligning their visual weight. |
| Source Information | External identities card exists with generic management layout | `CentralBrandExternalIdentity` + `ImportSource` | B | Phase 17 | Treat as source context; retain existing scoped mutation and safe links. |
| Record metadata | Large secondary card competes with primary information | Brand ID and timestamps | D | Phase 17 | Keep secondary and compact. |
| Lifecycle actions | Correct actions in a separate card | Draft / Active / Archived actions | B | Phase 17 | Preserve approved transitions; do not copy publication controls. |
| Published, Synced, publication status and Sites tab | No equivalent | Future SiteBrand/projection | C | Deferred | Intentionally absent; these are not lifecycle. |
| Hero, dark/light logos and media completeness | Only exact global primary `brand_logo` | Future/unsupported media roles | C | Deferred | No fake slots or speculative Shared Media roles. |
| Recent Products, price/rating snapshots | Product and Category totals remain in Brand summary; no separate recent list or decorative metrics | Current Product usage/category reads; no approved Brand-filtered Product destination or rating/price metrics | Adapted / C | Phase 18.2 / Deferred | Overview avoids duplicate Product context; rating/price remain intentionally absent. |
| Field source/confidence/history | External identity links only | Future field-level provenance | C | Deferred | Do not infer canonical auto-update or matching confidence. |

## CA-013 — Brand Create / Edit

Phase 18.3 converges the approved canonical form and Phase 16 Organization ownership into one dense Brand information editor. The prototype remains a density/hierarchy reference, not a source of deprecated Brand domains.

| Prototype region | Current desktop / mobile equivalent | Domain source | Gap | Phase | Notes |
|---|---|---|---|---|---|
| Compact canonical identity/profile sections | One Brand information card with four divided subsections and 3→2→1 responsive field grids | Canonical Brand input | A | Phase 18.3 | Header actions and compact rail keep most canonical data in the first desktop viewport. |
| Parent Company selector | Embedded Company & origin row with Assign/Change plus contextual Create/Clear | `CentralBrandOwnership → Organization` | A | Phase 18.3 | Visual integration only; one owner and separate authoritative mutations remain exact. |
| Website/support/contact/color | Compact internal subsections without redundant helper copy | Canonical Brand fields | A | Phase 18.3 | No persistence or validation changes. |
| Tags in prototype form | Managed only from CA-012 Classification | Existing editorial Tags | B | Phase 18.3 | Intentionally not duplicated in CA-013. |
| Publish/save controls | Create/Save in page header; lifecycle remains CA-012 | Existing save plus CA-012 lifecycle | A | Phase 18.3 | No sticky footer, Save Draft, preview, or publication shortcut. |
| Description, SEO, visibility, site assignment | No canonical equivalent in Brand profile | Translation or future Site projection domains | C | Deferred | Must not become new Brand columns. |
| Manual category assignment | No editor by design | Category coverage is derived from Products | C | Deferred | Prototype control conflicts with approved semantics. |
| Arbitrary external identifier fields | External identities use configured namespaces | `CentralBrandExternalIdentity` | C | Deferred | Do not add free-form identifier columns to the profile. |

## CA-014 — Brand Media / Logo

Phase 18.4 converges the honest single-role implementation into a focused Brand logo manager. The prototype remains a hierarchy and polish reference rather than authority for its multi-role DAM domain.

| Prototype region | Current desktop / mobile equivalent | Domain source | Gap | Phase | Notes |
|---|---|---|---|---|---|
| Primary logo preview and controls | Contained preview, real state badges, primary Replace and secondary assignment removal | Shared Media Core assignment and variants | Converged (A/D) | Phase 18.4 | Upload is modal; no uploader or large destructive action remains permanently open. |
| Missing/unavailable media health | Polished no-logo, Processing, Failed and Unavailable states with existing recovery actions | Media assignment usability + derived Quality | Implemented (A) | Phase 18.4 | Missing variants never override a usable normalized master. |
| Responsive asset metadata/actions | Compact details/variants rail; single-column before 1280px; explicit tablet/mobile coverage | Existing Media asset/variant read model | Converged (D) | Phase 18.4 | Long values wrap and controls remain usable at 390px. |
| Wordmark, symbol, dark/light, hero and OG slots | No equivalent | Unsupported Brand media roles | C | Deferred | Do not render placeholders that imply role support. |
| Localized/site media | No equivalent | Future localized/site media | C | Deferred | Remains outside global canonical media. |
| Generic library/DAM browser | Lazy bounded Shared Media picker only | Existing compatible-asset selector; generic DAM redesign | Adapted / C | Phase 18.4 / Deferred | Initial page does not query/render the library; generic management remains elsewhere. |

## CA-015 — Brand Translations

Current desktop/mobile implements the common translation status model, exact-locale routes and source-hash context, but differs from the prototype's dense locale workspace, two-column editor balance and activity emphasis.

| Prototype region | Current desktop / mobile equivalent | Domain source | Gap | Phase | Notes |
|---|---|---|---|---|---|
| Locale navigation with health | Selector and status badges exist | Active Locales + `BrandTranslation.status` | D | Phase 18 | Make locale health faster to scan and retain exact-locale URLs. |
| Source/target editor columns | Current source context and target form are more vertically separated | Canonical source hash context + `BrandTranslation` | D | Phase 18 | Restore denser desktop balance; stack source before target on mobile. |
| Translation fields | Name, tagline, descriptions and SEO fields exist | `BrandTranslation` only | A | Phase 18 | Converge grouping for supported fields, not canonical Brand columns. |
| Missing/Machine/Human/Approved/Outdated | Existing common states | Common translation status/source hash | B | Phase 18 | Preserve exact approved terms; do not invent prototype review states. |
| Activity/context | Existing audit/context is less visually prominent | Current audit and source metadata | D | Phase 18 | Place as secondary context without competing with the editor. |
| AI translate/provider actions | No workflow exists | Future AI translation domain | C | Deferred | A supported `MachineTranslated` value does not imply a provider. |
| Per-field review/provenance | Row-level status only | Future field-level provenance/review | C | Deferred | Do not fabricate per-field state. |
| Localized media/site delivery | No equivalent | Future localized/site media/projection | C | Deferred | Remains intentionally absent. |

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

CA-014 A/B/D work is closed and final-converged. Before convergence, a tall preview, permanently open uploader, full-width empty Variants card, always-rendered six-card media selector, and prominent red removal control made the page read as a technical file warehouse. The final `brand-media-v5` composition uses an approximately 70/30 desktop workspace: Primary logo and its compact actions sit above Generated variants in the left rail, while an independent compact Asset details card begins at the same top edge in the right rail. Replace is primary, Choose remains secondary, and `Remove logo from brand` lives in the established overflow. Empty, Processing, Failed, and Unavailable are explicit delivery states with the same replacement recovery actions.

The Shared Media selector is now an inline disclosure below the workspace rather than a popup. Its compact shell is always discoverable, but results load only for an explicit `picker=1` request. It retains stable six-item server pagination, existing filename/checksum/numeric-ID search, eager variant loading, safe URL resolution, server-side compatibility checks, a visible `Current`/`aria-current` state, and no-op prevention. Upload remains the existing secure Shared Media ingest in a focus-managed modal; assignment changes only after success, while failed replacement preserves the prior logo and old assets survive replacement/removal.

The reviewed 1440×1000 final frame shows Primary logo, all Asset details, Generated variants, and the compact picker disclosure; 1024 retains the aligned two-column workspace, while 768×1024 and 390×844 preserve the stacked priority order and have no horizontal overflow. The separate 1440×1000 expanded-picker frame verifies the inline compact four-column card grid. Dark/light logos, wordmarks as a separate role, favicon, hero, OG, localized or Site media, Media Completeness, alt-text completeness, Edit Image, and Delete Asset remain intentional architectural divergences. CA-014 manages one exact global primary `brand_logo` assignment and never deletes the Shared Media asset.

## Remaining bounded backlog

Only A/B/D work is eligible. Category C rows above are explicitly excluded.

| Screen | Prototype region / class | Exact acceptance target |
|---|---|---|
| CA-015 | Locale health/status mapping (B/D) | Make all active locales and common statuses scannable, preserve exact-locale navigation and bound the selector at 390. |
| CA-015 | Source/target editor and activity (A/D) | Match the prototype's two-column desktop balance for supported `BrandTranslation` fields and stack logically on mobile, with activity kept secondary. |
