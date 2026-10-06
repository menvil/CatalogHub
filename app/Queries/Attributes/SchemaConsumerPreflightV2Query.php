<?php

namespace App\Queries\Attributes;

/** Runtime entry point for the immutable Phase 19.3 contract. */
final class SchemaConsumerPreflightV2Query
{
    /** @return array<string, mixed> */
    public function report(): array
    {
        return (new \App\Queries\SchemaCutoverV2\SchemaConsumerPreflightV2Query)->report();
    }

    public function assertReady(): void
    {
        (new \App\Queries\SchemaCutoverV2\SchemaConsumerPreflightV2Query)->assertReady();
    }
}
