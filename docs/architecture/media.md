# Media architecture

CatalogHub keeps media identity separate from its storage location and public URL. `MediaAsset` is the normalized master, `MediaVariant` is a derived delivery representation, and `MediaAssignment` attaches an asset to a generic entity/role/context. URLs are generated at read time by `MediaUrlGenerator`; no URL is persisted.

New user raster uploads enter only through `MediaService`: `UploadedFile → ImageInput → ImageIngestor → NormalizedImage → MediaStorage → MediaAsset`. The GD ingestor reads bytes, cross-checks `getimagesizefromstring` and fileinfo, limits input to 20 MiB, 8000px per dimension and 16,000,000 pixels before decode, then fully decodes and re-encodes it. JPEG orientation is normalized and re-encoding strips EXIF/GPS/XMP metadata. JPEG, PNG and WebP are accepted; GIF, SVG, AVIF and other formats are rejected for new uploads. SVG/vector ingest needs its own sanitizer/security pipeline and is deferred.

The normalized bytes determine MIME, extension, checksum and deduplication. Masters retain their detected format; transparent PNG/WebP input keeps alpha. `MediaStorage` owns disk I/O and only accepts normalized images for untrusted writes. Paths use generated UUIDs and canonical extensions. A failed database insert after a write triggers best-effort cleanup while preserving the database exception.

Variants are typed specifications from `MediaVariantSpecificationRegistry`, processed by `ImageVariantProcessor`, and orchestrated by `MediaVariantGenerator`. Queue jobs merely delegate. Variants never upscale, use deterministic paths and specification hashes, and skip when the ready file/hash already matches. Existing product variants remain registered; `brand_logo_128`, `brand_logo_256` and `brand_logo_512` are WebP contain/no-upscale variants.

Brand Logo v1 is a global primary assignment (`central_brand`, `brand_logo`, null locale/site/market). Replacing/removing a logo only changes the assignment; assets and files are retained. Future lifecycle work may release, retain for a grace period, then purge orphaned assets.
