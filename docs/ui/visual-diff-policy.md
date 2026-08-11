# Visual diff review policy

Baselines are approved product artifacts. Tests only compare them; no test, seeder, or CI job may overwrite them.

1. A contributor captures a new reference from the documented fixture and fixed viewport.
2. The manifest checksum and screen contract are updated in the same review.
3. A reviewer inspects the PNG/diff artifact and explicitly approves the change.
4. Threshold changes require a written reason. Font antialiasing differences are handled by the documented per-suite threshold, never by silently accepting a screenshot.

`SystemErrorVisualTest` permits a mean channel difference of `0.07`: its deliberate inline system-font fallback keeps 500 pages usable while assets are unavailable, and has a larger macOS/Linux rendering variance than bundled application screens.

CI uploads mismatch captures through `VISUAL_ARTIFACT_DIR`. A missing reference, checksum mismatch, or a failed comparison blocks acceptance.

`php scripts/check-visual-baseline-change.php <base-revision>` blocks a baseline change unless a reviewer has explicitly set `VISUAL_BASELINE_REVIEWED=1`. This guard does not generate or alter a reference.
