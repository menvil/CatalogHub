<?php

namespace App\Actions\ProductAttributes;

use App\Domains\Projections\ProjectionStaleDetector;
use App\Enums\AttributeDataType;
use App\Enums\Permission;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Categories\CategoryAccess;
use App\Services\CategorySchema\SchemaRevision;
use App\Services\ProductAttributes\ProductAttributeValueValidator;
use App\Services\Units\UnitConverter;
use Illuminate\Support\Facades\DB;

final class SaveProductSpecsAction
{
    public function __construct(
        private readonly ProductAttributeValueValidator $validator,
        private readonly UnitConverter $unitConverter,
    ) {}

    /**
     * @param  array<int|string, array<string, mixed>>  $payload
     */
    public function handle(CentralProduct $product, array $payload, ?User $actor = null, ?int $expectedSchemaRevision = null): void
    {
        app(CategoryAccess::class)->authorize(Permission::CatalogProductsManage, $actor);
        DB::transaction(function () use ($product, $payload, $expectedSchemaRevision): void {
            app(AttributeIdentityLock::class)->acquire();
            $product = CentralProduct::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($expectedSchemaRevision !== null && $product->category !== null) {
                app(SchemaRevision::class)->expect($product->category, $expectedSchemaRevision);
            }
            $validated = $this->validator->validate($product, $payload);

            $changed = false;
            foreach ($validated as $attributeId => $valueData) {
                $existing = CentralProductAttributeValue::query()->where('central_product_id', $product->id)->where('attribute_definition_id', $attributeId)->lockForUpdate()->first();
                if ($this->isEmptyValue($valueData)) {
                    if ($existing !== null) {
                        $existing->delete();
                        $changed = true;
                    }

                    continue;
                }

                $stored = $this->normalizeForStorage($valueData, $existing);
                $value = $existing ?? new CentralProductAttributeValue(['central_product_id' => $product->id, 'attribute_definition_id' => $attributeId]);
                $value->fill($stored);
                if (! $value->exists || $value->isDirty()) {
                    $value->saveOrFail();
                    $changed = true;
                }
            }
            if (! $changed) {
                return;
            }
            app(ProjectionStaleDetector::class)->markStaleForProduct($product);
        });
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function isEmptyValue(array $valueData): bool
    {
        return match ($valueData['value_type']) {
            AttributeDataType::Integer->value, AttributeDataType::Decimal->value => blank($valueData['value_number'])
                && blank($valueData['value_min'])
                && blank($valueData['value_max']),
            AttributeDataType::String->value, AttributeDataType::Text->value => blank($valueData['value_text']),
            AttributeDataType::Boolean->value => $valueData['value_bool'] === null || $valueData['value_bool'] === '',
            AttributeDataType::Enum->value => blank($valueData['value_enum_code']),
            AttributeDataType::MultiEnum->value, AttributeDataType::Json->value => empty($valueData['value_json']),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $valueData
     * @return array<string, mixed>
     */
    private function normalizeForStorage(array $valueData, ?CentralProductAttributeValue $existing): array
    {
        $stored = [
            'raw_value' => $this->blankToNull($valueData['raw_value']),
            'value_type' => $valueData['value_type'],
            'value_text' => $this->blankToNull($valueData['value_text']),
            'value_number' => $this->blankToNull($valueData['value_number']),
            'value_bool' => $valueData['value_bool'],
            'value_enum_code' => $this->blankToNull($valueData['value_enum_code']),
            'value_json' => $valueData['value_json'],
            'value_min' => $this->blankToNull($valueData['value_min']),
            'value_max' => $this->blankToNull($valueData['value_max']),
            'source_unit' => $this->blankToNull($valueData['source_unit']),
            'canonical_value' => $this->blankToNull($valueData['canonical_value']),
            'canonical_unit' => $this->blankToNull($valueData['canonical_unit']),
            'confidence' => $this->blankToNull($valueData['confidence']),
            'source_type' => $this->blankToNull($valueData['source_type']),
            'source_id' => $this->blankToNull($valueData['source_id']),
            'source_reference' => $valueData['source_reference'],
        ];

        if (in_array($valueData['value_type'], [AttributeDataType::Integer->value, AttributeDataType::Decimal->value], true)) {
            $probe = $existing === null ? null : (clone $existing)->fill($stored);
            if ($probe === null || $probe->isDirty(['value_number', 'source_unit', 'canonical_unit'])) {
                $stored = $this->withCanonicalNumericValue($stored);
            } else {
                $stored['canonical_value'] = $existing->canonical_value;
                $stored['canonical_unit'] = $existing->canonical_unit;
            }
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private function withCanonicalNumericValue(array $stored): array
    {
        if (blank($stored['value_number'])) {
            $stored['canonical_value'] = null;

            return $stored;
        }

        if (filled($stored['source_unit']) && filled($stored['canonical_unit'])) {
            $stored['canonical_value'] = $this->unitConverter->convert(
                $stored['value_number'],
                (string) $stored['source_unit'],
                (string) $stored['canonical_unit'],
            );

            return $stored;
        }

        $stored['canonical_value'] = $stored['value_number'];
        $stored['canonical_unit'] = $stored['canonical_unit'] ?: $stored['source_unit'];

        return $stored;
    }

    private function blankToNull(mixed $value): mixed
    {
        return is_string($value) && blank($value) ? null : $value;
    }
}
