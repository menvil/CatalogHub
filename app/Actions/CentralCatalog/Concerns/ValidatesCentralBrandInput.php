<?php

namespace App\Actions\CentralCatalog\Concerns;

use App\Data\CentralCatalog\CentralBrandInput;
use App\Models\CentralCatalog\CentralBrand;
use App\Queries\CentralCatalog\DuplicateCentralBrandNameQuery;
use App\Support\Normalization\BrandInputNormalizer;
use App\Support\Normalization\SlugNormalizer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

trait ValidatesCentralBrandInput
{
    /**
     * @return array{name: string, normalized_name: string, normalized_name_hash: string, slug: string, website_url: string|null, country_code: string|null}
     */
    private function validatedBrandInput(CentralBrandInput $input, ?CentralBrand $brand = null): array
    {
        $name = BrandInputNormalizer::name($input->name);

        Validator::make(['name' => $name], [
            'name' => ['required', 'min:1', 'max:255'],
        ])->validate();

        $slug = $this->normalizedSlug($input->slug, $name, $brand);
        $websiteUrl = $this->normalizedWebsiteUrl($input, $brand);
        $countryCode = $this->normalizedCountryCode($input, $brand);

        $slugRule = Rule::unique('central_brands', 'slug');

        if ($brand !== null) {
            $slugRule->ignore($brand->getKey(), $brand->getKeyName());
        }

        $normalizedValidator = Validator::make([
            'name' => $name,
            'slug' => $slug,
            'website_url' => $websiteUrl,
            'country_code' => $countryCode,
        ], [
            'slug' => ['required', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slugRule],
            'website_url' => ['nullable', 'max:255', 'url:http,https'],
            'country_code' => ['nullable', 'regex:/\A[A-Z]{2}\z/'],
        ]);

        $normalizedValidator->after(function ($validator) use ($name, $brand): void {
            if (! $validator->errors()->has('name') && (new DuplicateCentralBrandNameQuery)->exists($name, $brand)) {
                $validator->errors()->add('name', 'A brand with this canonical name already exists.');
            }
        });

        $normalized = $normalizedValidator->validate();

        return [
            'name' => $name,
            'normalized_name' => BrandInputNormalizer::nameIdentity($name),
            'normalized_name_hash' => BrandInputNormalizer::nameIdentityHash($name),
            'slug' => (string) $normalized['slug'],
            'website_url' => isset($normalized['website_url']) ? (string) $normalized['website_url'] : null,
            'country_code' => isset($normalized['country_code']) ? (string) $normalized['country_code'] : null,
        ];
    }

    /**
     * @param  array{name: string, normalized_name: string, normalized_name_hash: string, slug: string, website_url: string|null, country_code: string|null}  $validated
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

    private function normalizedCountryCode(CentralBrandInput $input, ?CentralBrand $brand): ?string
    {
        if (! $input->hasCountryCode) {
            return $brand?->country_code;
        }

        return BrandInputNormalizer::countryCode($input->countryCode);
    }
}
