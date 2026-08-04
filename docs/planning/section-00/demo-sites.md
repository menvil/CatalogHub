# Phase 0.3 Foundation Sites

`SiteFoundationSeeder` owns three deterministic runtime-context fixtures. It is idempotent and is included in `DatabaseSeeder`.

| Site | Code | Primary host | Status | Locales | Currency | Timezone |
| --- | --- | --- | --- | --- | --- | --- |
| Tech Germany | `tech-germany` | `tech-germany.test` | active | `de-DE` default, `en-DE` enabled | EUR | Europe/Berlin |
| Monitors Germany | `monitors-germany` | `monitors-germany.test` | active | `de-DE` default, `en-DE` enabled | EUR | Europe/Berlin |
| Archived Germany | `archived-germany` | `archived-germany.test` | archived | `de-DE` default, `en-DE` enabled | EUR | Europe/Berlin |

The seeder creates no categories, products, projections, content, or theme data. Existing `Database\Seeders\Demo` classes remain the separate product-bearing demo dataset.

## Resolution contract

- `site_domains.host` is the source for host resolution. The legacy `sites.domain` column is retained and synchronized for compatibility with existing URL generation.
- Hosts are stored lowercase without scheme, port, path, or trailing dot. Unknown and inactive hosts never fall back to another site.
- Public resolution accepts active primary and alias domains. Preview domains and non-public statuses require explicit administrative resolution.
- Requested locales resolve only when enabled; otherwise the enabled site default is used. Currency and timezone are site-level runtime values.
- `ResolveSiteRuntimeContext` binds the immutable context for Public and Site Admin requests. Central Admin does not require it.
- Locale, timezone, request attributes, and resolved container instances are restored or cleared after every request, including long-running worker lifecycles.
