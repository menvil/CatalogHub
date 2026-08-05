# Iconography And Status Semantics

Heroicons is the single icon source for new foundation code. It is already installed through the Blade/Filament stack, so no additional icon package is permitted without a separate architecture decision.

Use `x-ui.icon` rather than inline SVG. Supported foundation sizes are `sm` (16px), `md` (20px), and `lg` (24px); `md` is the default.

| Semantic use | Heroicon | Meaning |
| --- | --- | --- |
| success | `check-circle` | Completed or healthy. |
| warning | `exclamation-triangle` | Attention required. |
| danger | `x-circle` | Failed or destructive. |
| info | `information-circle` | Informational context. |
| view | `eye` | Open read-only detail. |
| edit | `pencil-square` | Modify an existing record. |

Status meaning must not rely on color alone: pair the icon or status surface with visible text. Decorative icons use `aria-hidden`; standalone meaningful icons require a label. Emoji are not interface icons.
