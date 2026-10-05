<?php

namespace App\Actions\CategorySchema;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\User;
use App\Services\AttributeGlobalization\GlobalAttributeWriter;

final readonly class CreateGlobalAttributeDefinitionAction
{
    public function __construct(private GlobalAttributeWriter $writer) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data, ?User $actor = null): AttributeDefinition
    {
        return $this->writer->create($data, $actor);
    }
}
