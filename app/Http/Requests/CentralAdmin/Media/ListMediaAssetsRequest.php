<?php

namespace App\Http\Requests\CentralAdmin\Media;

use App\Data\Media\MediaLibraryFiltersData;
use Illuminate\Foundation\Http\FormRequest;

final class ListMediaAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function filters(): MediaLibraryFiltersData
    {
        $data = $this->validated();

        return new MediaLibraryFiltersData(
            status: $this->nullableString($data, 'status'),
            type: $this->nullableString($data, 'type'),
            search: $this->nullableString($data, 'search'),
        );
    }

    /** @param array<string, mixed> $data */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
