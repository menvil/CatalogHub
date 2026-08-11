# Section Zero CI Acceptance

Phase 0.15 was accepted on 2026-08-11 against commit
`559a96bc265fdec45cba1e9a7f4754b64aedb7ba` using the manually dispatched
[CI run 31527822255](https://github.com/menvil/CatalogHub/actions/runs/31527822255).
The run used a fresh GitHub-hosted checkout and `use_dependency_cache: false`;
all Composer cache steps were skipped and setup-node received no npm cache mode.
PostgreSQL 18.4 and MariaDB 11.4 started as empty service containers.

## Results

| Job | Result | Duration |
|---|---|---:|
| Frontend quality | passed | 18s |
| Dependency audit | passed | 24s |
| Code style (Pint) | passed | 43s |
| Fresh database (PostgreSQL) | passed | 53s |
| Browser smoke (Playwright) | passed | 58s |
| Migrations (MariaDB) | passed | 75s |
| Tests (PHPUnit · SQLite) | passed | 122s |
| Architecture & static analysis (PHPStan) | passed | 124s |
| Visual regression (Chromium) | passed | 136s |
| Backend quality | passed | 4s |
| Summary | passed | 6s |

The successful run produced the one-day `public-build` artifact and the
fourteen-day `architecture-debt-report` artifact. Failure diagnostics were also
verified by the preceding no-cache
[run 31527429542](https://github.com/menvil/CatalogHub/actions/runs/31527429542):
its unmasked Chromium sandbox startup failure uploaded the five-day
`visual-diagnostics` artifact with ten files. The fix added the same explicit
CI sandbox argument already used by the Site and Public shell checks; no visual
reference or tolerance changed.

All gate jobs completed successfully without retries, `continue-on-error`,
baseline updates, production secrets, or cache-only success. Branch protection
was read back from GitHub with the seven documented strict required contexts,
one approving review, stale and last-push review enforcement, conversation
resolution, administrator enforcement, and force-push/deletion disabled.
