# CA-003 Product Detail Admin Wireframe

Task: P00-017 - Create Product Detail Admin Wireframe  
Area: UX / Wireframe  
Status: Phase 0 low-fidelity artifact

## Purpose

Show a Central Admin product detail screen as a canonical data object, not as a
public storefront page.

This artifact is not a production UI, Blade template, Filament resource,
Livewire component, model, migration, or public product page.

## Primary Actor

`catalog_editor`

## Low-Fidelity Layout

```txt
+--------------------------------------------------------------------------------+
| Product: Dell UltraSharp U2723QE              Status: Approved  Version: 12      |
| Brand: Dell            Category: Monitors     Quality: Warnings                 |
| Actions: Save | Request review | View history | Mark deprecated                 |
+--------------------------------------------------------------------------------+
| Tabs: Overview | Specs | Media | Translations | Sources | Versions | Sites       |
+-------------------------------+------------------------------------------------+
| Product identity              | Data quality warnings                           |
| - canonical title             | - missing translation: bg                       |
| - model / identifiers         | - media variant missing                         |
| - brand                       | - stale site projections: 3                     |
| - category                    |                                                |
+-------------------------------+------------------------------------------------+
| Canonical specs grouped by sections                                             |
| Display                                                                        |
| - Screen size: 27 inch                                                          |
| - Resolution: 3840x2160                                                         |
| - Panel type: IPS                                                               |
| Ports                                                                          |
| - HDMI: 1                                                                       |
| - DisplayPort: 1                                                                |
| Energy                                                                         |
| - Power: 100 W                                                                  |
+--------------------------------------------------------------------------------+
| Media summary             | Translation summary        | Sources / Import history |
| - primary asset           | - en complete              | - last import batch       |
| - gallery count           | - bg missing fields        | - source URL              |
| - broken variants         | - de stale                 | - raw artifact link       |
+--------------------------------------------------------------------------------+
| Change requests / conflicts                                                     |
| - pending correction: refresh rate                                               |
| - conflict: duplicate candidate                                                  |
+--------------------------------------------------------------------------------+
| Site publication status                                                         |
| Site        Status     Projection   Offers    Translation    Last sync          |
| Tech demo   visible    stale        ok        missing bg     timestamp          |
| Monitors    hidden     current      no offers ok             timestamp          |
+--------------------------------------------------------------------------------+
```

## Panels And Tabs

- Overview: identity, quality warnings, summaries, and publication status.
- Specs: canonical attributes grouped by category sections.
- Media: assets, variants, assignments, and quality issues.
- Translations: global translations and missing/stale locale coverage.
- Sources: imports, evidence, source URLs, and raw artifact references.
- Versions: product version history and change notes.
- Sites: site eligibility, visibility, projection, offers, and stale status.

## Primary Actions

- Edit canonical identity when allowed.
- Edit canonical specs through schema-valid fields.
- Open media assignment.
- Open translation queue.
- Review source/import history.
- Open version diff.
- Open change request or conflict.

## States

- Draft: not eligible for public projection.
- Approved: eligible for sites.
- Deprecated: should not be newly published without review.
- Conflict: duplicate or correction conflict needs resolution.
- Warning: missing media, missing translations, stale projections, missing offers.

## Scope Boundaries

- Local SEO, local slug, homepage usage, reviews, and leads belong to Site Admin.
- Public page layout belongs to Public Demo Site wireframes.
- Public site must not read draft Central product.
- No code is created in Phase 0.
