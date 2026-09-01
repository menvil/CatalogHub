<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;

final class SearchOrganizationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:255']];
    }

    public function queryText(): ?string
    {
        $query = $this->validated('q');

        return is_string($query) ? $query : null;
    }
}
