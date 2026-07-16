<?php

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Models\CentralCatalog\AttributeOption;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Model;

final class SaveAttributeOptionTranslationRequest extends SaveTranslationRequest
{
    protected function translationRules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function currentTranslation(): ?Model
    {
        $option = $this->route('option');
        $locale = $this->route('locale');

        return $option instanceof AttributeOption && $locale instanceof Locale
            ? $option->translations()->where('locale', $locale->code)->first()
            : null;
    }
}
