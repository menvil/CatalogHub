<?php

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Model;

final class SaveAttributeSectionTranslationRequest extends SaveTranslationRequest
{
    protected function translationRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function currentTranslation(): ?Model
    {
        $section = $this->route('section');
        $locale = $this->route('locale');

        return $section instanceof AttributeSection && $locale instanceof Locale
            ? $section->translations()->where('locale', $locale->code)->first()
            : null;
    }
}
