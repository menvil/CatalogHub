# CA-019 Category Schema Builder Wireframe

Task: P00-016 - Create Category Schema Builder Wireframe  
Area: UX / Wireframe  
Status: Phase 0 low-fidelity artifact

## Purpose

Show how a Central Admin user manages category schema sections and attributes.

This artifact is not backend implementation, database schema, validation code,
Filament UI, Livewire UI, or production design.

## Primary Actor

`data_manager`

## Low-Fidelity Layout

```txt
+--------------------------------------------------------------------------------+
| Category: Monitors                         Status: Draft / Validating / Approved |
| Actions: Save Draft | Validate Schema | Approve Schema | Run Test Import        |
+------------------------+------------------------------+------------------------+
| Sections               | Attributes in selected section | Attribute detail       |
| + Display              | Display                        | Name: Refresh rate     |
| + Dimensions & Weight  | - Screen size                  | Code: refresh_rate     |
| + Ports                | - Resolution                   | Data type: number      |
| + Energy               | - Panel type                   | Unit: Hz               |
|                        | - Refresh rate [selected]      |                        |
| Section actions:       | - Response time                | Flags:                 |
| Add / Rename / Reorder |                              | [x] required           |
|                        | Attribute actions:             | [x] filterable         |
|                        | Add / Duplicate / Remove       | [x] sortable           |
|                        | Reorder                        | [x] comparable         |
|                        |                              |                        |
|                        |                              | Enum/options editor    |
|                        |                              | Shown when enum type   |
+------------------------+------------------------------+------------------------+
| Schema preview                                                                  |
| - Public specs grouping                                                          |
| - Facet preview                                                                  |
| - Compare table preview                                                          |
| - Import mapping hints                                                           |
+--------------------------------------------------------------------------------+
| Validation warnings                                                              |
| - Missing unit for numeric attribute                                             |
| - Required attribute has poor import coverage                                    |
| - Filterable enum has too many unknown values                                    |
| - Template compatibility warning                                                 |
+--------------------------------------------------------------------------------+
```

## Required Sections

- Display.
- Dimensions & Weight.
- Ports.
- Energy.

## Attribute Detail Fields

- name;
- code;
- description;
- data type selector;
- unit selector;
- required flag;
- filterable flag;
- sortable flag;
- comparable flag;
- enum options editor when data type is enum;
- normalization notes;
- mapping hints.

## Primary Actions

- Add, rename, reorder, or remove section.
- Add, duplicate, reorder, or remove attribute.
- Select data type.
- Select unit.
- Configure flags.
- Manage enum options.
- Preview schema.
- Validate schema.
- Approve schema after warnings are resolved or accepted.

## States

- Empty: category has no sections.
- Draft: schema can be edited.
- Validating: warnings and coverage checks are visible.
- Approved: destructive changes require future versioning/compatibility rules.
- Error: invalid unit, duplicate code, missing required field, incompatible type.

## Scope Boundaries

- This screen belongs to Central Admin only.
- Site Admin cannot directly edit category schema.
- Test import is a workflow action reference, not an import implementation.
- No backend schema builder is created in Phase 0.
