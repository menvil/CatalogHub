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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateCentralBrandAction
{
    use ValidatesCentralBrandInput;

    public function __construct(private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrandInput $input): CentralBrand
    {
        $validated = $this->validatedBrandInput($input);

        try {
            return DB::transaction(function () use ($actor, $validated): CentralBrand {
                $brand = new CentralBrand;
                $brand->forceFill([...$validated, 'status' => CentralBrandStatus::Draft])->saveOrFail();
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
                        'country_code' => $brand->country_code,
                    ],
                );

                return $brand;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $errors = $this->uniqueConstraintValidationErrors($validated);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            throw $exception;
        }
    }
}
