<?php

namespace App\Actions\CentralCatalog;

use App\Actions\CentralCatalog\Concerns\ValidatesCentralBrandInput;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class UpdateCentralBrandAction
{
    use ValidatesCentralBrandInput;

    /** @param array<string, mixed> $data */
    public function handle(CentralBrand $brand, array $data): CentralBrand
    {
        $brand->refresh();
        $validated = $this->validatedBrandInput($data, $brand);

        try {
            $brand->forceFill($validated)->saveOrFail();
        } catch (UniqueConstraintViolationException $exception) {
            $brand->refresh();
            $errors = $this->uniqueConstraintValidationErrors($validated, $brand);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            throw $exception;
        }

        return $brand->refresh();
    }
}
