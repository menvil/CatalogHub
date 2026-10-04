# Screenshot capture rules

Names use `<screen-id>__state__viewport.png`, with lower-case ASCII identifiers only (for example, `z-007__central-500__1280x900.png` or `ca-011__default__1440x1000.png`). `Tests\\Support\\ScreenshotNaming` validates the screen ID, state and dimensions before a new path is created. The manifest validator enforces the same path, a `WIDTHxHEIGHT` viewport, and a versioned deterministic fixture name. References live in `tests/Visual/baselines`; transient captures and diff artifacts live outside Git under `storage/visual-artifacts`.

Playwright capture uses the pinned Linux/amd64 browser image (including its installed fonts), whose immutable identity is declared in `tools/visual/environment.json`, DPR 1, fixed viewport, bundled local assets, hidden scrollbars, and deterministic fixture data. Animations and font loading must settle before capture. Product fonts retain their existing design and system fallbacks; capture and comparison use the same renderer rather than changing typography to match another OS. See [visual tests](../testing/visual-tests.md) for the Docker-backed commands and the separate legacy PHP reference checks. A viewport is written as `WIDTHxHEIGHT`; duplicate screen/state/viewport tuples are forbidden.

PNG references and `.sha256` files are committed. Current captures and diffs are CI artifacts, never committed automatically.
