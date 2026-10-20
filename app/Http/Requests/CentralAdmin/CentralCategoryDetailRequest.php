<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CentralCategoryDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['locale' => ['nullable', 'integer', 'min:1', Rule::exists('locales', 'id')->where('is_active', true)]];
    }

    public function localeId(): ?int
    {
        return $this->validated('locale') === null ? null : (int) $this->validated('locale');
    }
}
