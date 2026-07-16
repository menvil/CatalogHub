<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Actions\Media\AssignMediaToProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Media\AssignProductMediaRequest;
use App\Http\Requests\CentralAdmin\Media\ProductMediaPreviewRequest;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MediaAssignment;
use App\Queries\Media\ProductMediaManagerQuery;
use App\Services\Media\MediaResolver;
use App\Services\Media\MediaUrlGenerator;
use App\Support\Media\MediaAssignmentRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class ProductMediaManagerController extends Controller
{
    public function show(
        ProductMediaPreviewRequest $request,
        CentralProduct $product,
        ProductMediaManagerQuery $query,
        MediaResolver $resolver,
        MediaUrlGenerator $urls,
    ): View {
        Gate::authorize('manageMedia', $product);

        $preview = $request->previewData();
        $media = $query->forProduct($product, $preview->mediaSearch);

        return view('central-admin.products.media-manager', [
            'product' => $product,
            'roles' => MediaAssignmentRoles::ALL,
            'assets' => $media->assets,
            'assetSearch' => $media->assetSearch,
            'assignments' => $media->assignments,
            'urlGenerator' => $urls,
            'resolution' => $resolver->explain(
                entityType: MediaAssignment::ENTITY_TYPE_CENTRAL_PRODUCT,
                entityId: (int) $product->getKey(),
                role: $preview->role,
                locale: $preview->locale,
                siteId: $preview->siteId,
                marketId: $preview->marketId,
            ),
            'previewRole' => $preview->role,
            'previewLocale' => $preview->locale,
            'previewSiteId' => $preview->siteId,
            'previewMarketId' => $preview->marketId,
        ]);
    }

    public function assign(
        AssignProductMediaRequest $request,
        CentralProduct $product,
        AssignMediaToProductAction $action,
    ): RedirectResponse {
        Gate::authorize('manageMedia', $product);

        $action->handle($product, $request->assignmentData());

        return redirect()
            ->route('central.products.media', $product)
            ->with('status', 'Media assignment saved.');
    }
}
