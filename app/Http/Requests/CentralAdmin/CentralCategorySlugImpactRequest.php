<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;

/** Read-only CA-018 slug routing-impact preview fragment. */
final class CentralCategorySlugImpactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['slug' => ['required', 'string', 'max:255']];
    }

    public function proposedSlug(): string
    {
        return (string) $this->validated('slug');
    }
}
