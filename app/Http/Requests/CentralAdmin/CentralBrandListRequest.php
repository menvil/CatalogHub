<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Data\CentralCatalog\BrandListFiltersData;
use App\Enums\CentralBrandStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CentralBrandListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(CentralBrandStatus::class)],
            'sort' => ['nullable', Rule::in(['name', 'slug', 'status', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in([20, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): BrandListFiltersData
    {
        $data = $this->validated();

        return new BrandListFiltersData(
            search: $this->nullableTrimmedString($data, 'q'),
            status: $this->nullableTrimmedString($data, 'status'),
            sort: is_string($data['sort'] ?? null) ? $data['sort'] : 'name',
            direction: is_string($data['direction'] ?? null) ? $data['direction'] : 'asc',
            perPage: is_numeric($data['per_page'] ?? null) ? (int) $data['per_page'] : 20,
        );
    }

    /** @param array<string, mixed> $data */
    private function nullableTrimmedString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
