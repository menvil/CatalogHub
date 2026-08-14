<?php

namespace App\Actions\CentralCatalog;

use App\Actions\CentralCatalog\Concerns\ValidatesCentralBrandInput;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class CreateCentralBrandAction
{
    use ValidatesCentralBrandInput;

    /** @param array<string, mixed> $data */
    public function handle(array $data): CentralBrand
    {
        $validated = $this->validatedBrandInput($data);

        try {
            $brand = new CentralBrand;
            $brand->forceFill([
                ...$validated,
                'status' => CentralBrandStatus::Draft,
            ])->saveOrFail();

            return $brand;
        } catch (UniqueConstraintViolationException $exception) {
            $errors = $this->uniqueConstraintValidationErrors($validated);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            throw $exception;
        }
    }
}
