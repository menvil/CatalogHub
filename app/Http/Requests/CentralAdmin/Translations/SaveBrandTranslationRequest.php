<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Data\Translations\BrandTranslationInput;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Model;

final class SaveBrandTranslationRequest extends SaveTranslationRequest
{
    protected function translationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function brandTranslationInput(): BrandTranslationInput
    {
        $data = $this->validated();
        $name = $data['name'] ?? null;
        $status = $data['status'] ?? TranslationStatus::HumanReviewed->value;

        assert(is_string($name));
        assert(is_string($status));

        return new BrandTranslationInput(
            name: trim($name),
            tagline: $this->nullableTrimmedString($data, 'tagline'),
            shortDescription: $this->nullableTrimmedString($data, 'short_description'),
            description: $this->nullableTrimmedString($data, 'description'),
            seoTitle: $this->nullableTrimmedString($data, 'seo_title'),
            seoDescription: $this->nullableTrimmedString($data, 'seo_description'),
            status: TranslationStatus::from($status),
        );
    }

    protected function currentTranslation(): ?Model
    {
        $brand = $this->route('brand');
        $locale = $this->route('locale');

        return $brand instanceof CentralBrand && $locale instanceof Locale
            ? $brand->translations()->where('locale', $locale->code)->first()
            : null;
    }

    /** @param array<string, mixed> $data */
    private function nullableTrimmedString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
