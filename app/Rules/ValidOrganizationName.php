<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Normalization\OrganizationNameNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidOrganizationName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute field must be a string.');

            return;
        }

        if (! OrganizationNameNormalizer::isValidInput($value)) {
            $fail('Organization names must be valid UTF-8 and cannot contain control characters or newlines.');

            return;
        }

        $name = OrganizationNameNormalizer::display($value);
        if ($name === '') {
            $fail('The :attribute field is required.');

            return;
        }

        if (mb_strlen($name, 'UTF-8') > 255) {
            $fail('The :attribute field must not be greater than 255 characters.');
        }
    }
}
