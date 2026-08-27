<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\Imports\LinkCentralBrandExternalIdentityAction;
use App\Actions\Imports\RemoveCentralBrandExternalIdentityAction;
use App\Actions\Imports\UpdateCentralBrandExternalIdentityAction;
use App\Exceptions\Imports\ExternalIdentityConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\StoreCentralBrandExternalIdentityRequest;
use App\Http\Requests\CentralAdmin\UpdateCentralBrandExternalIdentityRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\User;
use App\Queries\Imports\CentralBrandExternalIdentityQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CentralBrandExternalIdentityController extends Controller
{
    public function store(
        StoreCentralBrandExternalIdentityRequest $request,
        CentralBrand $brand,
        LinkCentralBrandExternalIdentityAction $link,
        CentralBrandExternalIdentityQuery $query,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);
        $source = $query->sourceForLink($request->importSourceId());

        try {
            $link->handle($actor, $brand, $source, $request->externalId(), $request->externalUrl());
        } catch (ExternalIdentityConflictException $exception) {
            return $this->validationRedirect('add', ['external_id' => [$exception->getMessage()]]);
        } catch (ValidationException $exception) {
            return $this->validationRedirect('add', $exception->errors());
        }

        return $this->detailRedirect($brand, 'External identity linked.');
    }

    public function update(
        UpdateCentralBrandExternalIdentityRequest $request,
        CentralBrand $brand,
        CentralBrandExternalIdentity $externalIdentity,
        UpdateCentralBrandExternalIdentityAction $update,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        try {
            $update->handle($actor, $brand, $externalIdentity, $request->externalId(), $request->externalUrl());
        } catch (ExternalIdentityConflictException $exception) {
            return $this->validationRedirect((string) $externalIdentity->getKey(), ['external_id' => [$exception->getMessage()]]);
        } catch (ValidationException $exception) {
            return $this->validationRedirect((string) $externalIdentity->getKey(), $exception->errors());
        }

        return $this->detailRedirect($brand, 'External identity updated.');
    }

    public function destroy(
        Request $request,
        CentralBrand $brand,
        CentralBrandExternalIdentity $externalIdentity,
        RemoveCentralBrandExternalIdentityAction $remove,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);
        $remove->handle($actor, $brand, $externalIdentity);

        return $this->detailRedirect($brand, 'External identity removed.');
    }

    private function detailRedirect(CentralBrand $brand, string $message): RedirectResponse
    {
        return redirect()
            ->to(route('central.brands.show', $brand).'#external-identities')
            ->with('success', $message);
    }

    /** @param array<string, list<string>> $errors */
    private function validationRedirect(string $modal, array $errors): RedirectResponse
    {
        return back()
            ->withErrors($errors)
            ->withInput()
            ->with('external_identity_modal', $modal)
            ->with('external_identity_errors', $errors);
    }
}
