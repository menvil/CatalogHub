<?php

namespace App\Http\Requests\CentralAdmin\Catalog;

use App\Data\CentralCatalog\CentralBrandListFiltersData;
use App\Enums\CentralBrandStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListCentralBrandsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(CentralBrandStatus::class)],
            'sort' => ['nullable', Rule::in(['name', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([20, 50, 100])],
        ];
    }

    public function filters(): CentralBrandListFiltersData
    {
        $data = $this->validated();

        return new CentralBrandListFiltersData(
            search: $this->nullableString($data, 'search'),
            status: $this->nullableString($data, 'status'),
            sort: $this->nullableString($data, 'sort') ?? 'name',
            direction: $this->nullableString($data, 'direction') ?? 'asc',
        );
    }

    public function perPage(): int
    {
        $perPage = $this->validated('per_page');

        return is_int($perPage) ? $perPage : 20;
    }

    /** @param array<string, mixed> $data */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
