<?php

declare(strict_types=1);

namespace App\Actions\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Organization;
use App\Models\User;
use App\Rules\ValidOrganizationName;
use App\Support\Normalization\OrganizationNameNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final readonly class CreateOrganizationAndAssignCentralBrandOwnerAction
{
    public function __construct(private AssignCentralBrandOwnerAction $assignOwner) {}

    public function handle(User $actor, CentralBrand $brand, string $name): CentralBrand
    {
        Gate::forUser($actor)->authorize('catalog.brands.manage');

        Validator::make(['name' => $name], ['name' => ['required', new ValidOrganizationName]])->validate();
        $name = OrganizationNameNormalizer::display($name);

        return DB::transaction(function () use ($actor, $brand, $name): CentralBrand {
            $normalizedName = OrganizationNameNormalizer::search($name);
            $organization = new Organization;
            $organization->forceFill([
                'name' => $name,
                'normalized_name' => $normalizedName,
                'normalized_name_prefix' => OrganizationNameNormalizer::prefixForNormalizedName($normalizedName),
            ])->saveOrFail();

            return $this->assignOwner->handle($actor, $brand, $organization);
        });
    }
}
