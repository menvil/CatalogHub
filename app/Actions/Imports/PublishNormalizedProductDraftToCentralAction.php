<?php

namespace App\Actions\Imports;

use App\Enums\AttributeDataType;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use LogicException;

final class PublishNormalizedProductDraftToCentralAction
{
    /** @var list<string> */
    private const MEDIA_ROLES = ['main', 'card', 'gallery', 'hero', 'og', 'logo', 'icon', 'manual', 'package', 'technical'];

    /** @var list<string> */
    private const SINGULAR_MEDIA_ROLES = ['main', 'card', 'hero', 'og', 'logo', 'icon'];

    public function handle(NormalizedProductDraft $draft, ?User $user): CentralProduct
    {
        if (! $user instanceof User || ! ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor())) {
            throw new AuthorizationException('You are not allowed to publish normalized drafts.');
        }

        return DB::transaction(function () use ($draft): CentralProduct {
            $lockedDraft = NormalizedProductDraft::query()->lockForUpdate()->findOrFail($draft->id);

            if ($lockedDraft->status !== 'approved') {
                throw new LogicException("Draft [{$lockedDraft->id}] cannot be published from status [{$lockedDraft->status}].");
            }

            if ($lockedDraft->errors()->where('severity', 'critical')->whereNull('resolved_at')->exists()) {
                throw new LogicException("Draft [{$lockedDraft->id}] has unresolved critical normalization errors.");
            }

            $product = $this->persistProduct($lockedDraft);
            $this->persistAttributes($lockedDraft, $product);
            $this->persistMedia($lockedDraft, $product);

            $lockedDraft->forceFill([
                'status' => 'published',
                'published_central_product_id' => $product->id,
            ])->save();

            return $product->refresh();
        });
    }

    private function persistProduct(NormalizedProductDraft $draft): CentralProduct
    {
        $isMatched = $draft->matched_central_product_id !== null;
        $product = $isMatched
            ? CentralProduct::query()->lockForUpdate()->findOrFail($draft->matched_central_product_id)
            : new CentralProduct;

        $data = [
            'name' => $draft->title,
            'model' => $draft->normalized_payload_json['model'] ?? $product->model,
        ];

        if (! $isMatched || $draft->brand_id !== null) {
            $data['central_brand_id'] = $draft->brand_id;
        }

        if (! $isMatched || $draft->category_id !== null) {
            $data['central_category_id'] = $draft->category_id;
        }

        if (filled($draft->slug)) {
            $data['slug'] = $draft->slug;
        }

        if (! $isMatched) {
            $data['status'] = CentralProductStatus::Draft;
        }

        $product->fill($data)->save();

        return $product;
    }

    private function persistAttributes(NormalizedProductDraft $draft, CentralProduct $product): void
    {
        $definitionIds = [];

        foreach ($draft->attributes_json ?? [] as $candidate) {
            if (($candidate['is_valid'] ?? true) === false) {
                throw new LogicException("Draft [{$draft->id}] contains an invalid normalized attribute candidate.");
            }

            $definition = $this->resolveAttributeDefinition(
                $draft,
                $candidate,
                (int) $product->central_category_id,
            );

            if (isset($definitionIds[$definition->id])) {
                throw new LogicException("Draft [{$draft->id}] contains duplicate candidates for attribute [{$definition->id}].");
            }

            $definitionIds[$definition->id] = true;

            if ((int) $product->central_category_id !== (int) $definition->central_category_id) {
                throw new LogicException("Attribute [{$definition->id}] does not belong to the published product category.");
            }

            CentralProductAttributeValue::query()->updateOrCreate(
                [
                    'central_product_id' => $product->id,
                    'attribute_definition_id' => $definition->id,
                ],
                $this->attributeStorageData($draft, $definition, $candidate),
            );
        }
    }

    /** @param array<string, mixed> $candidate */
    private function resolveAttributeDefinition(
        NormalizedProductDraft $draft,
        array $candidate,
        int $categoryId,
    ): AttributeDefinition {
        $definition = null;

        if (isset($candidate['attribute_definition_id'])) {
            $definition = AttributeDefinition::query()->find((int) $candidate['attribute_definition_id']);
        } elseif (filled($candidate['code'] ?? null)) {
            $definition = AttributeDefinition::query()
                ->where('central_category_id', $categoryId)
                ->where('code', $candidate['code'])
                ->first();
        }

        if (! $definition instanceof AttributeDefinition) {
            throw new LogicException("Draft [{$draft->id}] references an unknown attribute definition.");
        }

        return $definition;
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private function attributeStorageData(
        NormalizedProductDraft $draft,
        AttributeDefinition $definition,
        array $candidate,
    ): array {
        $type = (string) ($candidate['value_type'] ?? $definition->data_type->value);

        if ($type !== $definition->data_type->value) {
            throw new LogicException(
                "Attribute [{$definition->id}] expects value type [{$definition->data_type->value}], [{$type}] given."
            );
        }

        $value = $candidate['value'] ?? null;
        $metadata = is_array($candidate['metadata'] ?? null) ? $candidate['metadata'] : [];
        $rawValue = $candidate['raw_value'] ?? null;

        $stored = [
            'raw_value' => is_scalar($rawValue) || $rawValue === null
                ? $rawValue
                : json_encode($rawValue, JSON_UNESCAPED_UNICODE),
            'value_type' => $type,
            'value_text' => null,
            'value_number' => null,
            'value_bool' => null,
            'value_enum_code' => null,
            'value_json' => null,
            'value_min' => $candidate['value_min'] ?? null,
            'value_max' => $candidate['value_max'] ?? null,
            'source_unit' => $candidate['source_unit'] ?? $metadata['source_unit'] ?? null,
            'canonical_value' => $candidate['canonical_value'] ?? $metadata['canonical_value'] ?? null,
            'canonical_unit' => $candidate['canonical_unit'] ?? $metadata['canonical_unit'] ?? null,
            'confidence' => $candidate['confidence'] ?? $draft->confidence,
            'source_type' => 'import',
            'source_id' => (string) ($draft->rawProduct->external_id ?? $draft->raw_product_id),
            'source_reference' => [
                'import_batch_id' => $draft->import_batch_id,
                'raw_product_id' => $draft->raw_product_id,
                'normalized_product_draft_id' => $draft->id,
            ],
        ];

        match ($definition->data_type) {
            AttributeDataType::Integer,
            AttributeDataType::Decimal => $stored['value_number'] = $candidate['value_number'] ?? $value,
            AttributeDataType::String,
            AttributeDataType::Text => $stored['value_text'] = $candidate['value_text'] ?? $value,
            AttributeDataType::Boolean => $stored['value_bool'] = $candidate['value_bool'] ?? $value,
            AttributeDataType::Enum => $stored['value_enum_code'] = $candidate['value_enum_code'] ?? $value,
            AttributeDataType::MultiEnum,
            AttributeDataType::Json => $stored['value_json'] = $candidate['value_json'] ?? $value,
        };

        if (in_array($type, [AttributeDataType::Integer->value, AttributeDataType::Decimal->value], true)) {
            $stored['canonical_value'] ??= $stored['value_number'];
            $stored['canonical_unit'] ??= $definition->canonical_unit;
        }

        return $stored;
    }

    private function persistMedia(NormalizedProductDraft $draft, CentralProduct $product): void
    {
        $position = 0;

        foreach ($draft->media_json ?? [] as $candidate) {
            if (! is_array($candidate) || ! in_array($candidate['status'] ?? null, ['downloaded', 'accepted'], true)) {
                continue;
            }

            $asset = MediaAsset::query()->find((int) ($candidate['media_asset_id'] ?? 0));

            if (! $asset instanceof MediaAsset) {
                throw new LogicException("Draft [{$draft->id}] references an unknown media asset.");
            }

            $role = (string) ($candidate['role'] ?? ($position === 0 ? 'main' : 'gallery'));

            if (! in_array($role, self::MEDIA_ROLES, true)) {
                throw new LogicException("Unsupported imported media role [{$role}].");
            }

            $scope = [
                'entity_type' => 'central_product',
                'entity_id' => $product->id,
                'role' => $role,
                'locale' => $candidate['locale'] ?? null,
                'site_id' => null,
                'market_id' => null,
            ];
            $isPrimary = in_array($role, self::SINGULAR_MEDIA_ROLES, true)
                || (bool) ($candidate['is_primary'] ?? false);

            if (in_array($role, self::SINGULAR_MEDIA_ROLES, true)) {
                MediaAssignment::query()->where($scope)->delete();
            }

            MediaAssignment::query()->updateOrCreate(
                $scope + ['media_asset_id' => $asset->id],
                [
                    'position' => $position,
                    'is_primary' => $isPrimary,
                    'visibility' => 'global',
                ],
            );
            $position++;
        }
    }
}
