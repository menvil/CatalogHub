---
screen_id: Z-005
context: public
purpose: Provide the public shell for a multi-category site.
roles: public_visitor
route: /{locale}
viewports: desktop=1280x900;mobile=360x800
fixture: public-multi-shell-v1
regions: header;category-navigation;locale-selector;content-grid;footer;seo-meta
actions: change-locale;follow-category-link
states: default;mobile;enabled-locales
permissions: publicly-available-site
responsive: Header navigation and content grid adapt without administrative controls.
out_of_scope: catalog-data;search;theme-editor
reference_version: v1
---

# Z-005 — Public multi-category shell

## Purpose

The multi-category shell is the public presentation boundary for sites that expose more than one catalogue category. It provides semantic header, category-navigation, search, locale, content-grid, footer, SEO, and system-page integration points without implementing catalogue data.

## Runtime contract

- Theme ID: `cataloghub-multi`.
- Layout marker: `data-public-layout="multi-category"`.
- Theme IDs and Blade views come only from the typed whitelist in `config/public-themes.php`.
- Canonical and `hreflang` URLs use the active primary domain and enabled site locales.
- Public rendering loads only the public Vite entry point and has no Central or Site Admin dependency.

## Responsive acceptance

The approved desktop viewport is 1280 × 900 and the approved mobile viewport is 360 × 800. The deterministic `/dev/public-shell` fixture contains no catalog records or random data. Reference images are reviewed manually and are never updated by a test.
