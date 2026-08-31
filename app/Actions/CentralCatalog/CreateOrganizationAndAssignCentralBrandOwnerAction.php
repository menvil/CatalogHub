<?php

declare(strict_types=1);

namespace App\Actions\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Organization;
use App\Models\User;
use App\Support\Normalization\OrganizationNameNormalizer;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class CreateOrganizationAndAssignCentralBrandOwnerAction
{
    public function __construct(private AssignCentralBrandOwnerAction $assignOwner) {}

    public function handle(User $actor, CentralBrand $brand, string $name): CentralBrand
    {
        Validator::make(['name' => $name], [
            'name' => [
                'string',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    $controlCharacterMatch = is_string($value) ? preg_match('/\p{Cc}/u', $value) : false;
                    if ($controlCharacterMatch === false || $controlCharacterMatch === 1) {
                        $fail('Organization names must be valid UTF-8 and cannot contain control characters or newlines.');
                    }
                },
            ],
        ])->validate();

        $name = OrganizationNameNormalizer::display($name);
        Validator::make(['name' => $name], [
            'name' => [
                'required',
                'string',
                'max:255',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    $controlCharacterMatch = is_string($value) ? preg_match('/\p{Cc}/u', $value) : false;
                    if ($controlCharacterMatch === false || $controlCharacterMatch === 1) {
                        $fail('Organization names must be valid UTF-8 and cannot contain control characters or newlines.');
                    }
                },
            ],
        ])->validate();

        return DB::transaction(function () use ($actor, $brand, $name): CentralBrand {
            $organization = new Organization;
            $organization->forceFill([
                'name' => $name,
                'normalized_name' => OrganizationNameNormalizer::search($name),
            ])->saveOrFail();

            return $this->assignOwner->handle($actor, $brand, $organization);
        });
    }
}
