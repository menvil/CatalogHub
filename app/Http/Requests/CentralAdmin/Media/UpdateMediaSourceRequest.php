<?php

namespace App\Http\Requests\CentralAdmin\Media;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateMediaSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2000'],
            'license_type' => ['nullable', 'string', 'max:100'],
            'license_url' => ['nullable', 'url', 'max:2000'],
            'attribution' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array{source_type?: string|null, source_name?: string|null, source_url?: string|null, license_type?: string|null, license_url?: string|null, attribution?: string|null}
     */
    public function payload(): array
    {
        return $this->validated();
    }
}
