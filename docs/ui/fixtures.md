# Deterministic UI fixtures

Visual fixtures are immutable presentation inputs, not demo data. Each fixture has a version marker, fixed locale `en-US`, timezone `UTC`, and a clock frozen at `2026-08-09T10:00:00Z` when it renders dates.

- Never use Faker, current time, remote images, unordered database queries, or generated avatars in a visual fixture.
- Use stable model keys and explicit ordering.
- A fixture change requires a screen-contract and visual-reference review; it does not update a baseline itself.
- `CentralShellFixture`, `SiteAdminShellFixture`, `PublicShellFixture`, and `AdminComponentGalleryFixture` are the Foundation fixture sources. New fixture pages must expose a version marker.
- `BrandListFixture` provides `brands-list-v1`: 24 explicit canonical Brands with all lifecycle states, nullable metadata, stable ordering inputs, and fixed UTC timestamps. The browser harness loads it separately after the Foundation seeder; it is never production demo data.
- `BrandFormFixture` provides `brand-form-v1`: one fixed Draft Brand (`Samsung Form Fixture`, ID `13013`) with canonical slug, website, country, and timestamps for CA-013 edit/browser/visual coverage. The browser harness loads it separately after `BrandListFixture`; it is never production demo data.
