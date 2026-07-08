# PUB-005 Product Detail Page Wireframe

Task: P00-020 - Create Public Product Page Wireframe  
Area: UX / Wireframe  
Status: Phase 0 low-fidelity artifact

## Purpose

Show how a product projection becomes a public product detail page with media,
offers, specs, reviews, content links, comparison, leads, and similar products.

This artifact is not production frontend, Blade, CSS, theme implementation,
controller code, API design, or high-fidelity visual design.

## Primary User

`public_visitor`, with optional actions by `lead_submitter` and `review_author`.

## Projection Inputs

- product title;
- brand/category breadcrumbs;
- media gallery;
- rating and review count;
- best offer and offer list;
- key specs summary;
- specs grouped by category sections;
- related articles/guides;
- comparison eligibility;
- lead form configuration;
- similar products.

## Desktop Low-Fidelity Layout

```txt
+--------------------------------------------------------------------------------+
| Breadcrumbs: Home > Monitors > Dell                                             |
+--------------------------------------------------------------------------------+
| Product title: Dell UltraSharp U2723QE                                          |
| Brand: Dell                         Rating: 4.6 (128 reviews)                   |
+-------------------------------------------+------------------------------------+
| Media gallery                             | Best offer                         |
| [primary image]                           | Price / merchant / availability    |
| [thumb] [thumb] [thumb]                   | [View offer] [Compare]             |
|                                           | Lead block: Need repair/quote?     |
|                                           | [Submit lead]                      |
+-------------------------------------------+------------------------------------+
| Key specs summary                                                              |
| 27 inch | 3840x2160 | IPS | 60 Hz | USB-C | 100 W                              |
+--------------------------------------------------------------------------------+
| Offers block / offers table                                                     |
| Merchant | Price | Availability | Updated | CTA                                |
+--------------------------------------------------------------------------------+
| Specs by sections                                                               |
| Display: screen size, resolution, panel type, refresh rate                      |
| Ports: HDMI, DisplayPort, USB-C                                                 |
| Dimensions & Weight: width, height, weight                                      |
| Energy: power, energy class                                                     |
+--------------------------------------------------------------------------------+
| Reviews section                                                                 |
| Rating distribution | latest reviews | [Write review]                          |
+--------------------------------------------------------------------------------+
| Related articles / guides                                                       |
| - Best monitors for office work                                                 |
| - How to choose 4K monitor                                                      |
+--------------------------------------------------------------------------------+
| Similar products                                                                |
| [product card] [product card] [product card]                                    |
+--------------------------------------------------------------------------------+
```

## Mobile Notes

```txt
1. Breadcrumbs collapse to compact path.
2. Title, brand, rating, and primary CTA appear before long specs.
3. Gallery becomes swipeable but remains inspectable.
4. Best offer and lead CTA stay near the top.
5. Key specs render as compact rows or chips.
6. Offers table collapses into offer cards.
7. Specs sections become stacked accordions or section blocks.
8. Compare CTA remains available after key specs and near similar products.
9. Reviews, related content, and similar products stack below specs/offers.
```

## Primary CTAs

- View offer.
- Compare.
- Submit lead.
- Write review.
- Open related guide.
- Open similar product.

## States

- No offers: show lead or availability inquiry path.
- No reviews: show empty review state and write-review CTA.
- Missing media: show fallback media if site policy allows.
- Missing translation: use configured fallback or hide according to site policy.
- Hidden/excluded/draft product: page must not be public.
- Stale projection: public page uses last built projection unless future policy
  marks it unavailable.

## Scope Boundaries

- Page reads projection data, not draft Central tables.
- Offers do not introduce checkout, cart, order, payment, or delivery flows.
- Reviews go through moderation rules.
- Lead submission creates a lead, not a sale.
- No frontend implementation is created in Phase 0.
