# Categories / Schema prototype gap audit — CA-016…CA-026

Phase 19.0, 2026-10-04. Audited source: `develop` `fea2cc7bc71e2972074d7ebe2b7e20319abf04b4` after PR #611. All eleven exact approved PNGs in `pictures/1. Central Admin/1.4. Categories : Schema/` were inspected at 1448×1086. Hashes/dimensions/locale are registered in the [visual manifest](../planning/cataloghub-v2-visual-reference-manifest.md) and `visual-references.json` as `categories-schema-prototype-v1`. They are source references, not screenshots of implemented screens. No CA-016…CA-026 regression baselines or finished-screen acceptance are claimed.

Authority: [ADR-0003](../architecture/adr/0003-categories-schema-ownership.md), [migration map](../planning/categories-schema-migration-map.md), [serial roadmap](../planning/roadmap-v2-screen-driven.md). A generic existing Filament screen does not establish prototype parity. Hidden menus/other tab bodies are not shown in these PNGs; only their visible affordances are audited, with no invented hidden requirements.

Classification is exactly: **A** directly supported by the existing domain; **B** valid product requirement needing backend/domain work; **C** presentation concept adapted to real semantics; **D** rejected because it duplicates, contradicts or pollutes a domain boundary. This legend intentionally differs from the older Brand audit. A row's classification describes its concept at the discovery base; even A rows require integration, permission, audit and safety work. Phase column means future Phase 19 implementation, **never 19.0**. `—` means omitted/deferred. Permission shorthand: **CAT**=`catalog.categories.manage`; **SCH**=`catalog.schema.manage`; **TR**=`translations.manage`. Every persisted mutation also requires `central.mutation.execute`; every screen requires Central admission. “Site access” in preview rows means ADR-0003's bounded Central preview read policy; actual Site-workspace navigation additionally requires existing Site permission/membership. Navigation to another capability appears only if allowed.

## Shared visible elements and states

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| CatalogHub shell, sidebar, global search/help/bell/sparkle/Quick Add/avatar/backup promotion | Common navigation and global actions | Existing shared shell/registry; mock icons do not define new actions | C | Use approved shared shell; no new notifications/AI/backup feature from these references | Shared design system and stable registry | Screen + destination capability | Existing shell only |
| Headers/breadcrumbs/back/tab links | Category context and workflow navigation | Category identity exists; most target routes not implemented | C | Stable Category ID context; link only real authorized destinations; no Site workspace mutation | Registry/routes/query boundaries | CAT/SCH/TR per destination | Yes, each screen |
| Drag handles, numeric order, sorting and pagination | Immediate-looking reorder, table sorting/paging | Stored positions and ordered scopes exist; generic listing only | B | Deterministic ID tie-break, server paging; separate explicit transactional reorder with stale revision checks; keyboard alternative | F01/F03/F07 ordering actions | Owning capability | Yes |
| Save/Discard/Cancel/actions, timestamps | Commit or abandon edits | Most existing writes lack mature audit/revision contract | B | Explicit Save; client-only cancel/discard; truthful DB timestamps; disabled/no-op states and errors preserve input | F07 + audit/transaction | Owning capability + Central mutation | Yes |
| Empty/loading/error/forbidden/validation/stale states (not pictured) | Single populated reference implies a complete workflow | Existing components, incomplete module workflows | B | Future screen contract names real empty/unavailable/no-product/conflict/validation states; no invented counts/placeholders | Read models + action errors | Owning capability | Yes, each screen |

## CA-016 — Categories List (19.4)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Total Categories / Active cards | Counts and active percentage | Category status/table | A | Real counts with declared lifecycle scope; archive filter available | Category list query | CAT | Yes |
| Total monthly +3.1%, KPI ellipses | Historical growth / menu | No Category analytics or KPI menu contract | B | Omit fabricated trends and dead menus | Historical analytics deferred | CAT | — |
| With Schema card | Counts categories with schema | Sections/definitions/schema status available but no precise readiness metric | C | Count categories with assignments, separately show real schema lifecycle; not “all required attributes defined” | Assignment aggregate | CAT | Yes |
| Missing Translations card / Market-Language filter | Translation health / locale context | CategoryTranslation + active Locales, no market-owned Category | C | Explicit active-Locale translation filter and missing-row count; no canonical Market field | Shared translation scoped summary | CAT | Yes, read-only summary |
| Needs Review card and status badge | Appears as Category lifecycle | Schema validator/status exist, no Category Needs Review | C | Separate schema issues/approval column and filter; never add lifecycle enum | Schema read model/F07 | CAT | Yes |
| Search / Level / Status / Filters | Name/slug, hierarchy depth, lifecycle selection | Name/slug search and parent relation; no depth query | B | Real indexed name/slug search, derived depth/parent scope, exact lifecycle; useful clear controls only | Hierarchy list query | CAT | Yes |
| Category icon/color, child arrow, name/slug/Parent | Visual taxonomy identity | Name/slug/parent supported, no media/color contract | C | Identity text and hierarchy marker; shared fallback icon, no stored image/color | Category query | CAT | Yes |
| Products / Attributes / Facets columns | Usage counts | Products/definitions/FacetDefinitions available | C | Direct Product count, assignment count, canonical facet count; no inherited totals implied | Grouped aggregate query | CAT | Yes |
| Sites filter / Sites count | Site use or publication implied | SiteCategory selection and SiteCategoryProjection exist | C | Derived distinct non-archived selected Sites; label “Selected by Sites”; no editable Category Site field or publication claim | Site-domain bounded read summary | CAT | Yes, derived |
| Updated / table row actions / New Category | Open/edit/new operations | Timestamps and generic create/edit | A | Stable links and legal Category lifecycle actions; no approval shortcut | F01 actions + registry | CAT | Yes |
| Checkboxes / New dropdown / Import Categories | Bulk/import workflow | No approved Category import/bulk action contract; Product Imports are separate | B | Omit bulk selectors and import/dropdown affordance until an explicit domain workflow; simple New Category | Imports future contract | CAT | — |

## CA-017 — Category Detail (19.5)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Name/Active/Edit/Actions | Canonical identity and operations | Category fields and generic editor; no detail route yet | A | Source/localized identity, separate lifecycle/schema status, legal lifecycle actions and Edit | Category detail query/F01 | CAT | Yes |
| ID/Slug copy / Parent link / Created/Updated | Record facts | Direct fields/relationship/timestamps | A | Read-only facts with clipboard and stable authorized parent link | Category query | CAT | Yes |
| Description | Canonical-looking prose | CategoryTranslation.description only | C | Locale-labeled translated description/fallback; edit in CA-026 | TranslationResolver | CAT read / TR write | Yes |
| Sections/Attributes/Required Attributes/Facets/View Schema | Schema totals and shortcut | Local model counts and flags | C | Real Sections/assignments/required/facets summary; authorized schema destination | Assignment/facet aggregates | CAT read / SCH navigation | Yes |
| Comparison Set / Comparison Sets | Named reusable sets and count | No set domain | D | One configured comparison row count/summary; no set entity | F09 comparison rows | CAT read / SCH edit | Adapted summary |
| Facet Sets / Used in Channels / Variants Configuration Enabled | Additional domains/usage | No authoritative Category contracts | D | Omit; FacetDefinitions are sufficient, Product variants remain Product-domain | Separate product decisions needed | CAT | — |
| Products / Used in Sites / Site chips / View all Sites | Counts/related Site usage | Product FK and Site selections available | C | Direct Product count and read-only selection summary; authorized Site destination only; no arbitrary market flags or publication inference | Site-domain aggregate + access | CAT | Yes, adapted |
| Created By / Updated By / Recent Activity / View Full Activity | Actor history | Category lacks actor fields/events; shared audit exists | B | Real attributed future events, legacy “unavailable”; bounded feed, existing authorized activity destination if supported | Required audit registry | CAT | Yes, bounded feed |
| Add Attribute / Add Facet / Manage Translations / schema tabs | Quick operations | Older builder/facet resource/translation editor | C | Navigate to owning workflow; never Category-permission mutation shortcut | Registry + capability split | SCH or TR | Yes |
| Duplicate Category | Copies entire Category/domain | Only CloneCategorySchema exists and duplicates definitions today | D | No duplicate Category. Defer explicit schema copy workflow until it can reuse global meanings safely | F02 clone redesign | SCH | — |
| Export Category | Implies complete export | Schema array export and snapshot exports exist, no complete Category export contract | C | Defer header action; existing schema export may remain under its own versioned contract, not relabeled Category export | Export version contract | SCH | — for new action |

## CA-018 — Category Create / Edit (19.6)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Parent selector/clear | Pick one parent | FK and descendant-excluding dropdown | B | Server cycle/concurrency/reorder validation; explicit root allowed | F01 hierarchy action | CAT | Yes |
| Name / Slug | Public-looking canonical input | Reference name + unique URL key exist | C | Label source/reference name; editable canonical slug with route impact; display copy translated separately | F01 validation/query | CAT | Yes |
| Status / “Active categories are visible in your catalog” | Lifecycle implies publication | Category lifecycle and Site visibility separate | D | Replace helper: Active is canonical availability; Site selection/publication is separately managed | Category lifecycle action | CAT | Adapted lifecycle only |
| Display Order | Scalar sibling placement | Position exists, unsafe direct concurrent writes possible | B | Validated sibling placement/reorder with revision, not arbitrary conflicting number | F01 | CAT | Yes |
| Description rich editor | Saves localized copy as canonical prose | Only CategoryTranslation supports description | D | Remove canonical field; authorized link to CA-026; no second description column or implicit Locale write | Translation workflow | TR at destination | — in canonical form |
| Icon upload/Remove / Color | Category appearance | No approved Category media role/color domain | D | Omit upload/color; fallback visual identity only | Separate media/presentation decision | CAT | — |
| Category Code | Integrations identity distinct from ID/slug | No code contract or consumer | D | Omit speculative second identity; ID is stable | External identity contract future | CAT | — |
| General/Settings/Display/SEO/Advanced tabs | Hidden settings domains | Only basic Category fields; SEO belongs CA-025 | C | One focused canonical form; authorized related schema/SEO/translation links, no empty tabs | Registry/capabilities | CAT; SCH/TR destinations | Yes, adapted |
| Summary: With Schema / Sections / Products / Sites / Last Updated | Read summary while editing | Existing relations/timestamps; derived metrics need query | C | Actual persisted-record context; create state shows no fabricated schema/count/update. With Schema is presence, not approval | Category summary query | CAT | Yes |
| Cancel / Save Category | Submit one canonical form | Generic create/edit writes | B | Explicit validated transactional action + minimized audit; no schema status edit | F01 permission/audit | CAT + Central mutation | Yes |

## CA-019 — Category Schema Builder (19.7, final 19.11)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Sections list/count/selected row / attributes count | Master/detail schema navigation | Ordered Sections/local definitions | C | Flat local Sections and assignments; Ungrouped visible when present | F02/F03 read model | SCH | Yes |
| Section drag / order / Add Section / ellipsis | Reorder/create/manage | Create/update/remove actions, partial ordering safety | B | Revision-checked order and guarded removal; named legal actions only | F03/F07 + audit | SCH | Yes |
| Section Name / Slug | Reference label and machine key | Section name/code, underscore code validation | C | Source name and stable Code; no hyphen-slug rules or routing | Section action/code guards | SCH | Yes |
| Section icon/change icon | Stored decorative identity | No Section icon/media domain | D | Shared fallback icon; no editable persistence | Presentation only | SCH | — for editor |
| Section Description | Localized explanatory text | SectionTranslation.description | C | Read translated context; link to CA-026, not duplicate canonical field | Shared Section translations | SCH read / TR write | Yes, adapted |
| Attribute name / code / Required | Meaning + local requirement | Current category-local definition fields | C | Reused global definition, required from assignment; edit scopes clearly separated | F02 | SCH | Yes |
| Attribute Type / colored legend | Number/Select/Boolean/Text/Multiselect/File | Canonical enum has eight types, no File | C | Explicit string/text/integer/decimal/boolean/enum/multi_enum labels; legacy JSON read-only | F04 | SCH | Yes |
| File in legend | File-valued Product fact | No File persistence; Media separate | D | Omit File type | Media requirement deferred | SCH | — |
| Variants column | Counts selectable alternatives | No Category variant definition; options exist | C | Rename Options, count enum choices; no variant schema inference | Option aggregate | SCH | Yes |
| Add Attribute / row actions / drag | Create/edit/move | Local definition creation/move | B | Assign existing global meaning first; explicitly create canonical meaning if absent; move assignment, no definition clone | F02/F07 | SCH | Yes |
| Collapsed Sections drop area / Other Sections panels | Collapse/reveal editor navigation | Public collapsibility exists, no collapsed authoring entity | C | Local editor collapse state; never hidden/archived/nested schema mutation | UI state; F03 | SCH | Yes |
| Preview Schema | Render Product-style schema | Structural preview builder, incomplete real consumer parity | B | Shared assignment-based preview and real validation issues, explicit schema revision | Consumer convergence 19.3 | SCH | Yes |
| Review/Approve/Archive not visibly pictured | Existing schema lifecycle must survive builder | Existing actions with approval invalidation gap | B | Explicit lifecycle actions separate from Category; current-revision validation, no direct status select | F07 | SCH | Yes |

## CA-020 — Attribute Sections Editor (19.8)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Total Sections / Active Sections | Count by lifecycle | Sections + `is_visible`; no Section status enum | C | Total/Visible Sections; visibility is presentation configuration, not lifecycle | Local Section query | SCH | Yes |
| With Translations / 100% | Locale completeness | Section translations exist, no stated denominator | C | Active-Locale row coverage with declared scope and Missing/Outdated distinctions; no invented complete fields | Shared summary | SCH read | Yes |
| Display Order Complete 100% | Quality metric | Positions exist, no metric | C | Deterministic ordered list + validation issue count; omit fictitious completeness percentage | Schema validator | SCH | Yes, adapted |
| Search name/slug / Status / Sort | Filter Sections | Source name/code and visibility available | C | Search source name/code/localized label; visible/hidden filter; position/source-name sort | Section list query | SCH | Yes |
| Order/drag / Name/icon / Slug | Persist placement and identity | Position/name/code supported, no icon | C | Flat local Sections, stable code, visual fallback icon; explicit safe reorder | F03/F07 | SCH | Yes |
| Attributes / Updated | Local usage and time | Definitions count/timestamp | C | Assignment counts and actual timestamps | Assignment aggregate | SCH | Yes |
| Active badge / Display Label | Lifecycle and localized duplicate label | Visibility and SectionTranslation.name | C | Visible/Hidden and resolved translated label with Locale context; no canonical duplicate label | TranslationResolver | SCH read / TR write | Yes |
| Add Section / row menu | Manage Sections | Existing actions | B | Create/update/guarded empty removal; no hard remove with assignments or unsafe ordering | F03/F07/audit | SCH | Yes |
| Category Schema Preview rail / View Full Schema | Ordered section summary | Structural preview builder | B | Same assignment-backed flat schema as CA-019, not separately persisted structure | Shared preview | SCH | Yes |

## CA-021 — Attribute Definitions / assignment workflow (19.9)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Screen-section title / Total Attributes | Category-local definition inventory | Category-local definitions today | C | Assigned global definitions in selected Category/Section; global impact explicitly visible | F02 assignments | SCH | Yes |
| Required KPI / row checkbox | Intrinsic required flag | Definition.is_required exists | C | Assignment-level required editor/aggregate | Assignment action | SCH | Yes |
| Filterable / Comparable KPI/columns | Intrinsic flags | Both definition flags exist, competing consumers | D | Derived facet/comparison configuration membership and links; no intrinsic toggles | F08/F09 | SCH | Adapted derived display |
| Monthly +2.1% / card ellipses | Trend/menu | No historic Attribute analytics | B | Omit | Analytics future | SCH | — |
| Name / Code / Type | Define meaning in Category | Definition fields exist but local ownership | C | Global canonical code/type/source identity; reuse existing before creating; value guards and usage confirmation | F02/F04 | SCH | Yes |
| Number/Text/Dropdown/Boolean | UI type names | Canonical AttributeDataType | C | Integer/Decimal must be explicit; Dropdown→enum; string/text clarified | F04 | SCH | Yes |
| Variants | Option-like counts | Options exist; variant consumer does not | C | Options count/open CA-022 for enum types only | Option query | SCH | Yes |
| Unit | Free editable unit-looking value | Free strings + real measurement catalog | B | Global definition's relational dimension/unit pair; compatible existing-unit selectors, no new Unit management | F06/backfill | SCH | Yes |
| Type / Status / search / Filters | Inventory filtering | Type/name/code; no Attribute lifecycle enum | C | Type and assignment visibility filters, name/code search; legacy JSON explicitly read-only | Assignment query | SCH | Yes |
| Import Attributes / checkbox bulk / Add dropdown | Bulk import/create | No canonical Attribute import contract | B | Simple Assign existing/Create meaning; no misleading Product Import shortcut or bulk mutation | Separate import decision | SCH | — for import/bulk |
| Row actions / Add Attribute / Updated | CRUD local definitions | Old actions/timestamps | B | Separate global definition edit from local assignment config; safe unassign, no canonical delete via Category | F02/F07/audit | SCH | Yes |

## CA-022 — Attribute Options Editor (19.10)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Attribute context/Back / Total Options | Category-breadcrumb editor of choices | Global option ownership is currently through local definition | C | Global definition/options context with all using Category impact; Category is navigation context | F02/F05 query | SCH | Yes |
| Active Options / Active status | Choice lifecycle | `is_visible` only | C | Visible/Hidden (retired from new choices); retain references | F05 consumers | SCH | Yes |
| Option Value | Internal identity e.g. IPS | Option.code with lowercase-underscore validation today; persisted code referenced by facts | C | Stable machine Code; preserve all legacy codes exactly, immutable ordinary edits; label can be IPS | F05 guards | SCH | Yes |
| Display Name | Human label | Option.label + translations | C | Source/reference label, localized display through OptionTranslation | Existing typed copy | SCH reference / TR localized | Yes |
| Slug | Second required URL identity | None; option has no independent URL | D | Omit; no code/value/slug triple | F05 | SCH | — |
| Description | Canonical-looking prose | OptionTranslation.description | C | Localized text on CA-026, read context here | Shared Option translations | SCH read / TR write | Yes, adapted |
| Order / drag | Order choices | Position supported, concurrency incomplete | B | Global choice order with revision/lock/affected-schema invalidation | F05/F07 | SCH | Yes |
| Default Option toggle / Default badge | Prefills Product fact | No default contract; would invent missing facts | D | Omit, no implicit defaults | F05 | SCH | — |
| Add/Edit/Cancel/Save / Filters/menu/pagination | CRUD options | Actions exist but unrestricted delete/code update | B | Explicit safe Save/hide/remove-unreferenced; used code blocked; audit/no-op; filters for visibility | F05/audit | SCH + Central mutation | Yes |

## CA-023 — Category Facets Config (19.12)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Attribute column / Add Facet | Select facet source | Attribute/Brand/Rating sources supported but Category-local definition check | B | Assignment-backed Attribute source; preserve existing Brand/Rating support; no duplicate definition | F08/consumer cutover | SCH | Yes |
| Facet Label | Editable display label | label_override exists, no typed facet localization | B | Translated Attribute label by default; reference override + typed locale override if needed | FacetTranslation shared workflow | SCH reference / TR localized | Yes |
| Type List / Range | Filter semantics | FacetType checkbox/select/range/boolean | C | Real supported type validated against canonical value type; numeric screen size is Range unless explicit buckets exist; HDR boolean remains boolean | Facet validation | SCH | Yes |
| Show on Sites | Global Site visibility toggle | Facet visibility/activity + independent Site override | C | Canonical default visibility; no Site override writes/publication switch | F08 | SCH | Yes |
| Order / drag | Filter order | Facet position | B | Explicit atomic saved order | Schema revision/action | SCH | Yes |
| Widget Checkbox List / Range Slider | Arbitrary widget control | Existing facet type/public widgets, no separate general widget catalog | C | Choose only a supported type/widget pairing; no competing widget persistence | Public widget contract | SCH | Yes, restricted |
| Searchable toggle | Search inside options or product full text ambiguous | No facet option-search authority; Attribute searchable is full text | B | Defer option-list search; never bind to assignment full-text is_searchable | Future public widget contract | SCH | — |
| Search facets/All Types/row menu | Config list operations | Facet models and resource | A | Search real attribute/code/reference labels, filter supported types, safe add/remove | Facet query/actions | SCH | Yes |
| Public preview: option labels/counts/checkboxes/Show more/range inputs | Live public filter rendering | Real facet queries/public widgets exist, no cohesive admin preview | B | Authorized Site/Locale, same resolver/document values; real freshness-aware counts/ranges; boolean/enum mismatch corrected, no static numbers | Shared preview/facet documents | SCH + Site access | Yes |
| Last updated by / Discard / Save Changes | Attributed batch save | Timestamp exists, no facet mutation audit | B | Explicit transaction, one semantic save plus approval invalidation; real actor from event | F07/audit | SCH + Central mutation | Yes |

## CA-024 — Category Comparison Config (19.13)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Comparison Attributes / Add / menu | Select reusable facts | Only definition comparable flag; public renders all specs | B | Rows referencing Category assignments, unique membership; remove config only | F09 foundation 19.3 | SCH | Yes |
| Compare Label | Category-specific override | No comparison label domain | B | Optional reference + shared typed locale override; default AttributeTranslation.label | Comparison translation extension | SCH reference / TR localized | Yes |
| Order / drag | Ordered rows | Definition/Section positions only | B | Comparison position sole order, safe explicit Save | F09/F07 action | SCH | Yes |
| Show | Visibility in comparison | Old flag is incomplete | B | Comparison row is_visible; definition/normal spec visibility is not a second authority | Comparison config | SCH | Yes |
| Highlight Differences | Per-row highlighting | Public builder does not compare values | B | Typed inequality flag/result; missing distinct from zero/false; enum code/set equality | Shared comparison evaluator | SCH | Yes |
| Priority High/Medium/Low | Row prioritization | None | B | Bounded emphasis only; explicit order remains authoritative | F09 priority enum | SCH | Yes |
| Preview Products/images/values | Real public preview | Same-category 2–4 Product projection builder exists | B | Explicit real Products and authorized Site/Locale; shared config/projection contract, honest missing/stale states | Projection/comparison convergence | SCH + Site access | Yes |
| Green numeric/enum cells | Implies best/winner values | No better-is-higher/lower/enum ranking semantics | D | Neutral difference highlighting, never infer winner from quantity/OLED label | F09 typed equality | SCH | Adapted highlight |
| Save Configuration / “Changes are saved automatically” | Contradictory save models | No complete config writer | C | Explicit Save, local dirty preview/discard and stale-revision rejection | Comparison action/audit | SCH + Central mutation | Yes |

## CA-025 — Category SEO Templates (19.14)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Title Template | Literal + variable expression | Static SEO text only | B | Real restricted template for category-page or product-page target, explicit Locale/reference copy, compiled on Save | F10 CategorySeoTemplate + typed text | SCH structure / TR localized | Yes |
| Meta Description Template | Dynamic Product description | Static translated SEO only | B | Same safe evaluator, no executable expressions/HTML, deterministic missing values/fallback | F10 | SCH / TR localized | Yes |
| Available variable chips / More / variables rail | Category, Brand and attribute expansion | Domain values exist, no whitelist/binding registry | B | Category/identity/Brand reserved whitelist; assigned eligible attributes only with ID binding; reject unknown/incompatible scope variables | Safe compiler/registry | SCH | Yes |
| URL Template + example/variables | Changes public Product/category path | URLs generated from canonical/Site slugs; no redirects/collision system | D | Read-only actual canonical URL; no stored URL template, no slug/path side effects | Existing Site routing/F10 | SCH read | — for editable template |
| Meta Robots | Template indexing policy | Existing indexability hardcodes robots | B | Restricted index/noindex × follow/nofollow; cannot bypass hidden/pending Site restriction | Template policy + SeoProjectionBuilder | SCH | Yes |
| Live Title/URL/Description / SERP | Real resolved Product preview | Projection SEO builder exists; no template evaluator/selector | B | Explicit real Product + Category/Site/Locale, template and effective override result/provenance, no-product/missing/stale states | Shared evaluator + authorized preview | SCH + Site access | Yes |
| Character counts and recommended lengths / best practices | SEO advice/counters | No template count service | C | Count rendered preview text, advisory length hints; no fake CTR/engine guarantees | Preview output | SCH | Yes, presentation |
| Save Changes / Reset to Defaults | Persist or restore rules | No complete domain | B | Explicit transactional save/invalidation/audit; Reset fills form, never writes before Save | F07/F10 | SCH + Central mutation | Yes |

## CA-026 — Category Translation Workspace (19.15)

| Visual element | Prototype behavior | Current backend/domain support | Class | Final intended behavior | Backend dependency | Permission | Phase 19? |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Item/scope table: Category Name/Description, Display/Dimensions/Ports Sections, Attributes | Aggregate separate localized entities | Separate Category/Section/Attribute editors/models exist | B | One workspace over typed entities, Category-scoped assignments + global ownership markers; no duplicate rows by Category | F11 aggregate query | TR | Yes |
| Option labels (implicit wider scope) | Translate choices | AttributeOptionTranslation exists | A | Include global options for assigned definitions, deduplicate by owner; shared impact visible | Option translation query | TR | Yes |
| Facet labels / Comparison text / SEO text scopes | Translate special presentation copy | No full typed overrides/template translations | B | Include approved owner-specific facet/comparison overrides and SEO template strings; existing Category static SEO stays Category scope | 19.12–19.14 typed extension | TR | Yes |
| Comparison Headline / SEO Intro rows | New generic localized content fields | No canonical config field/consumer | D | Omit invented fields; translate real comparison labels/template text | Separate content requirement | TR | — |
| Total Locales / Completed / Needs Review / Missing / Outdated | KPI counts/statuses | Active Locales and shared row statuses exist | C | Derive counts in declared scope; use actual Missing/HumanReviewed/Approved/Outdated statuses, historical MachineTranslated retained; no Active/Draft translation enum | Shared summary/F11 | TR | Yes |
| Coverage bars/donut/status graph/review queue | Field/Locale % and reporting | Row completeness/report services exist; no new per-field authoritative denominator | C | Bounded actual row counts with declared active-Locale/entity denominator; no 96% fabricated field score or new report route | Shared translation reports | TR | Yes, adapted summary |
| Search items / scopes/status/filter / paging / quick locales | Navigate tasks | Separate queries/status filters and active Locale model | B | Explicit Source→Target, scoped server search/paging; no row creation on selection; RTL/LTR per Locale | F11 query/Brand pattern | TR | Yes |
| Source Text / Translated Text / selected Locale / quick switch | Edit reference and target copy | Generic query read-only; Brand mature Source selection | B | Read-only Source reference with provenance; copy client-only; target fields tailored to actual entity | CA-015 pattern | TR | Yes |
| Short Label | Arbitrarily available for every item | AttributeTranslation.short_label only | C | Show only for Attribute scope; not Section/Category option invention | Typed field schema | TR | Yes, scoped |
| Slug (Translate) | Localized route identity | None in Category/Section translations; Site local slug separate | D | Omit; no localized canonical routing contract | Routing decision deferred | TR | — |
| SEO Title / Meta Description while Editing Display Section | Generic fields on any scope | SEO fields belong CategoryTranslation, not Section | D | Entity-specific fields; Section name/description, Attribute label/short/help, Option label/description, real SEO target templates | F11 typed forms | TR | Adapted forms |
| Notes for Translators | Stores arbitrary notes | No scoped contract | B | Defer; no metadata column merely from mockup | Future translation tooling | TR | — |
| Machine Suggestion / 98% / Apply | Provider-generated suggestion | No provider/domain contract in this flow | B | Future work; no fake suggestion, confidence, AI mutation or automatic approval | Provider decision deferred | TR | — |
| Save Draft / Save Translation / Approve / Mark Outdated | Multiple status-bearing writes | Old saves accept status; Brand explicit workflow exists | B | One Save as HumanReviewed; explicit current-source Approve; explicit Mark Outdated; clear approval on change; transaction/locks/no-op | Shared Brand-grade actions + audit | TR + Central mutation | Yes |
| Preview / View in Store | Shows effective localized output | TranslationResolver/projections exist, no aggregate preview | B | Read-only scoped preview; Store link only if authorized real Site/Locale route exists; no publish claim | Projection resolver + Site access | TR + Site access | Yes, constrained |
| Export / Locale Actions / view all locales/reports | Broad translation admin tools | Snapshot translation export/shared reports exist; no scoped toolbar contract | C | Use only existing authorized destinations; defer new workspace export/bulk/locale management | Shared reports/export contracts | TR | Existing links only |
| Recent Translation Activity / Updated | Actor/time feed | Shared audit mature for Brand only | B | Real bounded owning-entity events aggregated through assignments; no copied global history or fictional legacy actors | Audit registry/query | TR | Yes |

## Visual/product acceptance boundary

Future implementation retains each prototype's header → operational context → editor/table → preview hierarchy using the existing design system. CA-019 master/detail and CA-023…CA-025 editor/preview rails collapse into readable stacked surfaces on tablet/mobile; table actions and opaque codes must not overflow. CA-026 uses CA-015 source/target field pairing with explicit Locale direction. Desktop density must remain useful after unsupported columns/cards are removed, rather than filling gaps with invented data.

Each screen MR must define deterministic fixtures, authorized/denied actions, real empty/error/stale states, desktop/tablet/mobile captures and semantic/manual review against these exact source PNGs. Only then can implemented-screen baselines be created through the normal visual approval process. Phase 19.16 converges the section; 19.17 accepts it. Phase 19.0 closes discovery only.
