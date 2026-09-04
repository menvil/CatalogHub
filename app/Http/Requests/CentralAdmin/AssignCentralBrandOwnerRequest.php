<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignCentralBrandOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->session()->flash('brand_ownership_modal', 'assign');
        $this->session()->flash('brand_ownership_errors', $validator->errors()->toArray());

        parent::failedValidation($validator);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
        ];
    }

    public function organizationId(): int
    {
        $organizationId = $this->validated('organization_id');
        assert(is_int($organizationId) || is_string($organizationId));

        return (int) $organizationId;
    }
}
