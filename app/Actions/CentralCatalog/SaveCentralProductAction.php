<?php

namespace App\Actions\CentralCatalog;

use App\Enums\Permission;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Categories\CategoryAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Existing Product editor writes share the identity lock with Specs and publish. */
final class SaveCentralProductAction
{
    public function handle(?CentralProduct $product, array $data, ?User $actor = null): CentralProduct
    {
        app(CategoryAccess::class)->authorize(Permission::CatalogProductsManage, $actor);

        return DB::transaction(function () use ($product, $data): CentralProduct {
            app(AttributeIdentityLock::class)->acquireTarget();
            $record = $product === null ? new CentralProduct : CentralProduct::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $record->fill($data);
            if ($record->exists && $record->isDirty('central_category_id') && CentralProductAttributeValue::query()->where('central_product_id', $record->id)
                ->whereNotIn('attribute_definition_id', CategoryAttributeAssignment::query()->where('central_category_id', $record->central_category_id)->select('attribute_definition_id'))->exists()) {
                throw ValidationException::withMessages(['central_category_id' => 'Every stored fact requires an assignment in the resulting Category. Explicit Category migration required.']);
            }
            if (! $record->exists || $record->isDirty()) {
                $record->saveOrFail();
            }

            return $record;
        }, 3);
    }
}
