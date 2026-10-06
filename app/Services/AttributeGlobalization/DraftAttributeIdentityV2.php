<?php

namespace App\Services\AttributeGlobalization;

use App\Models\Imports\NormalizedProductDraft;
use App\Queries\SchemaCutoverV2\DraftAttributeIdentityV2Query;

/** Runtime adapter for the immutable v1-to-v2 identity contract. */
final class DraftAttributeIdentityV2
{
    /** @return list<array<string, mixed>> */
    public function candidates(NormalizedProductDraft $draft, ?int $categoryId = null): array
    {
        return (new DraftAttributeIdentityV2Query)->candidates((object) [
            'category_id' => $draft->category_id,
            'matched_central_product_id' => $draft->matched_central_product_id,
            'attribute_identity_version' => $draft->attribute_identity_version,
            'attributes_json' => $draft->getAttribute('attributes_json'),
        ], $categoryId);
    }
}
