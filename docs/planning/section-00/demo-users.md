# Foundation Demo Users

`FoundationDemoUsersSeeder` owns eight deterministic, non-sensitive personas.
It may run only in `local` or `testing`; production execution fails before any
persona is written. Every account uses the local-only password
`cataloghub-foundation-demo`.

| Persona | Email | Global role | Active site memberships |
| --- | --- | --- | --- |
| Super Admin | `super-admin@demo.cataloghub.test` | `super_admin` | Tech Germany and Monitors Germany (`site_admin`) |
| Central Admin | `central-admin@demo.cataloghub.test` | `central_admin` | none |
| Catalog Editor | `catalog-editor@demo.cataloghub.test` | `catalog_editor` | none |
| Site Admin | `site-admin@demo.cataloghub.test` | `site_admin` | Tech Germany and Monitors Germany (`site_admin`) |
| Translator | `translator@demo.cataloghub.test` | `translator` | Tech Germany (`translator`) |
| Moderator | `moderator@demo.cataloghub.test` | `moderator` | Monitors Germany (`moderator`) |
| No Access | `no-access@demo.cataloghub.test` | `site_admin` | none; negative access fixture |
| Disabled | `disabled@demo.cataloghub.test` | `central_admin` | none; `disabled_at` is fixed |

The Site Admin persona is the canonical multi-site browser fixture. The Central
Admin persona intentionally has no site membership so cross-context denial can
be tested without creating another account. Re-running the seeder replaces only
these fixture users' memberships and produces the same graph.
