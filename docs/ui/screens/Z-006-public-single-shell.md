---
screen_id: Z-006
context: public
purpose: Provide the distinct public shell for a single-category site.
roles: public_visitor
route: /{locale}
viewports: desktop=1280x900;mobile=360x800
fixture: public-single-shell-v1
regions: header;hero;filter-slot;locale-selector;content-region;footer;seo-meta
actions: change-locale;open-filter-slot
states: default;mobile;enabled-locales
permissions: publicly-available-site
responsive: The focused hero and filter slot remain usable at the mobile viewport.
out_of_scope: catalog-data;search-implementation;theme-editor
reference_version: v1
---

# Z-006 — Public single-category shell

## Purpose

The single-category shell is a structurally distinct public boundary for focused catalogue sites. It prioritizes a focused hero and filter/search integration point while reusing the public header, footer, locale selector, SEO contract, tokens, and safe system pages.

## Runtime contract

- Theme ID: `cataloghub-single`.
- Layout marker: `data-public-layout="single-category"`.
- A bare active site host redirects to `/{defaultLocale}`; unknown and archived
  bare hosts return the safe public `404` boundary.
- The runtime resolver maps the site mode or a whitelisted theme ID to this layout; database values can never select an executable Blade path.
- Current site and locale remain explicit at desktop and mobile sizes.
- Public rendering has no administrative assets, navigation, or layout classes.

## Responsive acceptance

The approved desktop viewport is 1280 × 900 and the approved mobile viewport is 360 × 800. Browser acceptance verifies layout markers, semantic landmarks, enabled locale links, admin isolation, and the absence of runtime errors before comparing an explicitly approved reference.
