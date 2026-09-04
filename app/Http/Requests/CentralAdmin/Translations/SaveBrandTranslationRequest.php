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
        $status = $data['status'] ?? TranslationStatus::HumanReviewed->value;

        assert(is_string($status));

        return new BrandTranslationInput(
            name: $this->string('name')->trim()->toString(),
            tagline: $this->nullableTrimmedString('tagline'),
            shortDescription: $this->nullableTrimmedString('short_description'),
            description: $this->nullableTrimmedString('description'),
            seoTitle: $this->nullableTrimmedString('seo_title'),
            seoDescription: $this->nullableTrimmedString('seo_description'),
            status: TranslationStatus::from($status),
        );
    }

    protected function currentTranslation(): ?Model
    {
        $brand = $this->route('brand');
        $locale = $this->route('locale');

        return $brand instanceof CentralBrand && $locale instanceof Locale
            ? $brand->translations()->where('locale_id', $locale->id)->first()
            : null;
    }

    private function nullableTrimmedString(string $key): ?string
    {
        $value = $this->string($key)->trim()->toString();

        return $value === '' ? null : $value;
    }
}
