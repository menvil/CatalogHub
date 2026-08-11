# CI Security And Retention

CatalogHub CI treats pull-request code as untrusted. The quality workflow uses
`pull_request`, never `pull_request_target`, has repository contents read-only by
default, and does not reference production or deployment secrets. Database
credentials in the workflow are disposable service-container fixture values.

Reusable actions are pinned to full commit SHAs. Composer caches contain only
downloaded package archives, never the executable `vendor/` tree. Composer and
npm cache identities are derived from their lockfiles, have no broad restore
prefix, and can be disabled for a manual clean acceptance run with
`use_dependency_cache: false`.

Build artifacts are retained for one day. Failure diagnostics and Laravel logs
are retained for five days, architecture reports for fourteen days, and coverage
reports for thirty days. Upload paths are limited to build output, test reports,
screenshots, diffs, traces, and application logs; environment files are excluded.

The separate PR automation workflow has no checkout step, runs no pull-request
code, receives no secrets, and only manages release/hotfix labels for internal
branches. Deployment and release automation are outside this phase.
