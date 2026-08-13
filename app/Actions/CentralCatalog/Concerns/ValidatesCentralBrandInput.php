<?php

namespace App\Actions\CentralCatalog\Concerns;

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
    /** @var list<string> */
    private const SUPPORTED_FIELDS = ['name', 'slug', 'website_url', 'country_code'];

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, slug: string, website_url: string|null, country_code: string|null}
     */
    private function validatedBrandInput(array $data, ?CentralBrand $brand = null): array
    {
        $this->rejectUnsupportedFields($data);

        $validated = Validator::make($data, [
            'name' => ['required', 'string'],
            'slug' => ['sometimes', 'nullable', 'string'],
            'website_url' => ['sometimes', 'nullable', 'string'],
            'country_code' => ['sometimes', 'nullable', 'string'],
        ])->validate();

        $nameInput = $validated['name'];
        assert(is_string($nameInput));
        $name = BrandInputNormalizer::name($nameInput);
        $slug = $this->normalizedSlug($validated, $name, $brand);
        $websiteUrl = $this->normalizedWebsiteUrl($validated, $brand);
        $countryCode = $this->normalizedCountryCode($validated, $brand);

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
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slugRule],
            'website_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'country_code' => ['nullable', 'string', 'regex:/\A[A-Z]{2}\z/'],
        ]);

        $normalizedValidator->after(function ($validator) use ($name, $brand): void {
            if (! $validator->errors()->has('name') && (new DuplicateCentralBrandNameQuery)->exists($name, $brand)) {
                $validator->errors()->add('name', 'A brand with this canonical name already exists.');
            }
        });

        $normalized = $normalizedValidator->validate();

        return [
            'name' => (string) $normalized['name'],
            'slug' => (string) $normalized['slug'],
            'website_url' => isset($normalized['website_url']) ? (string) $normalized['website_url'] : null,
            'country_code' => isset($normalized['country_code']) ? (string) $normalized['country_code'] : null,
        ];
    }

    /** @param array<string, mixed> $data */
    private function rejectUnsupportedFields(array $data): void
    {
        $inputFields = array_map('strval', array_keys($data));
        $unsupported = array_values(array_diff($inputFields, self::SUPPORTED_FIELDS));

        if ($unsupported === []) {
            return;
        }

        $messages = [];

        foreach ($unsupported as $field) {
            $messages[$field] = "The {$field} field is not supported.";
        }

        throw ValidationException::withMessages($messages);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function normalizedSlug(array $validated, string $name, ?CentralBrand $brand): string
    {
        $slugInput = $validated['slug'] ?? null;
        assert(is_string($slugInput) || $slugInput === null);

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

    /** @param array<string, mixed> $validated */
    private function normalizedWebsiteUrl(array $validated, ?CentralBrand $brand): ?string
    {
        if (! array_key_exists('website_url', $validated)) {
            return $brand?->website_url;
        }

        $websiteUrl = $validated['website_url'];
        assert(is_string($websiteUrl) || $websiteUrl === null);

        return BrandInputNormalizer::nullableUrl($websiteUrl);
    }

    /** @param array<string, mixed> $validated */
    private function normalizedCountryCode(array $validated, ?CentralBrand $brand): ?string
    {
        if (! array_key_exists('country_code', $validated)) {
            return $brand?->country_code;
        }

        $countryCode = $validated['country_code'];
        assert(is_string($countryCode) || $countryCode === null);

        return BrandInputNormalizer::countryCode($countryCode);
    }
}
