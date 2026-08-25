<?php

namespace App\Actions\CentralCatalog;

use App\Actions\CentralCatalog\Concerns\ValidatesCentralBrandInput;
use App\Data\CentralCatalog\CentralBrandInput;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class CreateCentralBrandAction
{
    use ValidatesCentralBrandInput;

    public function handle(CentralBrandInput $input): CentralBrand
    {
        $validated = $this->validatedBrandInput($input);

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
