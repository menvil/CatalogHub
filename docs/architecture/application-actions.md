# Application actions

Presentation code validates and translates HTTP input; it does not own persistence orchestration. A mutation that combines authorization, locks, a database write, and audit recording is implemented as an Action.

`UpsertSiteMembershipAction` is the Section 0 reference boundary:

1. it authorizes the mutation with the target `Site`;
2. it opens one transaction and locks the parent site before membership lookup;
3. it changes one membership and records the whitelisted audit snapshot in that transaction;
4. it returns the persisted model to the caller.

Simple single-model CRUD need not gain an Action merely to follow a pattern. Controllers, pages, Blade views, policies, and query objects must not open transactions or perform mutation orchestration. Actions do not read request globals; their typed arguments make actor, subject, site, and requested state explicit.
