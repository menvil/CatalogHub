<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\SyncCentralBrandTagsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\UpdateCentralBrandTagsRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class CentralBrandTagsController extends Controller
{
    public function update(
        UpdateCentralBrandTagsRequest $request,
        CentralBrand $brand,
        SyncCentralBrandTagsAction $syncTags,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);
        $syncTags->handle($actor, $brand, $request->tagNames());

        return redirect()
            ->to(route('central.brands.show', $brand).'#classification')
            ->with('success', 'Brand tags updated.');
    }
}
