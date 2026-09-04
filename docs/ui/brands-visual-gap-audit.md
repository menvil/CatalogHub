# Brands visual gap audit — CA-011…CA-015

Audit date: 2026-09-02. Baseline: `develop` at `8e51ab9` after Brands Phase 17. Prototype reference version: `brand-prototype-v1`.

This audit compares the original Brand prototypes with the current desktop implementation and its responsive mobile composition. The PNGs in `pictures/1. Central Admin/1.3. Brands/` are the design source; `tests/Visual/baselines/` are regression evidence only. Every row is classified once: **A** implement from an approved domain source, **B** map prototype language to existing semantics, **C** intentional future-domain gap, or **D** pure composition/visual debt. Phase 18.1 closes the eligible CA-011 A/B/D work.

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
| Rich search/filter/action composition | Name/slug/company search; consistent Country, coverage, translation and quality filters; one global Clear; overflow actions | approved read model and routes | Converged (D) | Phase 18.1 | Explicit 6/3/2/1 control grids prevent intermediate-width overflow; query state persists through sort/page/per-page and history. |
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
| Recent Products, price/rating snapshots | Count and category coverage only | Product-management/read-model concerns beyond Brand overview | C | Deferred | CA-012 does not become Product management. |
| Field source/confidence/history | External identity links only | Future field-level provenance | C | Deferred | Do not infer canonical auto-update or matching confidence. |

## CA-013 — Brand Create / Edit

Desktop implements the approved canonical form and Phase 16 Organization ownership, but uses a more spacious generic form hierarchy. Mobile is usable and sequential, while the prototype's desktop density and contextual side rail are not yet represented.

| Prototype region | Current desktop / mobile equivalent | Domain source | Gap | Phase | Notes |
|---|---|---|---|---|---|
| Compact canonical identity/profile sections | Correct fields in larger generic sections | Canonical Brand input | D | Phase 18 | Match grouping, widths and action placement at 1440; preserve logical mobile order. |
| Parent Company selector | Approved Organization picker exists | `CentralBrandOwnership → Organization` | A | Phase 18 | Converge visual placement only; keep one owner and exact Organization semantics. |
| Website/support/contact/color | Existing canonical controls | Canonical Brand fields | D | Phase 18 | Improve density and hierarchy without persistence changes. |
| Tags in prototype form | Tags are currently managed from CA-012 | Existing editorial Tags | B | Phase 18 | Decide presentation using the existing tag action only; do not merge with categories. |
| Publish/save controls | Save creates/updates; lifecycle is separate | Existing save plus CA-012 lifecycle | B | Phase 18 | Use Save/Create language; no Save & Publish shortcut. |
| Description, SEO, visibility, site assignment | No canonical equivalent in Brand profile | Translation or future Site projection domains | C | Deferred | Must not become new Brand columns. |
| Manual category assignment | No editor by design | Category coverage is derived from Products | C | Deferred | Prototype control conflicts with approved semantics. |
| Arbitrary external identifier fields | External identities use configured namespaces | `CentralBrandExternalIdentity` | C | Deferred | Do not add free-form identifier columns to the profile. |

## CA-014 — Brand Media / Logo

Current desktop/mobile is an honest single-role workspace with upload/replace/remove and delivery state. The prototype is a multi-role DAM board; most of that apparent fidelity would require unapproved media domains.

| Prototype region | Current desktop / mobile equivalent | Domain source | Gap | Phase | Notes |
|---|---|---|---|---|---|
| Primary logo preview and controls | Exact `brand_logo` workspace exists | Shared Media Core assignment and variants | D | Phase 18 | Tighten identity framing, preview scale and metadata density. |
| Missing/unavailable media health | Honest empty and unusable states exist | Media assignment usability + derived Quality | A | Phase 18 | Align state prominence and route back to overview health. |
| Responsive asset metadata/actions | Safe stacking exists but is vertically loose | Existing Media asset/variant read model | D | Phase 18 | Bound long names and keep controls visible at 390. |
| Wordmark, symbol, dark/light, hero and OG slots | No equivalent | Unsupported Brand media roles | C | Deferred | Do not render placeholders that imply role support. |
| Localized/site media | No equivalent | Future localized/site media | C | Deferred | Remains outside global canonical media. |
| Generic library/DAM browser | Existing bounded upload/selection workflow only | Generic DAM redesign | C | Deferred | Phase 18 does not redesign DAM. |

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

CA-011 A/B/D work is closed. The final screen uses the original prototype—not the former regression baseline—as its hierarchy and density target. The reviewed result has five database-derived KPIs, a six-control operational filter bar with one global Clear, larger logo-led identity rows, grouped Product and Category context, explainable active-Locale translation coverage, a separate authoritative Quality column, overflow actions, and bounded pagination. The `1440x1000`, `1024x900`, `768x1024`, and `390x844` references were reviewed against the original and pre-polish Phase 18.1 result before their `brands-list-v3` baselines were approved.

Intentional differences remain explicit: Sites requires a future Site Brand projection; `Needs Review` is derived `Needs attention`; Language/Market is active-Locale Translation; numeric Media is omitted because only canonical logo identity has an approved contract; checkboxes wait for an approved bulk workflow; monthly trends wait for historical analytics; and the global shell remains outside screen ownership. The stable Imports destination is Product-oriented, so no misleading Brand import action is shown.

## Remaining bounded backlog

Only A/B/D work is eligible. Category C rows above are explicitly excluded.

| Screen | Prototype region / class | Exact acceptance target |
|---|---|---|
| CA-013 | Canonical form and ownership composition (A/D) | Recompose existing fields and Organization picker into prototype-like desktop proportions; preserve current validation, persistence and mobile field order. |
| CA-013 | Tags/save semantics mapping (B) | If Tags are surfaced, reuse the existing editorial tag action; label save actions without publication semantics. |
| CA-014 | Primary-logo workspace (A/D) | Increase identity/preview hierarchy, compact real asset metadata and strengthen missing/unavailable states on desktop and mobile using only `brand_logo`. |
| CA-015 | Locale health/status mapping (B/D) | Make all active locales and common statuses scannable, preserve exact-locale navigation and bound the selector at 390. |
| CA-015 | Source/target editor and activity (A/D) | Match the prototype's two-column desktop balance for supported `BrandTranslation` fields and stack logically on mobile, with activity kept secondary. |
