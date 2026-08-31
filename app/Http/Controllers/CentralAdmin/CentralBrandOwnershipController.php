<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\AssignCentralBrandOwnerAction;
use App\Actions\CentralCatalog\ClearCentralBrandOwnerAction;
use App\Actions\CentralCatalog\CreateOrganizationAndAssignCentralBrandOwnerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\AssignCentralBrandOwnerRequest;
use App\Http\Requests\CentralAdmin\CreateCentralBrandOwnerRequest;
use App\Http\Requests\CentralAdmin\SearchOrganizationsRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Queries\CentralCatalog\CentralBrandOwnershipQuery;
use App\Queries\CentralCatalog\OrganizationSearchQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CentralBrandOwnershipController extends Controller
{
    public function search(
        SearchOrganizationsRequest $request,
        CentralBrand $brand,
        OrganizationSearchQuery $organizations,
    ): JsonResponse {
        return response()->json(['options' => $organizations->search($request->queryText())]);
    }

    public function assign(
        AssignCentralBrandOwnerRequest $request,
        CentralBrand $brand,
        AssignCentralBrandOwnerAction $assignOwner,
        CentralBrandOwnershipQuery $ownerships,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);
        $organization = $ownerships->findOrganization($request->organizationId());
        $assignOwner->handle($actor, $brand, $organization);

        return redirect()
            ->route('central.brands.edit', $brand)
            ->with('success', 'Parent Company updated.');
    }

    public function create(
        CreateCentralBrandOwnerRequest $request,
        CentralBrand $brand,
        CreateOrganizationAndAssignCentralBrandOwnerAction $createAndAssign,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);
        $createAndAssign->handle($actor, $brand, $request->organizationName());

        return redirect()
            ->route('central.brands.edit', $brand)
            ->with('success', 'Organization created and assigned as Parent Company.');
    }

    public function clear(
        Request $request,
        CentralBrand $brand,
        ClearCentralBrandOwnerAction $clearOwner,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);
        $clearOwner->handle($actor, $brand);

        return redirect()
            ->route('central.brands.edit', $brand)
            ->with('success', 'Parent Company cleared.');
    }
}
