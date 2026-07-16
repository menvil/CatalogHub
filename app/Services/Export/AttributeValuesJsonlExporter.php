<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\CentralProductAttributeValue;

final class AttributeValuesJsonlExporter
{
    public function __construct(private readonly JsonlStreamWriter $writer) {}

    public function export(CatalogSnapshot $snapshot): JsonlExportResult
    {
        $rows = CentralProductAttributeValue::query()
            ->orderBy('id')
            ->cursor()
            ->map(fn (CentralProductAttributeValue $value): array => [
                'id' => $value->getKey(),
                'product_id' => $value->central_product_id,
                'attribute_definition_id' => $value->attribute_definition_id,
                'raw_value' => $value->raw_value,
                'value_type' => $value->value_type,
                'value_text' => $value->value_text,
                'value_number' => $value->value_number,
                'value_bool' => $value->value_bool,
                'value_enum_code' => $value->value_enum_code,
                'value_json' => $value->value_json,
                'value_min' => $value->value_min,
                'value_max' => $value->value_max,
                'source_unit' => $value->source_unit,
                'canonical_value' => $value->canonical_value,
                'canonical_unit' => $value->canonical_unit,
                'confidence' => $value->confidence,
                'source_type' => $value->source_type,
                'source_id' => $value->source_id,
                'source_reference' => $value->source_reference,
                'created_at' => $value->created_at?->toISOString(),
                'updated_at' => $value->updated_at?->toISOString(),
            ]);

        return $this->writer->write($snapshot, 'attribute_values', $rows);
    }
}
