<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Data\CentralCatalog\CentralBrandInput;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CentralBrandFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $brand = $this->route('brand');
        $currentCountryId = $brand instanceof CentralBrand ? $brand->country_id : null;
        $countryExists = Rule::exists('countries', 'id')->where(
            static function (Builder $query) use ($currentCountryId): void {
                $query->where(static function (Builder $availability) use ($currentCountryId): void {
                    $availability->where('is_active', true);

                    if ($currentCountryId !== null) {
                        $availability->orWhere('id', $currentCountryId);
                    }
                });
            },
        );

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer', $countryExists],
        ];
    }

    public function brandInput(): CentralBrandInput
    {
        $data = $this->validated();
        $name = $data['name'] ?? null;
        $slug = $data['slug'] ?? null;
        $websiteUrl = $data['website_url'] ?? null;
        $countryId = $data['country_id'] ?? null;

        assert(is_string($name));
        assert(is_string($slug) || $slug === null);
        assert(is_string($websiteUrl) || $websiteUrl === null);
        assert(is_int($countryId) || is_string($countryId) || $countryId === null);

        return new CentralBrandInput(
            name: $name,
            slug: $slug,
            hasWebsiteUrl: array_key_exists('website_url', $data),
            websiteUrl: $websiteUrl,
            hasCountryId: array_key_exists('country_id', $data),
            countryId: $countryId === null ? null : (int) $countryId,
        );
    }
}
