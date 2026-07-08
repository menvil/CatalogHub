# Site Admin Information Architecture

Task: P00-003 - Map Site Admin Information Architecture  
Area: UX / IA  
Status: Phase 0 discovery artifact

## Purpose

Site Admin is the administration area for a local portal. It controls how
approved Central Catalog data is presented in a specific market, locale, domain,
theme, and content context.

This is an information architecture document only. It does not define routes,
database structure, Laravel policies, Filament resources, Livewire components,
or production UI.

## Primary Actors

- `site_admin`
- `content_editor`
- `moderator`
- `support_operator`
- `price_manager` for assigned site price sources when applicable

## Ownership Boundaries

Site Admin may manage:

- site identity, domain, market, locale, and currency;
- enabled categories;
- product visibility and local publishing state;
- local slugs, SEO, intro text, and homepage usage;
- local media assignments;
- theme and template selection;
- homepage block configuration;
- sync status and projection rebuild requests;
- correction requests to Central;
- site price sources and manual local offers;
- reviews, leads, content, and polls.

Site Admin must not directly edit:

- canonical product identity;
- canonical brand;
- canonical category;
- canonical attribute schema;
- canonical specs;
- global translations;
- central product versions;
- accepted Central corrections.

Canonical changes must go through Site to Central correction requests.

## Sitemap

| Section | Purpose | Main screens | Primary actions | Important states |
| --- | --- | --- | --- | --- |
| Site Dashboard | Monitor local portal health. | SA-001 Site Dashboard | Review stale products, inspect leads, open sync issues, check price source status. | Healthy, stale projections, failed price source, pending moderation. |
| Site Settings | Manage identity and site-level configuration. | Settings overview, domain, market, locale, currency. | Update display name, domain, default locale, default market, currency. | Draft, active, invalid domain, unsupported locale. |
| Markets / Locales | Configure enabled local markets and language coverage. | Market list, locale list, coverage matrix. | Enable market, enable locale, set fallback locale. | Enabled, disabled, missing translation, fallback active. |
| Enabled Categories | Select which Central categories are available on the site. | Category scope list, category detail. | Enable category, disable category, inspect schema compatibility. | Enabled, disabled, incompatible, missing content. |
| Product Visibility | Control which eligible products appear publicly. | SA-013 Site Products List, visibility filters. | Show, hide, exclude, mark draft, bulk update visibility. | Visible, hidden, excluded, draft, stale, no offers. |
| Local Overrides | Adapt presentation without changing Central truth. | Override editor, projection preview. | Edit local title, slug, SEO, intro text, local media assignment. | Clean, unsaved, stale projection, conflicts with canonical update. |
| Theme / Templates | Choose theme and templates for the portal. | SA-022 Theme Selection, SA-023 Theme Compatibility Check. | Select theme, preview compatibility, apply template. | Compatible, warning, incompatible, draft theme. |
| Homepage Blocks | Configure local homepage content. | SA-025 Homepage Blocks Editor. | Add block, reorder block, select products/categories/articles. | Draft, published, missing referenced item, invalid block. |
| Sync | Inspect Central to Site projection status. | SA-029 Sync Dashboard, sync log detail. | Request rebuild, retry failed job, inspect stale products. | Up to date, stale, queued, failed, conflict. |
| Correction Requests | Propose reusable changes back to Central. | Correction list, correction detail. | Create request, attach evidence, submit, track review. | Draft, submitted, under review, approved, rejected, applied. |
| Price Sources / Offers | Manage site-specific offer inputs. | SA-039 Site Price Sources, offers list. | Add source, disable source, review offer status, add manual offer. | Healthy, delayed, failing, disabled, expired offers. |
| Reviews | Moderate public reviews for the site. | SA-047 Reviews List, review detail. | Approve, reject, flag, respond, hide. | Pending, approved, rejected, flagged. |
| Leads | Process public lead submissions. | SA-051 Leads List, lead detail. | Assign, mark contacted, close, export. | New, assigned, contacted, closed, spam. |
| Content | Manage local articles, guides, FAQ, and landing copy. | Article list, guide editor, FAQ editor. | Create, edit, publish, unpublish, localize. | Draft, scheduled, published, archived. |
| Polls | Manage simple public engagement polls. | Poll list, poll detail, results. | Create poll, publish, close, inspect results. | Draft, active, closed, invalid options. |

## Navigation Model

Primary navigation:

- Dashboard
- Products
- Overrides
- Sync
- Price Sources
- Content
- Reviews
- Leads
- Theme
- Settings

Site Admin navigation should not expose Central-only editors such as Attribute
Schema Builder, Central Product Detail canonical spec editor, or global
translation approval.

## Key Workflows Supported

- Enable a Central category for a site.
- Make an eligible product visible or hidden.
- Add a local SEO override and rebuild projection.
- Submit a correction request to Central with evidence.
- Moderate reviews and process leads.
- Check whether theme/templates support enabled categories and blocks.
- Retry projection rebuild after a Central update.

## Future Phase Dependencies

- Phase 10: Sites, Markets, Portal Admin UX.
- Phase 11: Template / Theme System v1.
- Phase 13: Public Demo Site v1.
- Phase 3, Phase 4, and Phase 9 provide Central Catalog, schema, and import data
  consumed by Site Admin.

## Phase 0 Boundaries

Included:

- Site Admin sections;
- local ownership boundaries;
- Central-only prohibited actions;
- sync and correction request areas;
- important states.

Excluded:

- Laravel installation;
- migrations;
- models;
- policies;
- Filament resources;
- Livewire components;
- production frontend.
