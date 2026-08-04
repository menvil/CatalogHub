# Future Optional Parallel Workflow for CatalogHub v2

| Field | Value |
| --- | --- |
| Status | Inactive future option |
| Current delivery mode | Serial-first |
| Current concurrency | One agent; one active work package/MR |
| Activation authority | CatalogHub Product Owner and Engineering Owner |
| Governing roadmap | `roadmap-v2-screen-driven.md` |

## Current rule

Parallel execution is postponed. No current roadmap phase, estimate, dependency,
seed contract, acceptance gate or MR may assume that multiple agents are
available. Work proceeds in roadmap order, with one independently mergeable
work package/MR active at a time.

Admin Shell, Design System and Workspace/Site Switcher stabilize first in Phase
01. Later screen-driven phases then execute serially. A dependency is resolved
and merged before the dependent phase starts; it is not worked around on another
branch.

This document does not authorize fan-out, parallel UI work, concurrent schema
changes or concurrent edits to shared components.

## Serial work-package contract

Before each current work package starts, its MR description records:

- roadmap phase and exact registry screen IDs;
- pinned green base commit;
- owned domain behavior and explicit schema/model changes;
- prerequisite MR, if any, already merged;
- deterministic seed scenario IDs;
- actors, permissions and immutable Site context cases;
- primary actions and functional tests;
- required visual artifacts and current reproducibility status;
- dashboard re-acceptance requirement;
- explicit non-goals, including shared UI and broad refactors.

Only after the current MR is accepted, merged and green may the next work package
start. Findings outside scope go to the next planning decision; they do not
expand the active MR.

## Shared-change rule in serial mode

Shared admin layout, design tokens, workspace state and Site-context
infrastructure belong to Phase 01. A later phase that genuinely needs a shared
change pauses before implementation and creates one small serial prerequisite
MR. That MR contains the shared contract change and regression evidence but no
domain-specific screen implementation. The screen MR starts only after the
prerequisite merges.

The same rule applies to global permission configuration, common test/visual
harnesses, dependency manifests and cross-module route conventions. Serial mode
reduces merge conflict risk, but it does not authorize opportunistic shared
refactors.

## Conditions required before future parallelization

Parallel delivery may be proposed only after all of these are demonstrated on
merged code:

1. the Admin Shell is stable across accepted Central and Site screens;
2. the design system and responsive component APIs are stable and versioned;
3. immutable Site context, queued-job authorization, cache/query scoping and
   two-tab isolation are implemented and passing;
4. route, permission, seed and read-model contracts have explicit module
   boundaries;
5. shared hotspots and their single-owner change process are documented;
6. at least the first Central and Site screen-driven phases have passed
   functional and semantic/manual visual acceptance;
7. CI can run package and integrated gates reliably from deterministic seeds.

Meeting these conditions does not switch modes automatically. Product and
Engineering must approve a roadmap/contract change that names an integration
owner and the exact concurrent work packages.

## Future-only dependency model

If parallelization is later activated, every dependency must be classified:

- **HARD:** the depended-on schema, behavior or screen contract must merge before
  the consumer starts; packages with a HARD dependency never run in parallel.
- **CONTRACT:** both packages may consume an already approved, versioned
  interface without editing its owner.
- **INTEGRATION:** packages are independent to implement but require a named
  serial integration/re-acceptance gate afterward.

Future work packages may start concurrently only when no HARD dependency exists
between them and their exact changed paths do not overlap.

## Non-active future example

A future proposal could run independent Central module MRs after the shell and
module boundaries are stable, then integrate them serially. Public Local Site
must still wait for every data-producing admin phase and contract it consumes.
In particular, Public content or poll rendering cannot run concurrently with the
Site Content/Polls work that defines or produces that data.

This paragraph is an illustration, not a wave plan, schedule or current
authorization. There are no active Wave 1/2/5 assignments.

## Activation checklist

A future parallel-mode amendment must add:

- named integration owner and concurrency limit;
- exact work packages, screen IDs and dependency types;
- non-overlapping owned paths and explicit shared hotspots;
- route/permission/seed/read-model contract versions;
- separate small MR rule for shared components;
- rebase and integration order;
- per-package and full-suite gates;
- dashboard and cross-site re-acceptance plan;
- rollback/forward-fix ownership.

Until that amendment is approved, the serial roadmap is the only execution plan.
