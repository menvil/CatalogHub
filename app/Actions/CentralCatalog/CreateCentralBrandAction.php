<?php

namespace App\Actions\CentralCatalog;

use App\Actions\CentralCatalog\Concerns\ValidatesCentralBrandInput;
use App\Data\CentralCatalog\CentralBrandInput;
use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Geography\CountryAssignmentValidator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateCentralBrandAction
{
    use ValidatesCentralBrandInput;

    public function __construct(
        private AuditRecorder $audit,
        private CountryAssignmentValidator $countryAssignments,
    ) {}

    public function handle(User $actor, CentralBrandInput $input): CentralBrand
    {
        /** @var array{name: string, normalized_name: string, normalized_name_hash: string, slug: string, website_url: string|null, country_id: int|null}|null $validated */
        $validated = null;

        try {
            return DB::transaction(function () use ($actor, $input, &$validated): CentralBrand {
                $validated = $this->validatedBrandInput($input);

                if ($validated['country_id'] !== null) {
                    $this->countryAssignments->lockActive($validated['country_id']);
                }

                $brand = new CentralBrand;
                $brand->forceFill([...$validated, 'status' => CentralBrandStatus::Draft])->saveOrFail();
                $brand->load('country');
                $this->audit->record(
                    AuditAction::CatalogBrandCreated,
                    AuditContext::Central,
                    $actor,
                    $brand,
                    null,
                    null,
                    [
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'status' => $brand->status->value,
                        'website_url' => $brand->website_url,
                        'country_code' => $brand->country?->alpha2,
                    ],
                );

                return $brand;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if ($validated === null) {
                throw $exception;
            }

            $errors = $this->uniqueConstraintValidationErrors($validated);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            throw $exception;
        }
    }
}
