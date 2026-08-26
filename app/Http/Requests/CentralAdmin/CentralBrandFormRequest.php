<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin;

use App\Data\CentralCatalog\CentralBrandInput;
use App\Models\CentralCatalog\CentralBrand;
use App\Support\Validation\CentralBrandProfileConstraints;
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
            'founded_year' => [
                'nullable',
                'integer',
                'min:'.CentralBrandProfileConstraints::MIN_FOUNDED_YEAR,
                'max:'.CentralBrandProfileConstraints::maximumFoundedYear(),
            ],
            'support_url' => ['nullable', 'string', 'max:'.CentralBrandProfileConstraints::URL_MAX_LENGTH],
            'contact_email' => ['nullable', 'string', 'email', 'max:'.CentralBrandProfileConstraints::EMAIL_MAX_LENGTH],
            'primary_color' => ['nullable', 'string', 'regex:'.CentralBrandProfileConstraints::HEX_COLOR_PATTERN],
        ];
    }

    public function brandInput(): CentralBrandInput
    {
        $data = $this->validated();
        $name = $data['name'] ?? null;
        $slug = $data['slug'] ?? null;
        $websiteUrl = $data['website_url'] ?? null;
        $countryId = $data['country_id'] ?? null;
        $foundedYear = $data['founded_year'] ?? null;
        $supportUrl = $data['support_url'] ?? null;
        $contactEmail = $data['contact_email'] ?? null;
        $primaryColor = $data['primary_color'] ?? null;

        assert(is_string($name));
        assert(is_string($slug) || $slug === null);
        assert(is_string($websiteUrl) || $websiteUrl === null);
        assert(is_int($countryId) || is_string($countryId) || $countryId === null);
        assert(is_int($foundedYear) || is_string($foundedYear) || $foundedYear === null);
        assert(is_string($supportUrl) || $supportUrl === null);
        assert(is_string($contactEmail) || $contactEmail === null);
        assert(is_string($primaryColor) || $primaryColor === null);

        return new CentralBrandInput(
            name: $name,
            slug: $slug,
            hasWebsiteUrl: array_key_exists('website_url', $data),
            websiteUrl: $websiteUrl,
            hasCountryId: array_key_exists('country_id', $data),
            countryId: $countryId === null ? null : (int) $countryId,
            hasFoundedYear: array_key_exists('founded_year', $data),
            foundedYear: $foundedYear === null ? null : (int) $foundedYear,
            hasSupportUrl: array_key_exists('support_url', $data),
            supportUrl: $supportUrl,
            hasContactEmail: array_key_exists('contact_email', $data),
            contactEmail: $contactEmail,
            hasPrimaryColor: array_key_exists('primary_color', $data),
            primaryColor: $primaryColor,
        );
    }
}
