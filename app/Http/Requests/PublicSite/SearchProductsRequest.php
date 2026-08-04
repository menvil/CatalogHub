<?php

namespace App\Http\Requests\PublicSite;

use App\Data\PublicSite\PublicSearchData;
use Illuminate\Foundation\Http\FormRequest;

final class SearchProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function searchData(): PublicSearchData
    {
        $value = $this->validated('q');

        return new PublicSearchData(
            term: is_string($value) ? trim($value) : '',
        );
    }
}
