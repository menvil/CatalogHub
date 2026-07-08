# CA-038 Normalized Draft Review Wireframe

Task: P00-018 - Create Import Review Wireframe  
Area: UX / Wireframe  
Status: Phase 0 low-fidelity artifact

## Purpose

Show the review screen where an import operator compares raw source data with a
normalized draft before approval into Central Catalog.

This is not import implementation, parser code, queue code, media downloader,
Filament UI, Livewire UI, or production design.

## Primary Actor

`import_operator`

## Low-Fidelity Layout

```txt
+--------------------------------------------------------------------------------+
| Batch: Monitors import #42     Source: serialized PHP files     Status: Review   |
| Draft 18 of 240                Confidence: 82%                  Actions:         |
| [Approve Draft] [Reject Draft] [Send to Mapping Rules] [Mark Duplicate]          |
+--------------------------------------------------------------------------------+
| Raw product panel                         | Normalized product panel             |
| Source row / artifact reference           | Canonical draft preview              |
| - title: Dell 27 4K monitor               | - title: Dell UltraSharp U2723QE      |
| - size: 27\"                              | - screen size: 27 inch                |
| - res: 3840 x 2160                        | - resolution: 3840x2160               |
| - hz: 60                                  | - refresh rate: 60 Hz                 |
| - ports: HDMI, DP                         | - HDMI ports: 1                       |
|                                           | - DisplayPort ports: 1                |
+-------------------------------------------+--------------------------------------+
| Mapped attributes and diff                                                       |
| Raw value              -> Canonical value        Confidence    Issue             |
| 27\"                   -> 27 inch               high          ok                |
| 3840 x 2160            -> 3840x2160             high          ok                |
| HDMI, DP               -> HDMI=1, DP=1          medium        verify            |
| unknown_panel          -> unmapped              low           mapping needed     |
+--------------------------------------------------------------------------------+
| Unmapped fields                 | Normalization errors                          |
| - marketing_badge               | - panel type enum not recognized              |
| - seller_note                   | - power unit ambiguous                         |
+--------------------------------------------------------------------------------+
| Duplicate candidates                                                             |
| Product                         Match reason              Score   Action         |
| Dell U2723QE existing           model + resolution        91%     Compare/Merge  |
+--------------------------------------------------------------------------------+
| Media download status                                                            |
| URL                              Status          Note                            |
| source/image1.jpg                downloaded      variant pending                 |
| source/image2.jpg                failed          404                             |
+--------------------------------------------------------------------------------+
```

## Primary Actions

- Approve normalized draft.
- Reject normalized draft.
- Send issue to mapping rules.
- Mark duplicate or merge candidate.
- Inspect raw artifact.
- Open media issue.

## Required Review Signals

- import batch context;
- raw product panel;
- normalized product panel;
- mapped attributes;
- raw-to-canonical diff;
- unmapped fields;
- normalization errors;
- duplicate candidates;
- media download status;
- confidence indicators.

## States

- Ready for approval: no blocking errors.
- Needs mapping: required fields unmapped.
- Duplicate risk: candidate score above review threshold.
- Media warning: media failed but may not block approval depending on category.
- Rejected: draft is not published to Central Catalog.
- Approved: draft becomes Central product or version update in future workflow.

## Scope Boundaries

- Approval publishes to Central Catalog review path only, not directly to public
  site products.
- Projection rebuild happens after Central publish through sync workflow.
- Mapping changes belong to CA-039 Mapping Rules Editor.
- No import parser, media downloader, or database implementation is created in
  Phase 0.
