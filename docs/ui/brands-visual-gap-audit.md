# Brands visual gap audit — CA-011…CA-015

Audit date: 2026-09-01. Baseline: `develop` at `03f9ad8` after Brands Phase 16. Prototype reference version: `brand-prototype-v1`.

This audit compares the original Brand prototypes with the Phase 16 desktop implementation and its responsive mobile composition. The PNGs in `pictures/1. Central Admin/1.3. Brands/` are the design source; `tests/Visual/baselines/` are regression evidence only. Every row is classified once: **A** implement from an approved domain source, **B** map prototype language to existing semantics, **C** intentional future-domain gap, or **D** pure composition/visual debt.

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

Phase 16 desktop is a conventional title/filter/table page; mobile bounds the table but retains a wide-table interaction. The prototype is a denser operating dashboard with summary metrics, stronger Brand identity rows, and more scan-oriented filters.

| Prototype region | Current desktop / mobile equivalent | Domain source | Gap | Phase | Notes |
|---|---|---|---|---|---|
| Total, Active and review summary cards | No summary band; mobile also starts directly with filters | `CentralBrand`, lifecycle counts, derived Brand Quality | A | Phase 18 | Add bounded aggregate cards; map review to Quality rather than status. |
| `Needs Review` | Quality is available on detail, not as a list status | `CentralBrandQualitySummary` semantics | B | Phase 18 | Label must be `Needs attention`; never add `CentralBrandStatus`. |
| Logo-led Brand rows | Name/slug table row without the prototype's identity weight | Shared Media `brand_logo` | A | Phase 18 | Add bounded logo selection and preserve honest missing/unavailable states. |
| Product and category context | Product count exists; category coverage is absent from the list | Derived Products and grouped Product→Category coverage | A | Phase 18 | Use grouped aggregates, never stored counters or manual Brand categories. |
| Translation health | No compact locale-health column | Active Locales + `BrandTranslation.status` and source hash | A | Phase 18 | Grouped read only; terminology follows common translation states. |
| Lifecycle filter/status | Lifecycle filter and badge exist but visual density differs | Draft / Active / Archived | D | Phase 18 | Match the prototype's compact scan rhythm without new lifecycle values. |
| Rich search/filter/action composition | Simpler filters and larger whitespace | Existing search, lifecycle and create actions | D | Phase 18 | Recompose at 1440 and adapt controls for 390 rather than scaling down. |
| Media/site coverage and Published/Synced columns | No equivalent | Future SiteBrand/projection and unsupported media roles | C | Deferred | Must not be synthesized from lifecycle or the single global logo. |

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

## Phase 18 bounded backlog

Only A/B/D work is eligible. Category C rows above are explicitly excluded.

| Screen | Prototype region / class | Exact acceptance target |
|---|---|---|
| CA-011 | Summary metrics and review mapping (A/B) | At 1440, render bounded Total/Active/Needs attention aggregates above the table; at 390, keep metrics and filters readable without page overflow. |
| CA-011 | Identity, product/category/translation rows (A) | Add grouped logo, real product/category counts and translation health without per-row queries or stored counters. |
| CA-011 | Filter/table density (D) | Match the prototype's compact scan hierarchy and action placement while retaining accessible responsive table behavior. |
| CA-013 | Canonical form and ownership composition (A/D) | Recompose existing fields and Organization picker into prototype-like desktop proportions; preserve current validation, persistence and mobile field order. |
| CA-013 | Tags/save semantics mapping (B) | If Tags are surfaced, reuse the existing editorial tag action; label save actions without publication semantics. |
| CA-014 | Primary-logo workspace (A/D) | Increase identity/preview hierarchy, compact real asset metadata and strengthen missing/unavailable states on desktop and mobile using only `brand_logo`. |
| CA-015 | Locale health/status mapping (B/D) | Make all active locales and common statuses scannable, preserve exact-locale navigation and bound the selector at 390. |
| CA-015 | Source/target editor and activity (A/D) | Match the prototype's two-column desktop balance for supported `BrandTranslation` fields and stack logically on mobile, with activity kept secondary. |
