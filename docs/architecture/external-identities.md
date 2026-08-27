# External Brand identities and provenance

`CentralBrand` is the canonical, source-independent Brand. A source-side Brand
record is represented by `App\Models\Imports\CentralBrandExternalIdentity`,
which links that Brand to the existing `App\Models\Imports\ImportSource`.
There is no second source registry and no source/external-ID shortcut column on
`central_brands`.

## Identity contract

An external ID is opaque within one ImportSource namespace. The normalizer and
both HTTP validators reject malformed UTF-8 and control characters in the
original value before trimming surrounding whitespace. This prevents leading or
trailing control bytes from being normalized into another identity. The
application then rejects blank values and otherwise preserves casing, internal
whitespace, and leading zeroes. Thus `ABC` and `abc` may coexist, while `000123`
remains `000123`.

Cross-database uniqueness is authoritative on
`(import_source_id, external_id_hash)`, where the hash is SHA-256 of the exact
normalized value. Resolver and mutation paths always compare the stored
`external_id` after a hash lookup; a matching hash with a different value raises
an integrity error instead of conflating records. Different sources may use the
same ID. A Brand may have multiple IDs in one source.

`CentralBrandExternalIdentityResolver::findIdentity()` and `findBrand()` perform
an exact database lookup only. They do not make network requests, fuzzy-match
names, create Brands, or arbitrate canonical field authority. Future importers
may use the resolver only when their source contract provides a genuine stable
external Brand ID.

## Persistence and lifecycle

`central_brand_external_identities` contains its ID, Brand FK, ImportSource FK,
external ID, internal hash, optional external record URL, and timestamps. Brand
physical deletion cascades link deletion; ImportSource deletion is restricted
while links exist. The source is immutable during edit. New links require an
active source, while links whose source later becomes inactive remain visible,
correctable, and removable. Link/update/unlink actions lock and transact the
Brand and linkage, convert unique races to controlled conflicts, and write a
Brand-subject audit event in the same transaction.

The optional record URL is presentation data, not a credential endpoint. It
accepts only absolute HTTP(S) URLs with a host and no userinfo. UI and audit
expose only source name/code/status, external ID, and safe URL as appropriate;
`ImportSource.config_json` and internal IDs/hashes are never serialized.

## Domain boundaries

`ImportSource` describes imported catalog data. `PriceSource` and
`ExternalProductMapping` describe merchant/offer Product matching and are not
reused. `MediaSource`, public social profiles, legal ownership, field-level
lineage, ImportBatch observation history, confidence/candidate matching,
automatic Brand creation/merge, and automatic canonical overwrite are separate
future concerns. External identities also do not participate in Brand
translation source hashes, Tags, or derived Category coverage.
