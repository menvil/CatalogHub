<?php

namespace App\Actions\Pricing;

use App\Enums\CentralProductStatus;
use App\Enums\ExternalProductMappingStatus;
use App\Enums\MarketOfferStatus;
use App\Exceptions\Pricing\CannotRemapExternalProductException;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ExternalProductMapping;
use App\Models\MarketOffer;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ManualRemapExternalProductAction
{
    /** @throws AuthorizationException */
    public function handle(
        User $admin,
        ExternalProductMapping $mapping,
        CentralProduct $newCentralProduct,
        string $reason,
    ): ExternalProductMapping {
        if (! $admin->hasCatalogHubPermission('prices.manage')) {
            throw new AuthorizationException('You are not allowed to remap external products.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new CannotRemapExternalProductException('A remap reason is required.');
        }

        DB::transaction(function () use ($admin, $mapping, $newCentralProduct, $reason): void {
            $locked = ExternalProductMapping::query()->lockForUpdate()->findOrFail($mapping->id);
            $target = CentralProduct::query()->lockForUpdate()->findOrFail($newCentralProduct->id);

            if ($locked->status === ExternalProductMappingStatus::Ignored) {
                throw new CannotRemapExternalProductException('Ignored mappings cannot be remapped.');
            }

            if ($target->status === CentralProductStatus::Archived) {
                throw new CannotRemapExternalProductException('Archived products cannot be remap targets.');
            }

            if ($locked->central_product_id === $target->id) {
                throw new CannotRemapExternalProductException('The mapping already uses this central product.');
            }

            $oldCentralProductId = $locked->central_product_id;

            MarketOffer::query()
                ->where('external_product_mapping_id', $locked->id)
                ->lockForUpdate()
                ->get()
                ->each(function (MarketOffer $offer) use ($locked, $target): void {
                    $collision = MarketOffer::query()
                        ->where('market_merchant_id', $offer->market_merchant_id)
                        ->where('central_product_id', $target->id)
                        ->where('price_source_id', $offer->price_source_id)
                        ->where('id', '!=', $offer->id)
                        ->lockForUpdate()
                        ->first();

                    if ($collision instanceof MarketOffer) {
                        $collision->update([
                            'external_product_mapping_id' => $locked->id,
                            'status' => MarketOfferStatus::Stale,
                        ]);
                        $offer->update([
                            'external_product_mapping_id' => null,
                            'status' => MarketOfferStatus::Stale,
                        ]);

                        return;
                    }

                    $offer->update([
                        'central_product_id' => $target->id,
                        'status' => MarketOfferStatus::Stale,
                    ]);
                });

            $existingNotes = trim((string) $locked->notes);
            $notes = $existingNotes === ''
                ? $reason
                : "{$existingNotes}\n\nRemapped: {$reason}";

            $locked->update([
                'central_product_id' => $target->id,
                'confidence' => 1,
                'status' => ExternalProductMappingStatus::Approved,
                'approved_at' => now(),
                'approved_by_user_id' => $admin->id,
                'rejected_at' => null,
                'rejected_by_user_id' => null,
                'notes' => $notes,
                'metadata' => [
                    ...($locked->metadata ?? []),
                    'manual_mapping' => true,
                    'last_mapping_action' => [
                        'action' => 'manual_remap',
                        'user_id' => $admin->id,
                        'old_central_product_id' => $oldCentralProductId,
                        'new_central_product_id' => $target->id,
                        'reason' => $reason,
                        'at' => now()->toISOString(),
                    ],
                ],
            ]);
        });

        return $mapping->refresh();
    }
}
