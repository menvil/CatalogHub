<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;

final class ListCentralBrandMediaAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'asset_search' => ['nullable', 'string', 'max:255'],
            'asset_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function assetSearch(): string
    {
        $value = $this->validated('asset_search');

        return is_string($value) ? trim($value) : '';
    }

    public function assetPage(): int
    {
        return max(1, (int) ($this->validated('asset_page') ?? 1));
    }
}
