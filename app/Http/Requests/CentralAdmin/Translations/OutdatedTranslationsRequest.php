<?php

namespace App\Http\Requests\CentralAdmin\Translations;

use App\Data\Translations\TranslationReportFiltersData;
use Illuminate\Foundation\Http\FormRequest;

final class OutdatedTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'locale' => ['nullable', 'string', 'max:20'],
            'entity_type' => ['nullable', 'string', 'in:product,category,attribute,section,option,unit'],
        ];
    }

    public function filters(): TranslationReportFiltersData
    {
        $data = $this->validated();

        return new TranslationReportFiltersData(
            locale: is_string($data['locale'] ?? null) ? $data['locale'] : null,
            entityType: is_string($data['entity_type'] ?? null) ? $data['entity_type'] : null,
        );
    }
}
