<?php

namespace App\Http\Requests\PublicSite;

use App\Data\PublicSite\PublicComparisonData;
use Illuminate\Foundation\Http\FormRequest;

final class CompareProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'products' => ['array', 'max:4'],
            'products.*' => ['string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $products = $this->query('products', []);
        $products = is_string($products) ? explode(',', $products) : $products;

        $slugs = array_values(array_unique(array_filter(array_map(
            fn (mixed $slug): string => is_string($slug) ? trim($slug) : '',
            $products,
        ))));

        $this->merge(['products' => array_slice($slugs, 0, 4)]);
    }

    public function comparisonData(): PublicComparisonData
    {
        $products = $this->validated('products', []);

        return new PublicComparisonData(
            slugs: is_array($products) ? array_values($products) : [],
        );
    }
}
