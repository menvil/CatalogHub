# P00-002 — Application Entry Points And Panels

Snapshot date: 2026-08-04  
Inventory command: `php artisan route:list --json`  
Observed totals: 148 routes in local/testing, 146 in production (the two `/dev/*` routes are excluded).

## Route ownership

| Owner / source | Prefix or pattern | Local count | Authentication and middleware | Rendering |
| --- | --- | ---: | --- | --- |
| Public application (`routes/web.php`) | `/`, `/health`, `/offers/{offer}/go`, `/{locale}/…` | 10 | Laravel `web`; search also uses `throttle:public-search`; no auth guard | `public.layouts.app` through controllers/theme layout resolver; `/` uses `pages.home` |
| Filament admin panel | `/admin/*` | 100 | guard `web`; panel middleware plus Filament `Authenticate` for protected pages | Filament panel layout |
| Custom Central translation routes | `/admin/translations/*`, `/admin/{entity}/…/translations/*` | 15 | `auth`, `can:translations.manage` | `layouts.central-admin` |
| Custom Central media/snapshot routes | `/central/*` | 7 | `auth`; media routes also require `can:media.manage` | `layouts.central-admin` or file response |
| Development-only application routes | `/dev/ui-kit`, `/dev/admin-visual-smoke` | 2 | `web`; only local/testing | `layouts.app`, `layouts.central-admin` |
| Framework/package routes | `/filament/*`, `/livewire-*`, `/storage/*`, `/up` | 14 | package/framework-owned | package/framework-owned |

No duplicate non-empty route names or duplicate method/domain/URI signatures were observed. No route has an explicit domain constraint.

## The three product contexts as implemented

| Context | Actual entry point | Domain / prefix | Guard | Owner | Layout / navigation |
| --- | --- | --- | --- | --- | --- |
| Public Local Site | `/` and `/{locale}` | any host; localized routes use `/{locale}` and `SiteContextResolver` resolves the host | none | public controllers in `app/Http/Controllers/Public` | `resources/views/public/layouts/app.blade.php`, theme registry/layout resolver |
| Central Admin | `/admin`, login at `/admin/login` | any host; Filament prefix `admin`; custom endpoints also use `admin` and `central` | `web` session guard | single Filament panel plus custom Central controllers | Filament navigation discovery; some custom pages use `layouts.central-admin` |
| Site Admin | `/admin/sites/{record}/…` | any host; nested in the same `admin` prefix | same `web` session guard, policies and per-resource query scoping | `SiteResource` record-child pages in the `admin` panel | Filament layout; `layouts.site-admin` exists but is not used by a production route |

## Panel registry

Only `App\Providers\Filament\AdminPanelProvider` is registered in `bootstrap/providers.php`.

| Panel ID | Default | Path | Domain | Auth guard | Login | Resources/pages |
| --- | --- | --- | --- | --- | --- | --- |
| `admin` | yes | `admin` | none | `web` (default) | `/admin/login` | resources discovered from `app/Filament/Resources`; six explicit pages plus discovered resource pages |

Panel middleware: encrypted/queued cookies, session start/authentication, shared validation errors, CSRF, route bindings, Filament icon suppression and serving event. Protected panel pages add Filament `Authenticate`.

## Site Admin entry points

The current Site workspace surface comprises 13 `SiteResource` routes: index, dashboard, edit, products, brand visibility, local overrides, local SEO, themes, homepage blocks, pricing preview, products-without-offers, offer coverage and cheapest-products. They all live below `/admin/sites/{record}/…` except the sites index.

## Login and home outcomes

| Request | Current outcome |
| --- | --- |
| `GET /` | `200`, `pages.home` |
| `GET /admin` as guest | `302` to `/admin/login` |
| `GET /admin/login` | `200`, Filament login |
| `GET /{locale}` without a resolvable seeded host/site | `404` by site-context resolution |

## Collisions, mixed ownership and orphan surfaces

- No mechanical route-name or HTTP-signature collision exists.
- Central Admin ownership is split across Filament `/admin/*`, custom `/admin/*` translation controllers and custom `/central/*` controllers. Route names use `central.*` for both custom prefixes.
- Site Admin is a logical context, not a distinct panel or persistent workspace. It shares the Central navigation and authentication boundary.
- `resources/views/layouts/site-admin.blade.php` is exercised by layout tests but is not attached to a production route. `resources/views/layouts/public.blade.php` is likewise not the active public layout; the active layout is `public.layouts.app`.
- `/dev/*` routes are intentionally local/testing-only. The unnamed `/` route and package-owned unnamed Livewire routes are not name collisions, but callers cannot address them through named-route contracts.

Verification lives in `tests/Unit/Architecture/RouteInventoryTest.php`; no route, panel, provider or layout was moved.
