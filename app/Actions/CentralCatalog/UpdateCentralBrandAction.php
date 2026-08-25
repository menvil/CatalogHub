<?php

namespace App\Actions\CentralCatalog;

use App\Actions\CentralCatalog\Concerns\ValidatesCentralBrandInput;
use App\Data\CentralCatalog\CentralBrandInput;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class UpdateCentralBrandAction
{
    use ValidatesCentralBrandInput;

    public function handle(CentralBrand $brand, CentralBrandInput $input): CentralBrand
    {
        $brand->refresh();
        $validated = $this->validatedBrandInput($input, $brand);

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
