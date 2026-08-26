<?php

namespace App\Actions\CentralCatalog\Concerns;

use App\Data\CentralCatalog\CentralBrandInput;
use App\Models\CentralCatalog\CentralBrand;
use App\Queries\CentralCatalog\DuplicateCentralBrandNameQuery;
use App\Support\Normalization\BrandInputNormalizer;
use App\Support\Normalization\SlugNormalizer;
use App\Support\Validation\CentralBrandProfileConstraints;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

trait ValidatesCentralBrandInput
{
    /**
     * @return array{name: string, normalized_name: string, normalized_name_hash: string, slug: string, website_url: string|null, country_id: int|null, founded_year: int|null, support_url: string|null, contact_email: string|null, primary_color: string|null}
     */
    private function validatedBrandInput(CentralBrandInput $input, ?CentralBrand $brand = null): array
    {
        $name = BrandInputNormalizer::name($input->name);

        Validator::make(['name' => $name], [
            'name' => ['required', 'min:1', 'max:255'],
        ])->validate();

        $slug = $this->normalizedSlug($input->slug, $name, $brand);
        $websiteUrl = $this->normalizedWebsiteUrl($input, $brand);
        $countryId = $this->normalizedCountryId($input, $brand);
        $foundedYear = $this->normalizedFoundedYear($input, $brand);
        $supportUrl = $this->normalizedSupportUrl($input, $brand);
        $contactEmail = $this->normalizedContactEmail($input, $brand);
        $primaryColor = $this->normalizedPrimaryColor($input, $brand);

        $slugRule = Rule::unique('central_brands', 'slug');

        if ($brand !== null) {
            $slugRule->ignore($brand->getKey(), $brand->getKeyName());
        }

        $normalizedRules = [
            'slug' => ['required', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slugRule],
        ];

        if ($brand === null || $input->hasWebsiteUrl) {
            $normalizedRules['website_url'] = ['nullable', 'max:255', 'url:http,https'];
        }

        if ($brand === null || $input->hasCountryId) {
            $normalizedRules['country_id'] = ['nullable', 'integer'];
        }

        if ($brand === null || $input->hasFoundedYear) {
            $normalizedRules['founded_year'] = [
                'nullable',
                'integer',
                'min:'.CentralBrandProfileConstraints::MIN_FOUNDED_YEAR,
                'max:'.CentralBrandProfileConstraints::maximumFoundedYear(),
            ];
        }

        if ($brand === null || $input->hasSupportUrl) {
            $normalizedRules['support_url'] = ['nullable', 'max:'.CentralBrandProfileConstraints::URL_MAX_LENGTH, 'url:http,https'];
        }

        if ($brand === null || $input->hasContactEmail) {
            $normalizedRules['contact_email'] = ['nullable', 'max:'.CentralBrandProfileConstraints::EMAIL_MAX_LENGTH, 'email'];
        }

        if ($brand === null || $input->hasPrimaryColor) {
            $normalizedRules['primary_color'] = ['nullable', 'regex:'.CentralBrandProfileConstraints::HEX_COLOR_PATTERN];
        }

        $normalizedValidator = Validator::make([
            'name' => $name,
            'slug' => $slug,
            'website_url' => $websiteUrl,
            'country_id' => $countryId,
            'founded_year' => $foundedYear,
            'support_url' => $supportUrl,
            'contact_email' => $contactEmail,
            'primary_color' => $primaryColor,
        ], $normalizedRules);

        $normalizedValidator->after(function ($validator) use ($name, $brand): void {
            if (! $validator->errors()->has('name') && (new DuplicateCentralBrandNameQuery)->exists($name, $brand)) {
                $validator->errors()->add('name', 'A brand with this canonical name already exists.');
            }
        });

        $normalizedValidator->validate();

        return [
            'name' => $name,
            'normalized_name' => BrandInputNormalizer::nameIdentity($name),
            'normalized_name_hash' => BrandInputNormalizer::nameIdentityHash($name),
            'slug' => $slug,
            'website_url' => $websiteUrl,
            'country_id' => $countryId,
            'founded_year' => $foundedYear,
            'support_url' => $supportUrl,
            'contact_email' => $contactEmail,
            'primary_color' => $primaryColor,
        ];
    }

    /**
     * @param  array{name: string, normalized_name: string, normalized_name_hash: string, slug: string, website_url: string|null, country_id: int|null, founded_year: int|null, support_url: string|null, contact_email: string|null, primary_color: string|null}  $validated
     * @return array<string, string>
     */
    private function uniqueConstraintValidationErrors(array $validated, ?CentralBrand $brand = null): array
    {
        $errors = [];

        if ((new DuplicateCentralBrandNameQuery)->exists($validated['name'], $brand)) {
            $errors['name'] = 'A brand with this canonical name already exists.';
        }

        $slugQuery = CentralBrand::query()->where('slug', $validated['slug']);

        if ($brand !== null) {
            $slugQuery->where($brand->getKeyName(), '!=', $brand->getKey());
        }

        if ($slugQuery->exists()) {
            $errors['slug'] = 'The slug has already been taken.';
        }

        return $errors;
    }

    private function normalizedSlug(?string $slugInput, string $name, ?CentralBrand $brand): string
    {
        if ($slugInput === null || trim($slugInput) === '') {
            if ($brand !== null) {
                return $brand->slug;
            }

            $slugInput = Str::slug($name);
        }

        try {
            return SlugNormalizer::normalize($slugInput);
        } catch (InvalidArgumentException $exception) {
            $message = $brand === null && $slugInput === ''
                ? 'A canonical ASCII slug is required. Enter the slug manually.'
                : $exception->getMessage();

            throw ValidationException::withMessages(['slug' => $message]);
        }
    }

    private function normalizedWebsiteUrl(CentralBrandInput $input, ?CentralBrand $brand): ?string
    {
        if (! $input->hasWebsiteUrl) {
            return $brand?->website_url;
        }

        return BrandInputNormalizer::nullableUrl($input->websiteUrl);
    }

    private function normalizedCountryId(CentralBrandInput $input, ?CentralBrand $brand): ?int
    {
        if (! $input->hasCountryId) {
            return $brand?->country_id;
        }

        return $input->countryId;
    }

    private function normalizedFoundedYear(CentralBrandInput $input, ?CentralBrand $brand): ?int
    {
        return $input->hasFoundedYear ? $input->foundedYear : $brand?->founded_year;
    }

    private function normalizedSupportUrl(CentralBrandInput $input, ?CentralBrand $brand): ?string
    {
        if (! $input->hasSupportUrl) {
            return $brand?->support_url;
        }

        return BrandInputNormalizer::nullableUrl($input->supportUrl);
    }

    private function normalizedContactEmail(CentralBrandInput $input, ?CentralBrand $brand): ?string
    {
        if (! $input->hasContactEmail) {
            return $brand?->contact_email;
        }

        return BrandInputNormalizer::nullableEmail($input->contactEmail);
    }

    private function normalizedPrimaryColor(CentralBrandInput $input, ?CentralBrand $brand): ?string
    {
        if (! $input->hasPrimaryColor) {
            return $brand?->primary_color;
        }

        return BrandInputNormalizer::nullableHexColor($input->primaryColor);
    }
}
