# Foundation Fresh Install

The Section Zero acceptance fixture is installed with one fail-fast command:

```bash
composer bootstrap:foundation
```

Run it only from a disposable local or test checkout after configuring the
database in `.env`. If `.env` does not exist, the command copies `.env.example`;
review its database target before continuing. Composer dependencies must already
be installed so the Artisan guard can run. Node dependencies are installed from
`package-lock.json` by the command.

The command refuses every environment except `local` and `testing` before it
touches the database. It then generates the local application key, recreates the
database using `FoundationDemoSeeder`, recreates the public storage link, runs
`npm ci`, creates the production frontend build, verifies the fixture graph, and
smoke-checks the admin route inventory. Any failed step stops the command.

Expected fixture evidence:

- three foundation sites: two active and one archived;
- eight local-only personas and six active memberships;
- two public layouts with deterministic locales, hosts, and themes;
- zero brands, products, site products, categories, or home blocks.

The command is intentionally destructive to its configured non-production
database. It is not a deployment or upgrade command and never updates visual
baselines.
