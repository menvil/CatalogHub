<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class CreateCentralBrandOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->session()->flash('brand_ownership_modal', 'create');
        $this->session()->flash('brand_ownership_errors', $validator->errors()->toArray());

        parent::failedValidation($validator);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'organization_name' => [
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
        ];
    }

    public function organizationName(): string
    {
        $name = $this->validated('organization_name');
        assert(is_string($name));

        return $name;
    }
}
