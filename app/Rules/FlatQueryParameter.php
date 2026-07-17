<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class FlatQueryParameter implements ValidationRule
{
    private const MAX_VALUES = 100;

    private const MAX_LENGTH = 100;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_scalar($value)) {
            if (! is_string($value) || mb_strlen($value) <= self::MAX_LENGTH) {
                return;
            }

            $fail('The :attribute value may not be greater than '.self::MAX_LENGTH.' characters.');

            return;
        }

        if (! is_array($value) || ! array_is_list($value) || count($value) > self::MAX_VALUES) {
            $fail('The :attribute value must be a scalar or a flat list.');

            return;
        }

        foreach ($value as $item) {
            if (! is_scalar($item) || (is_string($item) && mb_strlen($item) > self::MAX_LENGTH)) {
                $fail('The :attribute value must contain only short scalar values.');

                return;
            }
        }
    }
}
