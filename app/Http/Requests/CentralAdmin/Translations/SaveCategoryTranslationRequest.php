<?php

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Model;

final class SaveCategoryTranslationRequest extends SaveTranslationRequest
{
    protected function translationRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function currentTranslation(): ?Model
    {
        $category = $this->route('category');
        $locale = $this->route('locale');

        return $category instanceof CentralCategory && $locale instanceof Locale
            ? $category->translations()->where('locale', $locale->code)->first()
            : null;
    }
}
