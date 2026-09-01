<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Rules\ValidOrganizationName;
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
                new ValidOrganizationName,
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
