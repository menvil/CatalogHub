<?php

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Models\Locale;
use App\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Model;

final class SaveUnitTranslationRequest extends SaveTranslationRequest
{
    protected function translationRules(): array
    {
        return [
            'short_name' => ['nullable', 'string', 'max:100'],
            'long_name' => ['nullable', 'string', 'max:255'],
            'plural_name' => ['nullable', 'string', 'max:255'],
            'symbol_position' => ['nullable', 'string', 'in:before,after'],
            'space_between_value_and_unit' => ['nullable', 'boolean'],
        ];
    }

    protected function currentTranslation(): ?Model
    {
        $unit = $this->route('unit');
        $locale = $this->route('locale');

        return $unit instanceof MeasurementUnit && $locale instanceof Locale
            ? $unit->translations()->where('locale', $locale->code)->first()
            : null;
    }
}
