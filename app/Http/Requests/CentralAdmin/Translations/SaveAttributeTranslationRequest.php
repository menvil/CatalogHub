<?php

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Model;

final class SaveAttributeTranslationRequest extends SaveTranslationRequest
{
    protected function translationRules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'short_label' => ['nullable', 'string', 'max:100'],
            'help_text' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function currentTranslation(): ?Model
    {
        $attribute = $this->route('attribute');
        $locale = $this->route('locale');

        return $attribute instanceof AttributeDefinition && $locale instanceof Locale
            ? $attribute->translations()->where('locale', $locale->code)->first()
            : null;
    }
}
