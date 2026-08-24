<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Data\CentralCatalog\CentralBrandInput;
use Illuminate\Foundation\Http\FormRequest;

final class CentralBrandFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:2'],
        ];
    }

    public function brandInput(): CentralBrandInput
    {
        $data = $this->validated();
        $name = $data['name'] ?? null;
        $slug = $data['slug'] ?? null;
        $websiteUrl = $data['website_url'] ?? null;
        $countryCode = $data['country_code'] ?? null;

        assert(is_string($name));
        assert(is_string($slug) || $slug === null);
        assert(is_string($websiteUrl) || $websiteUrl === null);
        assert(is_string($countryCode) || $countryCode === null);

        return new CentralBrandInput(
            name: $name,
            slug: $slug,
            hasWebsiteUrl: array_key_exists('website_url', $data),
            websiteUrl: $websiteUrl,
            hasCountryCode: array_key_exists('country_code', $data),
            countryCode: $countryCode,
        );
    }
}
