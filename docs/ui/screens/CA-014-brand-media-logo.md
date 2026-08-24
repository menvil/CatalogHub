# CA-014 — Brand Media / Logo

CA-014 manages one global primary Brand logo. It provides no-logo, logo-ready, logo-processing/fallback and upload-error states. A Brand manager can upload or replace a JPEG, PNG or WebP logo, or detach it with confirmation. Removal retains the asset in Media Library. The responsive preview uses `object-contain` on a neutral checkerboard background and metadata wraps on mobile.

Routes: `GET central.brands.media`, `POST central.brands.media.logo.store`, `DELETE central.brands.media.logo.destroy`.
