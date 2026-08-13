# Screenshot capture rules

Names use `<screen-id>__state__viewport.png`, with lower-case ASCII identifiers only (for example, `z-007__central-500__1280x900.png`). `Tests\\Support\\ScreenshotNaming` validates the screen ID, state and dimensions before a new path is created. The manifest validator enforces the same path, a `WIDTHxHEIGHT` viewport, and a versioned deterministic fixture name. References live in `tests/Visual/baselines`; transient captures and diff artifacts live outside Git under `storage/visual-artifacts`.

Capture uses Chrome, DPR 1, fixed viewport, bundled local assets, fixed fonts, hidden scrollbars, and deterministic fixture data. Animations must settle before capture. A viewport is written as `WIDTHxHEIGHT`; duplicate screen/state/viewport tuples are forbidden.

PNG references and `.sha256` files are committed. Current captures and diffs are CI artifacts, never committed automatically.
