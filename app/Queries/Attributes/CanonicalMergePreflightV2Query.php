<?php

namespace App\Queries\Attributes;

/** Runtime entry point for the immutable Phase 19.3 contract. */
final class CanonicalMergePreflightV2Query
{
    /** @return array<string, list<mixed>> */
    public function blockers(): array
    {
        return (new \App\Queries\SchemaCutoverV2\CanonicalMergePreflightV2Query)->blockers();
    }
}
