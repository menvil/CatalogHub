# Responsive Breakpoints And Density

These exact widths define both CSS breakpoints and deterministic visual fixtures.

| Class | Viewport | Density | Foundation behavior |
| --- | ---: | --- | --- |
| mobile | 360 × 800px | comfortable | Stack controls and collapse navigation. |
| tablet | 768 × 1024px | comfortable | Use two-column compositions where safe. |
| desktop | 1280 × 900px | compact | Show persistent sidebar and dense tables. |
| wide | 1440 × 1200px | compact | Cap content width and preserve readable lines. |

## Component behavior

- Navigation collapses below desktop and must remain keyboard accessible.
- Tables preserve their semantic columns and use horizontal scrolling when they cannot reflow safely.
- Page actions wrap before they overlap the title or breadcrumbs.
- Cards may move from one to two or four columns only at the fixed boundaries.
- Modals are bounded by the viewport and the semantic modal width.
- Compact desktop density may reduce whitespace, never hit target size or readable type.

These rules describe foundation primitives only; future business screens define their own reviewed composition without changing the breakpoint contract implicitly.
