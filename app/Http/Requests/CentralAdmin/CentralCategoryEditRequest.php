<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;

/** CA-018 Edit GET; optional proposed slug whose read-only routing impact is rendered. */
final class CentralCategoryEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['slug_preview' => ['nullable', 'string', 'max:255']];
    }

    public function slugPreview(): ?string
    {
        $slug = $this->validated('slug_preview');

        return is_string($slug) ? $slug : null;
    }
}
