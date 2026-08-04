<?php

namespace App\Http\Requests\PublicSite;

use App\Data\Facets\FacetFilterSet;
use App\Data\PublicSite\PublicProductListingData;
use App\Rules\FlatQueryParameter;
use Illuminate\Foundation\Http\FormRequest;

final class ListProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            '*' => ['nullable', new FlatQueryParameter],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }

    public function listingData(): PublicProductListingData
    {
        $data = $this->validated();
        $perPage = $data['per_page'] ?? 12;

        return new PublicProductListingData(
            filters: FacetFilterSet::fromQuery($data),
            perPage: is_numeric($perPage) ? (int) $perPage : 12,
        );
    }
}
