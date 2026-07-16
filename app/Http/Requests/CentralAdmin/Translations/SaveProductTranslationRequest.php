<?php

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Model;

final class SaveProductTranslationRequest extends SaveTranslationRequest
{
    protected function translationRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function currentTranslation(): ?Model
    {
        $product = $this->route('product');
        $locale = $this->route('locale');

        return $product instanceof CentralProduct && $locale instanceof Locale
            ? $product->translations()->where('locale', $locale->code)->first()
            : null;
    }
}
